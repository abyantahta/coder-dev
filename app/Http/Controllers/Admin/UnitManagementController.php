<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class UnitManagementController extends Controller
{
    public function index()
    {
        $units = MaintenanceUnit::with(['groups', 'users'])->get();
        return view('admin.units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        MaintenanceUnit::create($request->only('name', 'description'));
        return back()->with('success', 'Unit berhasil dibuat.');
    }

    public function update(Request $request, MaintenanceUnit $unit)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        $unit->update($request->only('name', 'description'));
        return back()->with('success', 'Unit berhasil diupdate.');
    }

    public function destroy(MaintenanceUnit $unit)
    {
        $groupIds = $unit->groups()->pluck('id');
        $inUse = $unit->users()->exists()
            || $unit->workOrders()->exists()
            || User::whereIn('group_id', $groupIds)->exists()
            || WorkOrder::whereIn('assigned_group_id', $groupIds)->exists();

        if ($inUse) {
            return back()->with('error', "Unit '{$unit->name}' tidak bisa dihapus karena masih dipakai oleh user atau WO (termasuk group di dalamnya).");
        }

        $unit->delete();
        return back()->with('success', 'Unit berhasil dihapus.');
    }

    public function storeGroup(Request $request, MaintenanceUnit $unit)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        $unit->groups()->create($request->only('name', 'description'));
        return back()->with('success', 'Group berhasil dibuat.');
    }

    public function updateGroup(Request $request, MaintenanceGroup $group)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        $group->update($request->only('name', 'description'));
        return back()->with('success', 'Group berhasil diupdate.');
    }

    public function destroyGroup(MaintenanceGroup $group)
    {
        if ($group->users()->exists() || $group->workOrders()->exists()) {
            return back()->with('error', "Group '{$group->name}' tidak bisa dihapus karena masih dipakai oleh user atau WO.");
        }

        $group->delete();
        return back()->with('success', 'Group berhasil dihapus.');
    }
}
