<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStep;
use App\Models\User;
use App\Models\WoCategory;
use App\Models\WoPartOrder;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GaPerformanceController extends Controller
{
    private const CLOSED = ['finished', 'cancelled', 'rejected'];

    public function index()
    {
        $user = Auth::user();
        abort_unless($user->isGaSectionHead() && $user->department_id, 403);

        $deptId = $user->department_id;
        $gaWo = fn () => WorkOrder::where('target_department_id', $deptId);

        // ── Summary ──────────────────────────────────────────────────────────
        $woByStatus = $gaWo()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $finishedRecent = $gaWo()
            ->where('status', 'finished')
            ->where('finished_at', '>=', now()->subMonths(6))
            ->get(['id', 'score', 'deadline', 'completed_at']);

        $withDeadline = $finishedRecent->filter(fn ($wo) => $wo->deadline && $wo->completed_at);
        $summary = [
            'finished' => (int) $woByStatus->get('finished', 0),
            'active' => (int) collect($woByStatus)->except(self::CLOSED)->sum(),
            'avg_score' => $finishedRecent->isNotEmpty() ? round($finishedRecent->avg('score'), 1) : null,
            'on_time_rate' => $withDeadline->isNotEmpty()
                ? round($withDeadline->filter(fn ($wo) => $wo->completed_at->lte($wo->deadline))->count() / $withDeadline->count() * 100, 1)
                : null,
            'need_review' => (int) $woByStatus->get('completed', 0),
            'waiting_material' => (int) $woByStatus->get('pending_parts', 0) + (int) $woByStatus->get('parts_ordered', 0),
            'overdue' => $gaWo()->active()->whereNotNull('deadline')->where('deadline', '<', now())->count(),
        ];

        // ── Monthly trend (6 months) ─────────────────────────────────────────
        $monthlyTrend = $gaWo()
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

        // ── Staff performance ────────────────────────────────────────────────
        // Staff = whoever GA's own assign step(s) hand work to, so this
        // follows the department's approval config instead of a hardcoded
        // role name.
        $assignKeys = ApprovalStep::where('department_id', $deptId)
            ->where('step_type', 'assign')
            ->whereNotNull('assigns_to_role_key')
            ->pluck('assigns_to_role_key');

        $staff = User::where('department_id', $deptId)
            ->whereHas('deptRole', fn ($q) => $q->where('department_id', $deptId)->whereIn('key', $assignKeys))
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($gaWo) {
                $finished = $gaWo()->where('assigned_member_id', $member->id)->where('status', 'finished')->get(['score', 'deadline', 'completed_at']);
                $timed = $finished->filter(fn ($wo) => $wo->deadline && $wo->completed_at);
                $current = $gaWo()->where('assigned_member_id', $member->id)
                    ->whereIn('status', ['assigned_member', 'rework', 'pending_parts', 'parts_ordered', 'parts_received'])
                    ->orderBy('deadline')
                    ->first();

                return (object) [
                    'user' => $member,
                    'active' => $gaWo()->where('assigned_member_id', $member->id)->active()->count(),
                    'finished' => $finished->count(),
                    'rework' => (int) $gaWo()->where('assigned_member_id', $member->id)->sum('rework_count'),
                    'sr' => $finished->isNotEmpty() ? round($finished->avg('score'), 1) : null,
                    'on_time' => $timed->isNotEmpty()
                        ? round($timed->filter(fn ($wo) => $wo->completed_at->lte($wo->deadline))->count() / $timed->count() * 100, 1)
                        : null,
                    'current' => $current,
                ];
            });

        // ── Per category ─────────────────────────────────────────────────────
        $categories = WoCategory::where('department_id', $deptId)
            ->orderBy('sort_order')
            ->get()
            ->map(function (WoCategory $cat) use ($gaWo) {
                $finished = $gaWo()->where('wo_category_id', $cat->id)->where('status', 'finished');

                return (object) [
                    'name' => $cat->name,
                    'leadtime_days' => $cat->leadtime_days,
                    'total' => $gaWo()->where('wo_category_id', $cat->id)->count(),
                    'active' => $gaWo()->where('wo_category_id', $cat->id)->active()->count(),
                    'finished' => (clone $finished)->count(),
                    'avg_score' => (clone $finished)->avg('score') !== null ? round((clone $finished)->avg('score'), 1) : null,
                ];
            });

        // ── Material procurement (GA SH acts as warehouse) ───────────────────
        $gaOrders = fn () => WoPartOrder::where(fn ($q) => $q
            ->where('department_id', $deptId)
            ->orWhereHas('workOrder', fn ($w) => $w->where('target_department_id', $deptId)));

        $receivedRecent = $gaOrders()
            ->where('status', 'received')
            ->whereNotNull('pr_date')
            ->where('received_at', '>=', now()->subMonths(6))
            ->get();

        $procurement = [
            'waiting_pr' => $gaOrders()->where('status', 'pending_warehouse')->count(),
            'pr_created' => $gaOrders()->where('status', 'pr_created')->count(),
            'received_6m' => $receivedRecent->count(),
            'avg_days' => $receivedRecent->isNotEmpty()
                ? round($receivedRecent->avg(fn ($o) => $o->procurement_days ?? 0), 1)
                : null,
        ];

        $openOrders = $gaOrders()
            ->whereIn('status', ['pending_warehouse', 'pr_created'])
            ->with('workOrder')
            ->latest()
            ->take(5)
            ->get();

        return view('performance.ga', compact(
            'summary', 'woByStatus', 'monthlyTrend', 'staff', 'categories', 'procurement', 'openOrders'
        ));
    }
}
