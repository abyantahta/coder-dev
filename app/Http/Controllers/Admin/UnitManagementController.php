<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
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
        $unit->delete();
        return back()->with('success', 'Unit berhasil dihapus.');
    }

    public function storeGroup(Request $request, MaintenanceUnit $unit)
    {
        $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        $unit->groups()->create($request->only('name', 'description'));
        return back()->with('success', 'Group berhasil dibuat.');
    }

    public function destroyGroup(MaintenanceGroup $group)
    {
        $group->delete();
        return back()->with('success', 'Group berhasil dihapus.');
    }
}
