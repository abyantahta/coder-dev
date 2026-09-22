<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\Jabatan;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with(['role', 'department', 'jabatan']);
        $sortField = request("sort_field", "name");
        $sortDirection = request("sort_direction", "asc");

        if (request("name")) {
            $query->where("name", "like", "%" . request("name") . "%");
        }

        $users = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $roles = Role::select('id', 'name')->get();
        $departments = Department::select('id', 'name')->get();
        $jabatans = Jabatan::select('id', 'name')->get();

        return inertia("Users/Index", [
            "users" => UserResource::collection($users),
            "roles" => $roles,
            "departments" => $departments,
            "jabatans" => $jabatans,
            "queryParams" => request()->query() ?: null,
            "success" => session('success'),
            "error" => session('error'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return to_route('users.index')->with('success', 'User was updated');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return to_route('users.index')->with('error', 'You cannot delete your own account');
        }

        $isReferenced = Transaction::where('created_by', $user->id)
            ->orWhere('pic', $user->id)
            ->orWhere('updated_by', $user->id)
            ->exists();

        if ($isReferenced) {
            return to_route('users.index')->with('error', 'This user is still referenced by existing transactions and cannot be deleted');
        }

        $user->delete();

        return to_route('users.index')->with('success', 'User was deleted');
    }
}
