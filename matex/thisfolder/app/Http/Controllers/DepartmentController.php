<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $query = Department::query();
        $sortField = request("sort_field", "name");
        $sortDirection = request("sort_direction", "asc");

        if (request("name")) {
            $query->where("name", "like", "%" . request("name") . "%");
        }

        $departments = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();

        return inertia("Departments/Index", [
            "departments" => DepartmentResource::collection($departments),
            "queryParams" => request()->query() ?: null,
            "success" => session('success'),
            "error" => session('error'),
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        Department::create($request->validated());

        return to_route('departments.index')->with('success', 'Department was created');
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return to_route('departments.index')->with('success', 'Department was updated');
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists() || $department->items()->exists()) {
            return to_route('departments.index')->with('error', 'This department is still assigned to users or items and cannot be deleted');
        }

        $department->delete();

        return to_route('departments.index')->with('success', 'Department was deleted');
    }
}
