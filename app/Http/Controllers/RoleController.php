<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $query = Role::with('permissions');
        $sortField = request("sort_field", "name");
        $sortDirection = request("sort_direction", "asc");

        if (request("name")) {
            $query->where("name", "like", "%" . request("name") . "%");
        }

        $roles = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $permissions = Permission::select('id', 'key', 'label')->get();

        return inertia("Roles/Index", [
            "roles" => RoleResource::collection($roles),
            "permissions" => $permissions,
            "queryParams" => request()->query() ?: null,
            "success" => session('success'),
            "error" => session('error'),
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name']]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return to_route('roles.index')->with('success', 'Role was created');
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();
        $role->update(['name' => $data['name']]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return to_route('roles.index')->with('success', 'Role was updated');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return to_route('roles.index')->with('error', 'This role is still assigned to users and cannot be deleted');
        }

        $role->delete();

        return to_route('roles.index')->with('success', 'Role was deleted');
    }
}
