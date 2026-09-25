<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MaintenanceGroup;
use App\Models\User;
use App\Models\WoCategory;
use App\Models\WoPartOrder;
use App\Models\WorkOrder;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $with = ['requester', 'unit', 'assignedGroup', 'assignedMember'];

        $base = WorkOrder::visibleTo($user)->with($with);
        $this->applyFilters($request, $base);

        $closed = ['finished', 'cancelled', 'rejected'];

        $inboxQuery    = (clone $base)->waitingOn($user);
        $inboxIds      = (clone $inboxQuery)->pluck('id');
        $progressQuery = (clone $base)->whereNotIn('status', $closed);
        if ($inboxIds->isNotEmpty()) {
            $progressQuery->whereNotIn('id', $inboxIds);
        }
        $historyQuery  = (clone $base)->whereIn('status', $closed);

        $inboxCount    = (clone $inboxQuery)->count();
        $progressCount = (clone $progressQuery)->count();
        $historyCount  = (clone $historyQuery)->count();

        $tab = $request->query('tab');
        if (! in_array($tab, ['inbox', 'progress', 'history'], true)) {
            $tab = $inboxCount > 0 ? 'inbox' : 'progress';
        }

        $wos = $this->applyListingOrder(match ($tab) {
            'inbox'    => $inboxQuery,
            'history'  => $historyQuery,
            default    => $progressQuery,
        })->paginate(15)->withQueryString();

        $filterGroups = $this->filterGroupsFor($user);

        return view('work-orders.index', compact(
            'wos', 'user', 'tab',
            'inboxCount', 'progressCount', 'historyCount',
            'filterGroups'
        ));
    }

    private function applyListingOrder($query)
    {
        return $query
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END");
    }

    private function applyFilters(Request $request, $query): void
    {
        if ($request->status) { $query->where('status', $request->status); }
        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('wo_number', 'like', '%' . $request->search . '%'));
        }

        $from = $this->parseFilterDate($request->date_from);
        $to   = $this->parseFilterDate($request->date_to);
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from) { $query->whereDate('created_at', '>=', $from->toDateString()); }
        if ($to)   { $query->whereDate('created_at', '<=', $to->toDateString()); }

        if ($request->group_id) {
            $query->where('assigned_group_id', $request->group_id);
        }

        if ($request->member_id) {
            $memberId = (int) $request->member_id;
            if ($request->group_id) {
                $inTeam = User::whereKey($memberId)
                    ->where('group_id', $request->group_id)
                    ->where('role', 'member')
                    ->exists();
                if ($inTeam) {
                    $query->where('assigned_member_id', $memberId);
                }
            } else {
                $query->where('assigned_member_id', $memberId);
            }
        }
    }

    private function parseFilterDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function filterGroupsFor(User $user)
    {
        $query = MaintenanceGroup::with([
            'users' => fn ($q) => $q->whereIn('role', ['group_head', 'member'])->orderBy('name'),
        ])->orderBy('name');

        if ($user->isUnitHead() && $user->unit_id) {
            $query->where('unit_id', $user->unit_id);
        } elseif (($user->isGroupHead() || $user->isMember()) && $user->group_id) {
            $query->where('id', $user->group_id);
        }

        return $query->get()->map(function (MaintenanceGroup $group) {
            $head = $group->users->firstWhere('role', 'group_head');

            return [
                'id'        => $group->id,
                'name'      => $group->name,
                'head_id'   => $head?->id,
                'head_name' => $head?->name ?? $group->name,
                'members'   => $group->users
                    ->where('role', 'member')
                    ->values()
                    ->map(fn (User $m) => ['id' => $m->id, 'name' => $m->name])
                    ->values(),
            ];
        })->values();
    }

    public function create()
    {
        return redirect()->route('work-orders.index', ['buat' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:200',
            'description'          => 'required|string',
            'category'             => 'nullable|string|max:100',
            'target_department_id' => 'required|exists:departments,id',
            'wo_category_id'       => ['required', Rule::exists('wo_categories', 'id')
                ->where('department_id', (int) $request->target_department_id)],
            'attachment'           => 'nullable|file|mimes:pdf,png|max:5120',
        ], [
            'wo_category_id.exists' => 'Kategori WO tidak sesuai dengan departemen tujuan.',
        ]);

        // Priority is no longer asked for at creation — default to medium.
        $validated['priority'] = 'medium';

        $dept     = Department::findOrFail($validated['target_department_id']);
        $woCategory = WoCategory::findOrFail($validated['wo_category_id']);

        $attachment = $this->storeAttachment($request->file('attachment'));
        unset($validated['attachment']);

        $wo = WorkOrder::create([
            ...$validated,
            ...$attachment,
            'wo_number'          => WorkOrder::generateWoNumber($dept),
            'requester_id'       => Auth::id(),
            'destination'        => $dept->slug,
            'leadtime_days'      => $woCategory->leadtime_days,
            'current_step_order' => 1,
            'status'             => 'pending',
        ]);

        $wo->addHistory(Auth::id(), 'created', 'Work Order dibuat.');

        return redirect()->route('work-orders.show', $wo)
            ->with('success', "WO {$wo->wo_number} berhasil dibuat.");
    }

    private function storeAttachment(?UploadedFile $file): array
    {
        if (! $file) {
            return ['attachment_path' => null, 'attachment_name' => null, 'attachment_type' => null];
        }

        $type = $file->getClientOriginalExtension() === 'pdf' ? 'pdf' : 'png';
        $path = 'wo-attachments/' . Str::uuid() . '.' . $type;

        if ($type === 'png' && extension_loaded('gd')) {
            $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
            $width = imagesx($image);
            $height = imagesy($image);
            $maxWidth = 1600;

            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) round($height * ($maxWidth / $width));
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            ob_start();
            imagepng($image, null, 8);
            $contents = ob_get_clean();
            imagedestroy($image);

            Storage::disk('public')->put($path, $contents);
        } else {
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
        }

        return [
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_type' => $type,
        ];
    }

    public function show(WorkOrder $workOrder, ApprovalService $service)
    {
        $user = Auth::user();
        $workOrder->load([
            'requester', 'unit', 'assignedGroup', 'assignedMember', 'acceptedBy',
            'histories.user', 'spareParts', 'partOrder.requestedBy', 'partOrder.handledBy', 'partOrder.lines',
            'targetDepartment', 'woCategory',
        ]);

        $currentStep = $workOrder->currentStep();
        $canAct      = $currentStep && $service->canAct($user, $workOrder);
        $canCancel   = $service->canCancel($user, $workOrder);

        $canEdit = $canAct || $canCancel
            || $workOrder->requester_id === $user->id
            || ($user->managesWarehouseFor($workOrder) && in_array($workOrder->status, ['pending_parts', 'parts_ordered', 'parts_received']));

        // Assignable options for assign steps
        $assignableGroups  = collect();
        $assignableMembers = collect();
        $forwardTargets    = collect();

        if ($canAct && $currentStep->step_type === 'assign') {
            if ($currentStep->assigns_to_role_key === 'group_head') {
                $assignableGroups = MaintenanceGroup::with(['users' => fn ($q) => $q->where('role', 'group_head')])
                    ->when(
                        $user->unit_id,
                        fn ($q) => $q->where('unit_id', $user->unit_id)
                    )->get();
            } else {
                $assignableMembers = User::whereHas('deptRole', fn ($q) =>
                    $q->where('department_id', $workOrder->target_department_id)
                      ->where('key', $currentStep->assigns_to_role_key)
                )->when(
                    $workOrder->assigned_group_id,
                    fn ($q) => $q->where('group_id', $workOrder->assigned_group_id)
                )->get();
            }
        } elseif ($canAct && $currentStep->step_type === 'spare_parts_check') {
            // "Assign to GH" picks the group in the same step, so the group list
            // is needed here too (not just on the following 'assign' step).
            $assignableGroups = MaintenanceGroup::with(['users' => fn ($q) => $q->where('role', 'group_head')])
                ->when(
                    $user->unit_id,
                    fn ($q) => $q->where('unit_id', $user->unit_id)
                )->get();
        }

        if ($canAct && $currentStep?->can_forward) {
            $forwardTargets = Department::where('is_active', true)
                ->where('id', '!=', $workOrder->target_department_id)
                ->get();
        }

        return view('work-orders.show', compact(
            'workOrder', 'user', 'canEdit',
            'currentStep', 'canAct', 'canCancel',
            'assignableGroups', 'assignableMembers', 'forwardTargets'
        ));
    }

    // ── Unit Head: Accept ─────────────────────────────────────────────────────
    public function accept(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isUnitHead() || $user->isSectionHead(), 403);
        abort_unless($workOrder->status === 'pending', 422, 'WO sudah diproses.');

        // Deadline belum di-set di sini — mulai saat assign ke GH
        $workOrder->update([
            'status'      => 'accepted',
            'unit_id'     => $user->unit_id,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);
        $workOrder->addHistory($user->id, 'accepted', 'WO diterima oleh ' . $user->name . '. Lanjutkan cek ketersediaan sparepart.');

        return back()->with('success', 'WO diterima. Silakan lakukan pengecekan sparepart.');
    }

    // ── Unit Head: Forward ke GA / QA ─────────────────────────────────────────
    public function forward(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isUnitHead() || $user->isSectionHead(), 403);
        abort_unless(in_array($workOrder->status, ['pending', 'accepted']), 422);

        $request->validate([
            'forward_to' => 'required|in:ga,qa',
            'reason'     => 'required|string|max:500',
        ]);

        $newStatus = 'forwarded_' . $request->forward_to;
        $workOrder->update([
            'status'         => $newStatus,
            'forwarded_to'   => $request->forward_to,
            'forward_reason' => $request->reason,
            'destination'    => $request->forward_to,
        ]);
        $workOrder->addHistory(
            $user->id, $newStatus,
            'Diteruskan ke ' . strtoupper($request->forward_to) . ': ' . $request->reason
        );

        return back()->with('success', 'WO diteruskan ke ' . strtoupper($request->forward_to) . '.');
    }

    // ── Unit Head: Submit Parts Check ────────────────────────────────────────
    public function checkParts(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isUnitHead() || $user->isSectionHead(), 403);
        abort_unless($workOrder->status === 'accepted', 422);

        $request->validate([
            'parts'                  => 'required|array|min:1',
            'parts.*.part_name'      => 'required|string|max:200',
            'parts.*.part_number'    => 'nullable|string|max:100',
            'parts.*.quantity'       => 'required|numeric|min:0.01',
            'parts.*.unit'           => 'required|string|max:20',
            'parts.*.is_available'   => 'required|in:1,0',
        ]);

        // Delete existing parts for re-submission
        $workOrder->spareParts()->delete();

        $hasUnavailable = false;
        foreach ($request->parts as $partData) {
            $available = (bool) $partData['is_available'];
            if (! $available) { $hasUnavailable = true; }
            $workOrder->spareParts()->create([
                'part_name'    => $partData['part_name'],
                'part_number'  => $partData['part_number'] ?? null,
                'quantity'     => $partData['quantity'],
                'unit'         => $partData['unit'],
                'is_available' => $available,
                'notes'        => $partData['notes'] ?? null,
            ]);
        }

        if ($hasUnavailable) {
            // Create part order request for Warehouse-MTC
            WoPartOrder::create([
                'wo_id'        => $workOrder->id,
                'requested_by' => $user->id,
                'request_note' => $request->input('parts_note'),
                'status'       => 'pending_warehouse',
            ]);
            $workOrder->update(['status' => 'pending_parts']);
            $workOrder->addHistory($user->id, 'pending_parts',
                'Cek sparepart: ada yang tidak tersedia. Diteruskan ke Warehouse-MTC.');

            return back()->with('success', 'Ada sparepart tidak tersedia. Request dikirim ke Warehouse-MTC.');
        }

        // All available — keep status 'accepted', ready to assign group
        $workOrder->addHistory($user->id, 'parts_checked', 'Semua sparepart tersedia. Siap assign ke Group Head.');
        return back()->with('success', 'Semua sparepart tersedia. Silakan assign ke Group Head.');
    }

    // ── Unit Head: Reject ─────────────────────────────────────────────────────
    public function reject(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isUnitHead() || $user->isSectionHead(), 403);
        abort_unless(in_array($workOrder->status, ['pending', 'accepted']), 422);

        $request->validate(['reason' => 'required|string|max:500']);

        $workOrder->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
        $workOrder->addHistory($user->id, 'rejected', 'WO ditolak: ' . $request->reason);

        return back()->with('success', 'WO berhasil ditolak.');
    }

    // ── Unit Head: Assign to Group (leadtime starts here) ────────────────────
    public function assignGroup(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isUnitHead() || $user->isSectionHead(), 403);
        abort_unless(in_array($workOrder->status, ['accepted', 'parts_received']), 422,
            'WO belum siap di-assign (cek parts dulu atau tunggu parts diterima).');

        // Jika masih accepted, pastikan parts sudah dicek atau tidak ada parts
        if ($workOrder->status === 'accepted' && $workOrder->spareParts()->count() === 0) {
            return back()->with('error', 'Lakukan pengecekan sparepart terlebih dahulu.');
        }
        if ($workOrder->hasUnavailableParts() && $workOrder->status !== 'parts_received') {
            return back()->with('error', 'Ada sparepart yang tidak tersedia. Tunggu konfirmasi Warehouse-MTC.');
        }

        $request->validate(['group_id' => 'required|exists:maintenance_groups,id']);
        $group = MaintenanceGroup::findOrFail($request->group_id);

        // Leadtime (7 hari) baru mulai dari sini
        $workOrder->update([
            'status'            => 'assigned_group',
            'assigned_group_id' => $group->id,
            'assigned_group_at' => now(),
            'deadline'          => now()->addDays(7),
            'parts_ready_at'    => $workOrder->status === 'parts_received' ? now() : $workOrder->parts_ready_at,
        ]);
        $workOrder->addHistory($user->id, 'assigned_group',
            "Diassign ke group: {$group->name}. Leadtime 7 hari dimulai.");

        return back()->with('success', "WO diassign ke {$group->name}. Deadline: " . now()->addDays(7)->format('d M Y'));
    }

    // ── Group Head: Assign to Member ─────────────────────────────────────────
    public function assignMember(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isGroupHead() || $user->isSectionHead(), 403);
        abort_unless($workOrder->assigned_group_id === $user->group_id || $user->isSectionHead(), 403);
        abort_unless($workOrder->status === 'assigned_group', 422);

        $request->validate(['member_id' => 'required|exists:users,id']);
        $member = User::where('id', $request->member_id)->where('role', 'member')->firstOrFail();

        $workOrder->update(['status' => 'assigned_member', 'assigned_member_id' => $member->id]);
        $workOrder->addHistory($user->id, 'assigned_member', "Diassign ke: {$member->name}.");

        return back()->with('success', "WO diassign ke {$member->name}.");
    }

    // ── Member / QA Member: Complete ─────────────────────────────────────────
    public function complete(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isMember() || $user->isQaMember() || $user->isSectionHead(), 403);
        abort_unless($workOrder->assigned_member_id === $user->id || $user->isSectionHead(), 403);
        abort_unless(in_array($workOrder->status, ['assigned_member', 'rework']), 422);

        $workOrder->update([
            'status' => 'completed',
            ...$workOrder->freezeActualEnd(),
        ]);

        $note = $workOrder->destination === 'qa'
            ? 'Pekerjaan selesai. Requester wajib konfirmasi dalam 2 hari (auto-confirm jika tidak direspons).'
            : 'Pekerjaan selesai, menunggu review requester.';

        $workOrder->addHistory($user->id, 'completed', $note);

        return back()->with('success', 'WO ditandai selesai, menunggu review.');
    }

    // ── Requester: Review ────────────────────────────────────────────────────
    public function review(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($workOrder->requester_id === $user->id, 403);
        abort_unless($workOrder->status === 'completed', 422);

        $request->validate([
            'action'      => 'required|in:approve,rework',
            'review_note' => 'nullable|string|max:500',
        ]);

        if ($request->action === 'approve') {
            $score = $workOrder->calculateScore();
            $workOrder->update([
                'status'      => 'finished',
                'finished_at' => now(),
                'score'       => $score,
                'review_note' => $request->review_note,
            ]);
            $workOrder->addHistory($user->id, 'finished', "Pekerjaan disetujui. Skor: {$score}.");
        } else {
            $reworkDeadline = ($workOrder->deadline && $workOrder->deadline->isFuture())
                ? $workOrder->deadline->copy()->addHours(48)
                : now()->addHours(48);

            $workOrder->update([
                'status'              => 'rework',
                'rework_count'        => $workOrder->rework_count + 1,
                'rework_requested_at' => now(),
                'rework_deadline'     => $reworkDeadline,
                'deadline'            => $reworkDeadline,
                'review_note'         => $request->review_note,
                ...$workOrder->unfreezeForRework(),
            ]);
            $workOrder->addHistory($user->id, 'rework', 'Rework diminta: ' . $request->review_note);
        }

        return back()->with('success', $request->action === 'approve' ? 'WO selesai!' : 'Rework diminta.');
    }

    // ── Cancel ────────────────────────────────────────────────────────────────
    public function cancel(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isSectionHead() || $user->isUnitHead(), 403);
        abort_unless(! in_array($workOrder->status, ['finished', 'cancelled']), 422);

        $request->validate(['reason' => 'required|string|max:500']);

        $workOrder->update(['status' => 'cancelled', 'rejection_reason' => $request->reason]);
        $workOrder->addHistory($user->id, 'cancelled', 'WO dibatalkan: ' . $request->reason);

        return back()->with('success', 'WO berhasil dibatalkan.');
    }
}
