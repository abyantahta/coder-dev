<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $data = ['user' => $user];

        if ($user->isSectionHead()) {
            $data['totalWo']     = WorkOrder::where('destination', 'maintenance')->count();
            $data['pendingWo']   = WorkOrder::where('destination', 'maintenance')->where('status', 'pending')->count();
            $data['activeWo']    = WorkOrder::where('destination', 'maintenance')->active()->count();
            $data['finishedWo']  = WorkOrder::where('destination', 'maintenance')->where('status', 'finished')->count();
            $data['units']       = MaintenanceUnit::with(['groups', 'members'])->get();
            $data['recentWos']   = WorkOrder::where('destination', 'maintenance')
                ->with(['requester', 'assignedMember', 'unit'])
                ->latest()
                ->limit(8)
                ->get();
            $data['overdueWos']  = WorkOrder::where('destination', 'maintenance')
                ->active()
                ->where('deadline', '<', now())
                ->whereNotNull('deadline')
                ->count();
        }

        elseif ($user->isUnitHead()) {
            $unitId = $user->unit_id;
            $data['pendingWo']   = WorkOrder::where('destination', 'maintenance')->where('status', 'pending')->count();
            $data['activeWo']    = WorkOrder::where('destination', 'maintenance')->where('unit_id', $unitId)->active()->count();
            $data['finishedWo']  = WorkOrder::where('destination', 'maintenance')->where('unit_id', $unitId)->where('status', 'finished')->count();
            $data['overdueWos']  = WorkOrder::where('unit_id', $unitId)->active()->where('deadline', '<', now())->whereNotNull('deadline')->count();
            $data['recentPending'] = WorkOrder::where('destination', 'maintenance')->where('status', 'pending')
                ->with('requester')->latest()->limit(5)->get();
            $data['groups']      = MaintenanceGroup::where('unit_id', $unitId)->with('members')->get();
        }

        elseif ($user->isGroupHead()) {
            $groupId = $user->group_id;
            $data['myGroupWos']  = WorkOrder::where('assigned_group_id', $groupId)->active()->count();
            $data['pendingAssign'] = WorkOrder::where('assigned_group_id', $groupId)->where('status', 'assigned_group')->count();
            $data['completedWo'] = WorkOrder::where('assigned_group_id', $groupId)->where('status', 'finished')->count();
            $data['members']     = User::where('group_id', $groupId)->where('role', 'member')->get();
            $data['recentWos']   = WorkOrder::where('assigned_group_id', $groupId)
                ->with(['requester', 'assignedMember'])->latest()->limit(5)->get();
        }

        elseif ($user->isMember()) {
            $data['myActive']    = WorkOrder::where('assigned_member_id', $user->id)->active()->count();
            $data['myFinished']  = WorkOrder::where('assigned_member_id', $user->id)->where('status', 'finished')->count();
            $data['overdue']     = WorkOrder::where('assigned_member_id', $user->id)->active()
                ->where('deadline', '<', now())->whereNotNull('deadline')->count();
            $data['myWos']       = WorkOrder::where('assigned_member_id', $user->id)
                ->with('requester')->whereIn('status', ['assigned_member', 'rework'])->latest()->get();
        }

        elseif ($user->isWarehouseMtc()) {
            $data['pendingOrders'] = WoPartOrder::where('status', 'pending_warehouse')->count();
            $data['activeOrders']  = WoPartOrder::where('status', 'pr_created')->count();
            $data['receivedMonth'] = WoPartOrder::where('status', 'received')
                ->where('received_at', '>=', now()->startOfMonth())->count();
            $data['overdueOrders'] = WoPartOrder::where('status', 'pr_created')
                ->whereNotNull('expected_arrival')
                ->where('expected_arrival', '<', now())->count();
        }

        elseif ($user->isQaGroupHead() || $user->isQaSectionHead()) {
            $data['pendingWo']   = WorkOrder::where('destination', 'qa')
                ->whereIn('status', ['pending', 'forwarded_qa'])->count();
            $data['activeWo']    = WorkOrder::where('destination', 'qa')->active()->count();
            $data['finishedWo']  = WorkOrder::where('destination', 'qa')->where('status', 'finished')->count();
            $data['needReview']  = WorkOrder::where('destination', 'qa')->where('status', 'completed')->count();
            $data['overdueWos']  = WorkOrder::where('destination', 'qa')
                ->active()->where('deadline', '<', now())->whereNotNull('deadline')->count();
            $data['recentWos']   = WorkOrder::where('destination', 'qa')
                ->with(['requester', 'assignedMember'])->latest()->limit(8)->get();
            $data['qaMembers']   = User::where('role', 'qa_member')
                ->where('department', $user->department)->get();
        }

        elseif ($user->isQaMember()) {
            $data['myActive']    = WorkOrder::where('assigned_member_id', $user->id)->active()->count();
            $data['myFinished']  = WorkOrder::where('assigned_member_id', $user->id)->where('status', 'finished')->count();
            $data['overdue']     = WorkOrder::where('assigned_member_id', $user->id)->active()
                ->where('deadline', '<', now())->whereNotNull('deadline')->count();
            $data['myWos']       = WorkOrder::where('assigned_member_id', $user->id)
                ->with('requester')->whereIn('status', ['assigned_member', 'rework'])->latest()->get();
        }

        else {
            // Regular user
            $data['myWos']       = WorkOrder::where('requester_id', $user->id)->with(['unit', 'assignedMember'])->latest()->get();
            $data['pendingWo']   = WorkOrder::where('requester_id', $user->id)->where('status', 'pending')->count();
            $data['activeWo']    = WorkOrder::where('requester_id', $user->id)->active()->count();
            $data['finishedWo']  = WorkOrder::where('requester_id', $user->id)->where('status', 'finished')->count();
            $data['needReview']  = WorkOrder::where('requester_id', $user->id)->where('status', 'completed')->count();
        }

        return view('dashboard.index', $data);
    }
}
