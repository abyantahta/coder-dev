<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QaController extends Controller
{
    // ── QA GH: Accept ────────────────────────────────────────────────────────
    public function accept(WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isQaGroupHead() || $user->isQaSectionHead(), 403);
        abort_unless($workOrder->destination === 'qa', 403);
        abort_unless(in_array($workOrder->status, ['pending', 'forwarded_qa']), 422, 'WO sudah diproses.');

        $workOrder->update([
            'status'      => 'accepted',
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);
        $workOrder->addHistory($user->id, 'accepted', 'WO diterima oleh QA ' . $user->name . '.');

        return back()->with('success', 'WO berhasil diterima. Silakan assign ke anggota QA.');
    }

    // ── QA GH: Reject ────────────────────────────────────────────────────────
    public function reject(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isQaGroupHead() || $user->isQaSectionHead(), 403);
        abort_unless($workOrder->destination === 'qa', 403);
        abort_unless(in_array($workOrder->status, ['pending', 'forwarded_qa', 'accepted']), 422);

        $request->validate(['reason' => 'required|string|max:500']);

        $workOrder->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
        $workOrder->addHistory($user->id, 'rejected', 'QA menolak WO: ' . $request->reason);

        return back()->with('success', 'WO berhasil ditolak.');
    }

    // ── QA GH: Forward ke dept lain (auto-accept 1 hari) ────────────────────
    public function forward(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isQaGroupHead() || $user->isQaSectionHead(), 403);
        abort_unless($workOrder->destination === 'qa', 403);
        abort_unless(in_array($workOrder->status, ['pending', 'forwarded_qa', 'accepted']), 422);

        $request->validate([
            'forward_to' => 'required|in:maintenance,ga',
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
            'QA meneruskan ke ' . strtoupper($request->forward_to) . ': ' . $request->reason .
            ' (auto-diterima dalam 1 hari jika tidak direspons)'
        );

        return back()->with('success', 'WO diteruskan ke ' . strtoupper($request->forward_to) . '. Auto-diterima dalam 1×24 jam.');
    }

    // ── QA GH: Assign ke QA Member ───────────────────────────────────────────
    public function assignMember(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isQaGroupHead() || $user->isQaSectionHead(), 403);
        abort_unless($workOrder->destination === 'qa', 403);
        abort_unless($workOrder->status === 'accepted', 422);

        $request->validate(['member_id' => 'required|exists:users,id']);
        $member = User::where('id', $request->member_id)->where('role', 'qa_member')->firstOrFail();

        $deadline = now()->addDays(3);
        $workOrder->update([
            'status'            => 'assigned_member',
            'assigned_member_id'=> $member->id,
            'deadline'          => $deadline,
        ]);
        $workOrder->addHistory(
            $user->id, 'assigned_member',
            "Diassign ke QA member: {$member->name}. Deadline: {$deadline->format('d M Y')}."
        );

        return back()->with('success', "WO diassign ke {$member->name}. Deadline: {$deadline->format('d M Y')}.");
    }

    // ── QA GH: Cancel ────────────────────────────────────────────────────────
    public function cancel(Request $request, WorkOrder $workOrder)
    {
        $user = Auth::user();
        abort_unless($user->isQaGroupHead() || $user->isQaSectionHead(), 403);
        abort_unless($workOrder->destination === 'qa', 403);
        abort_unless(! in_array($workOrder->status, ['finished', 'cancelled', 'rejected']), 422);

        $request->validate(['reason' => 'required|string|max:500']);

        $workOrder->update(['status' => 'cancelled', 'rejection_reason' => $request->reason]);
        $workOrder->addHistory($user->id, 'cancelled', 'QA membatalkan WO: ' . $request->reason);

        return back()->with('success', 'WO berhasil dibatalkan.');
    }

    // ── QA Performance Page ──────────────────────────────────────────────────
    public function performance()
    {
        $user = Auth::user();
        abort_unless($user->isQaGroupHead() || $user->isQaSectionHead(), 403);

        $members = User::where('role', 'qa_member')
            ->where('department', $user->department)
            ->get();

        // Monthly trend: last 6 months, avg score finished QA WOs
        $monthlyTrend = WorkOrder::where('destination', 'qa')
            ->where('status', 'finished')
            ->where('finished_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("DATE_FORMAT(finished_at, '%Y-%m') as month"),
                DB::raw('ROUND(AVG(score), 1) as avg_score'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // WO volume by status for QA
        $woByStatus = WorkOrder::where('destination', 'qa')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('performance.qa', compact('user', 'members', 'monthlyTrend', 'woByStatus'));
    }
}
