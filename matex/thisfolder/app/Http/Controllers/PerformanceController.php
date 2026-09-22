<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isSectionHead()) {
            $units   = MaintenanceUnit::with(['groups.members'])->get();
            $members = User::where('role', 'member')->with(['group', 'unit'])->get();
        } elseif ($user->isUnitHead()) {
            $units   = MaintenanceUnit::where('id', $user->unit_id)->with(['groups.members'])->get();
            $members = User::where('unit_id', $user->unit_id)->where('role', 'member')->with('group')->get();
        } elseif ($user->isGroupHead()) {
            $units   = collect();
            $members = User::where('group_id', $user->group_id)->where('role', 'member')->with('group')->get();
        } else {
            abort(403);
        }

        // Monthly trend: last 6 months, avg score of finished WOs per month
        $monthlyTrend = WorkOrder::where('status', 'finished')
            ->where('destination', 'maintenance')
            ->where('finished_at', '>=', now()->subMonths(6))
            ->when($user->isUnitHead(), fn ($q) => $q->where('unit_id', $user->unit_id))
            ->when($user->isGroupHead(), fn ($q) => $q->where('assigned_group_id', $user->group_id))
            ->select(
                DB::raw("DATE_FORMAT(finished_at, '%Y-%m') as month"),
                DB::raw('ROUND(AVG(score), 1) as avg_score'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // WO volume by status
        $woByStatus = WorkOrder::where('destination', 'maintenance')
            ->when($user->isUnitHead(), fn ($q) => $q->where('unit_id', $user->unit_id))
            ->when($user->isGroupHead(), fn ($q) => $q->where('assigned_group_id', $user->group_id))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Procurement trend (last 6 months)
        $procurementTrend = WoPartOrder::where('status', 'received')
            ->where('received_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("DATE_FORMAT(received_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(DATEDIFF(received_at, pr_date)) as avg_days')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $allReceived = WoPartOrder::where('status', 'received')->whereNotNull('pr_date')->whereNotNull('received_at')->get();
        $onTime = $allReceived->filter(fn ($o) => $o->procurement_days !== null && $o->procurement_days <= 30)->count();
        $procurementCompliance = $allReceived->count() > 0
            ? round(($onTime / $allReceived->count()) * 100, 1)
            : null;

        return view('performance.index', compact(
            'user', 'units', 'members', 'monthlyTrend', 'woByStatus',
            'procurementTrend', 'procurementCompliance'
        ));
    }
}
