<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentQadConfig;
use Illuminate\Http\Request;

class DepartmentQadConfigController extends Controller
{
    public function index()
    {
        $departments = Department::where('is_active', true)
            ->with('qadConfig')
            ->orderBy('name')->get();

        return view('admin.qad-config.index', compact('departments'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'site_code'        => 'required|string|max:20',
            'buyer_code'       => 'nullable|string|max:20',
            'approver_code'    => 'nullable|string|max:20',
            'end_user_id'      => 'nullable|string|max:20',
            'requester_userid' => 'nullable|string|max:20',
        ]);

        DepartmentQadConfig::updateOrCreate(
            ['department_id' => $department->id],
            $request->only('site_code', 'buyer_code', 'approver_code', 'end_user_id', 'requester_userid')
        );

        return back()->with('success', "Konfigurasi QAD untuk {$department->name} berhasil disimpan.");
    }
}
