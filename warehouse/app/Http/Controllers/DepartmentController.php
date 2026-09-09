<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('users')->latest()->get();
        return view('master.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('master.departments.form', ['department' => new Department()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:departments,code',
            'name' => 'required|string|max:100',
        ], [
            'code.unique' => 'Kode departemen sudah digunakan.',
        ]);

        Department::create($request->only('code', 'name', 'is_active') + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('master.departments.index')->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function edit(Department $department)
    {
        return view('master.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:departments,code,' . $department->id,
            'name' => 'required|string|max:100',
        ]);

        $department->update([
            'code'      => $request->code,
            'name'      => $request->name,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('master.departments.index')->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Departemen tidak bisa dihapus karena masih memiliki user.');
        }
        $department->delete();
        return back()->with('success', 'Departemen berhasil dihapus.');
    }
}
