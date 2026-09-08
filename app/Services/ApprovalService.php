<?php

namespace App\Services;

use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\MaintenanceGroup;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApprovalService
{
    /** Fallback when a requester_review step has no rework_additional_hours configured. */
    private const DEFAULT_REWORK_ADDITIONAL_HOURS = 48;

    public function canAct(User $user, WorkOrder $wo): bool
    {
        $step = $wo->currentStep();
        if (!$step || !$wo->target_department_id) return false;

        // Blocked while warehouse is handling parts
        if (in_array($wo->status, ['pending_parts', 'parts_ordered'])) return false;

        return match ($step->step_type) {
            'requester_review' => $wo->status === 'completed' && $user->id === $wo->requester_id,
            'completion'       => in_array($wo->status, ['assigned_member', 'parts_received', 'rework']) && $user->id === $wo->assigned_member_id,
            'material_check'   => $wo->status === 'assigned_member' && $user->id === $wo->assigned_member_id,
            default            => $step->actor_role_id !== null && $user->dept_role_id === $step->actor_role_id,
        };
    }

    public function canCancel(User $user, WorkOrder $wo): bool
    {
        if (in_array($wo->status, ['finished', 'cancelled', 'rejected'])) return false;
        if (!$wo->target_department_id) return false;

        if ($user->isDeptSuperuser() && $user->department_id === $wo->target_department_id) return true;

        $firstStep = ApprovalStep::where('department_id', $wo->target_department_id)
            ->orderBy('step_order')->first();

        return $firstStep && $user->dept_role_id === $firstStep->actor_role_id;
    }

    public function advance(WorkOrder $wo, User $actor, Request $request): void
    {
        $step = $wo->currentStep();
        abort_unless($step, 400, 'Tidak ada step aktif.');

        match ($step->step_type) {
            'standard'          => $this->handleStandard($wo, $actor),
            'spare_parts_check' => $this->handleSparePartsCheck($wo, $actor, $request),
            'assign'            => $this->handleAssign($wo, $actor, $step, $request),
            'material_check'    => $this->handleMaterialCheck($wo, $actor, $request),
            'completion'        => $this->handleCompletion($wo, $actor, $request),
            'requester_review'  => $this->handleRequesterReview($wo, $actor, $request),
            default             => abort(400, 'Tipe step tidak dikenali.'),
        };
    }

    public function reject(WorkOrder $wo, User $actor, string $reason): void
    {
        $wo->update([
            'status'             => 'rejected',
            'rejection_reason'   => $reason,
            'current_step_order' => null,
        ]);
        $wo->addHistory($actor->id, 'rejected', "WO ditolak. Alasan: {$reason}");
    }

    public function forward(WorkOrder $wo, User $actor, int $targetDeptId, string $reason): void
    {
        $dept = Department::findOrFail($targetDeptId);
        $wo->update([
            'status'               => 'pending',
            // Keep the legacy `destination` enum in sync with the new
            // target_department_id — WorkOrderController::index()'s listing
            // still filters by `destination` for several roles, so leaving
            // it stale made a forwarded WO invisible in the new department's
            // queue.
            'destination'          => $dept->slug,
            'forwarded_to'         => $dept->slug,
            'forward_reason'       => $reason,
            'target_department_id' => $targetDeptId,
            'wo_category_id'       => null,
            'leadtime_days'        => null,
            'current_step_order'   => 1,
        ]);
        $wo->addHistory($actor->id, 'forwarded', "WO diteruskan ke {$dept->name}. Alasan: {$reason}");
    }

    public function cancel(WorkOrder $wo, User $actor, string $reason): void
    {
        $wo->update([
            'status'             => 'cancelled',
            'rejection_reason'   => $reason,
            'current_step_order' => null,
        ]);
        $wo->addHistory($actor->id, 'cancelled', "WO dibatalkan. Alasan: {$reason}");
    }

    public function onPartsReceived(WorkOrder $wo, User $actor): void
    {
        $next = $this->nextStep($wo);
        $wo->update([
            'status'             => 'parts_received',
            'parts_ready_at'     => now(),
            // Deferred-leadtime flows (e.g. GA's material_check) never set a
            // deadline at assign time — start it now that parts are in hand.
            // No-op for flows that already set it (e.g. Maintenance).
            'deadline'           => $wo->deadline ?? $this->addWorkingDays(now(), $wo->leadtime_days ?? 7),
            'current_step_order' => $next?->step_order ?? $wo->current_step_order,
        ]);
        $wo->addHistory($actor->id, 'parts_received', 'Sparepart diterima. Siap dilanjutkan.');
    }

    // ─────────────────────────────────────────────────────────────────────

    private function handleStandard(WorkOrder $wo, User $actor): void
    {
        $next = $this->nextStep($wo);
        $wo->update([
            'status'             => 'accepted',
            'accepted_by'        => $actor->id,
            'accepted_at'        => now(),
            'unit_id'            => $wo->unit_id ?? $actor->unit_id,
            'current_step_order' => $next?->step_order ?? $wo->current_step_order,
        ]);
        $wo->addHistory($actor->id, 'accepted', "WO diterima oleh {$actor->name}.");
    }

    private function handleSparePartsCheck(WorkOrder $wo, User $actor, Request $request): void
    {
        $request->validate([
            'decision' => 'required|in:assign_gh,parts_unavailable',
            'note'     => 'required_if:decision,parts_unavailable|nullable|string|max:1000',
            'group_id' => 'required_if:decision,assign_gh|nullable|integer|exists:maintenance_groups,id',
        ]);

        if ($request->decision === 'assign_gh') {
            // "Assign to GH" both clears the parts check AND assigns the group
            // in one step, so it needs to skip past the following 'assign'
            // (group_head) step too, not just this one.
            $assignStep = $this->nextStep($wo);
            abort_unless(
                $assignStep && $assignStep->step_type === 'assign' && $assignStep->assigns_to_role_key === 'group_head',
                422,
                'Step assign ke Group Head tidak ditemukan setelah pengecekan parts.'
            );

            $group = MaintenanceGroup::findOrFail($request->group_id);
            $afterAssign = ApprovalStep::where('department_id', $wo->target_department_id)
                ->where('step_order', '>', $assignStep->step_order)
                ->orderBy('step_order')
                ->first();

            $wo->update([
                'status'             => 'assigned_group',
                'assigned_group_id'  => $group->id,
                'unit_id'            => $wo->unit_id ?? $group->unit_id,
                'assigned_group_at'  => now(),
                'current_step_order' => $afterAssign?->step_order ?? $assignStep->step_order,
            ]);
            $wo->addHistory($actor->id, 'parts_checked', 'Sparepart tersedia.');
            $wo->addHistory($actor->id, 'assigned_group', "Diassign ke group {$group->name}.");
            return;
        }

        // decision === 'parts_unavailable' — hapus order lama jika ada, buat order baru
        WoPartOrder::where('wo_id', $wo->id)->where('status', 'pending_warehouse')->delete();
        WoPartOrder::create([
            'wo_id'        => $wo->id,
            'requested_by' => $actor->id,
            'request_note' => $request->note,
            'status'       => 'pending_warehouse',
        ]);
        $wo->update(['status' => 'pending_parts']);
        $wo->addHistory($actor->id, 'pending_parts', 'Sparepart tidak tersedia. Diteruskan ke Warehouse-MTC.');
    }

    /**
     * The assigned staffer checks material availability themselves. If
     * available, the leadtime starts now. If not, they order their own PR
     * (self-service — see WarehouseController) and the leadtime starts once
     * onPartsReceived() marks it received.
     */
    private function handleMaterialCheck(WorkOrder $wo, User $actor, Request $request): void
    {
        $request->validate([
            'decision' => 'required|in:available,unavailable',
            'note'     => 'required_if:decision,unavailable|nullable|string|max:1000',
        ]);

        if ($request->decision === 'unavailable') {
            WoPartOrder::where('wo_id', $wo->id)->where('status', 'pending_warehouse')->delete();
            WoPartOrder::create([
                'wo_id'        => $wo->id,
                'requested_by' => $actor->id,
                'request_note' => $request->note,
                'status'       => 'pending_warehouse',
            ]);
            $wo->update(['status' => 'pending_parts']);
            $wo->addHistory($actor->id, 'pending_parts', 'Material tidak tersedia. Memesan PR sendiri.');
            return;
        }

        $next     = $this->nextStep($wo);
        $deadline = $this->addWorkingDays(now(), $wo->leadtime_days ?? 7);
        $wo->update([
            'deadline'           => $deadline,
            'current_step_order' => $next?->step_order ?? $wo->current_step_order,
        ]);
        $wo->addHistory($actor->id, 'material_checked', "Material tersedia. Leadtime dimulai. Deadline: {$deadline->format('d M Y')}.");
    }

    private function handleAssign(WorkOrder $wo, User $actor, ApprovalStep $step, Request $request): void
    {
        $next = $this->nextStep($wo);

        if ($step->assigns_to_role_key === 'group_head') {
            $request->validate(['group_id' => 'required|integer|exists:maintenance_groups,id']);
            $group = MaintenanceGroup::findOrFail($request->group_id);
            $wo->update([
                'status'             => 'assigned_group',
                'assigned_group_id'  => $group->id,
                'unit_id'            => $wo->unit_id ?? $group->unit_id,
                'assigned_group_at'  => now(),
                'current_step_order' => $next?->step_order ?? $wo->current_step_order,
            ]);
            $wo->addHistory($actor->id, 'assigned_group', "Diassign ke group {$group->name}.");
        } else {
            $request->validate(['member_id' => 'required|integer|exists:users,id']);
            $member = User::findOrFail($request->member_id);

            // If the next step checks material availability, the leadtime
            // hasn't started yet — defer the deadline to handleMaterialCheck()
            // / onPartsReceived() instead of setting it here.
            $deadline = $next?->step_type === 'material_check'
                ? null
                : $this->addWorkingDays(now(), $wo->leadtime_days ?? 7);

            $wo->update([
                'status'             => 'assigned_member',
                'assigned_member_id' => $member->id,
                'deadline'           => $deadline,
                'current_step_order' => $next?->step_order ?? $wo->current_step_order,
            ]);

            $wo->addHistory($actor->id, 'assigned_member', $deadline
                ? "Diassign ke {$member->name}. Deadline: {$deadline->format('d M Y')}."
                : "Diassign ke {$member->name}. Leadtime akan mulai setelah pengecekan material.");
        }
    }

    private function handleCompletion(WorkOrder $wo, User $actor, Request $request): void
    {
        $next = $this->nextStep($wo);
        $data = [
            'status'             => 'completed',
            'completed_at'       => now(),
            'completion_note'    => $request->completion_note ?: null,
            'current_step_order' => $next?->step_order ?? $wo->current_step_order,
        ];

        if ($request->hasFile('completion_image')) {
            $file = $request->file('completion_image');
            $path = 'completion-images/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
            $data['completion_image_path'] = $path;
        }

        $wo->update($data);
        $wo->addHistory($actor->id, 'completed', 'Pekerjaan selesai. Menunggu review requester.');
    }

    private function handleRequesterReview(WorkOrder $wo, User $actor, Request $request): void
    {
        if ($request->action === 'approve') {
            $score = $wo->calculateScore();
            $wo->update([
                'status'             => 'finished',
                'score'              => $score,
                'finished_at'        => now(),
                'review_note'        => $request->review_note,
                'current_step_order' => null,
            ]);
            $wo->addHistory($actor->id, 'finished', "Pekerjaan disetujui requester. Skor: {$score}.");
        } else {
            $completionStep = ApprovalStep::where('department_id', $wo->target_department_id)
                ->where('step_type', 'completion')
                ->orderByDesc('step_order')
                ->first();

            $reviewStep = $wo->currentStep();
            $additionalHours = $reviewStep?->rework_additional_hours ?? self::DEFAULT_REWORK_ADDITIONAL_HOURS;

            // If the original deadline hasn't passed yet, the extra time is
            // added on top of what's left (deadline + N hours). If it's
            // already passed, the extra time starts counting from now
            // (now + N hours) instead — configurable per department/step via
            // rework_additional_hours.
            $base = ($wo->deadline && $wo->deadline->isFuture()) ? $wo->deadline : now();

            $reworkCount    = ($wo->rework_count ?? 0) + 1;
            $reworkDeadline = $base->copy()->addHours($additionalHours);
            $wo->update([
                'status'              => 'rework',
                'rework_count'        => $reworkCount,
                'rework_requested_at' => now(),
                'rework_deadline'     => $reworkDeadline,
                'review_note'         => $request->review_note,
                'current_step_order'  => $completionStep?->step_order ?? $wo->current_step_order,
            ]);
            $wo->addHistory($actor->id, 'rework', "Rework #{$reworkCount} diminta (+{$additionalHours} jam). Deadline: {$reworkDeadline->format('d M Y, H:i')}.");
        }
    }

    private function nextStep(WorkOrder $wo): ?ApprovalStep
    {
        return ApprovalStep::where('department_id', $wo->target_department_id)
            ->where('step_order', '>', $wo->current_step_order)
            ->orderBy('step_order')
            ->first();
    }

    private function addWorkingDays(Carbon $date, int $days): Carbon
    {
        $current = $date->copy();
        $added   = 0;
        while ($added < $days) {
            $current->addDay();
            if (!$current->isWeekend()) $added++;
        }
        return $current;
    }
}
