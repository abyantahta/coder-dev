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
    public function canAct(User $user, WorkOrder $wo): bool
    {
        $step = $wo->currentStep();
        if (!$step || !$wo->target_department_id) return false;

        // Blocked while warehouse is handling parts
        if (in_array($wo->status, ['pending_parts', 'parts_ordered'])) return false;

        return match ($step->step_type) {
            'requester_review' => $wo->status === 'completed' && $user->id === $wo->requester_id,
            'completion'       => in_array($wo->status, ['assigned_member', 'rework']) && $user->id === $wo->assigned_member_id,
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
            'current_step_order' => $next?->step_order ?? $wo->current_step_order,
        ]);
        $wo->addHistory($actor->id, 'parts_received', 'Sparepart diterima Warehouse-MTC. Siap dilanjutkan.');
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
            $member   = User::findOrFail($request->member_id);
            $deadline = $this->addWorkingDays(now(), $wo->leadtime_days ?? 7);
            $wo->update([
                'status'             => 'assigned_member',
                'assigned_member_id' => $member->id,
                'deadline'           => $deadline,
                'current_step_order' => $next?->step_order ?? $wo->current_step_order,
            ]);
            $wo->addHistory($actor->id, 'assigned_member', "Diassign ke {$member->name}. Deadline: {$deadline->format('d M Y')}.");
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

            $reworkCount    = ($wo->rework_count ?? 0) + 1;
            $reworkDeadline = $this->addWorkingDays(now(), 2);
            $wo->update([
                'status'              => 'rework',
                'rework_count'        => $reworkCount,
                'rework_requested_at' => now(),
                'rework_deadline'     => $reworkDeadline,
                'review_note'         => $request->review_note,
                'current_step_order'  => $completionStep?->step_order ?? $wo->current_step_order,
            ]);
            $wo->addHistory($actor->id, 'rework', "Rework #{$reworkCount} diminta. Deadline: {$reworkDeadline->format('d M Y')}.");
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
