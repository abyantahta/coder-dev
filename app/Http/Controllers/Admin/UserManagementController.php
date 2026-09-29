<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    private const QA_ROLES  = ['qa_section_head', 'qa_group_head', 'qa_member', 'user'];
    private const GA_ROLES  = ['ga_section_head', 'member', 'user'];
    private const MTC_ROLES = ['section_head', 'unit_head', 'group_head', 'member', 'warehouse_mtc', 'user'];

    private function allowedRoles(User $actor): array
    {
        if ($actor->isQaSectionHead()) return self::QA_ROLES;
        if ($actor->isGaSectionHead()) return self::GA_ROLES;
        return self::MTC_ROLES;
    }

    private function scopeQuery(User $actor)
    {
        if ($actor->isQaSectionHead()) return User::where('department', 'QA');
        if ($actor->isGaSectionHead()) return User::where('department', 'GA');
        return User::whereNotIn('department', ['QA', 'GA']);
    }

    /**
     * The approval engine (ApprovalService::canAct, the assign dropdowns)
     * works off department_id + dept_role_id, not the legacy `role` /
     * `department` strings — so a user saved here without them could log
     * in but never be assigned or act on a step. Derive both from the
     * actor's department and the chosen legacy role.
     */
    /**
     * This page only ever manages the actor's own department, so a user
     * created/edited here always belongs to it — there is nothing to pick.
     *
     * @return array{slug: string, label: string, name: string}
     */
    private function actorDepartment(User $actor): array
    {
        $slug = $actor->isQaSectionHead() ? 'qa' : ($actor->isGaSectionHead() ? 'ga' : 'maintenance');
        $label = ['qa' => 'QA', 'ga' => 'GA', 'maintenance' => 'Maintenance'][$slug]; // legacy `department` text

        return ['slug' => $slug, 'label' => $label, 'name' => Department::where('slug', $slug)->value('name') ?? $label];
    }

    private function engineFields(User $actor, string $role): array
    {
        $slug = $this->actorDepartment($actor)['slug'];
        $departmentId = Department::where('slug', $slug)->value('id');

        $key = match ($role) {
            'qa_section_head', 'ga_section_head' => 'section_head',
            'qa_group_head' => 'group_head',
            'qa_member' => 'member',
            'member' => $slug === 'ga' ? 'staff' : 'member',
            default => $role,
        };

        $deptRoleId = $departmentId
            ? DepartmentRole::where('department_id', $departmentId)->where('key', $key)->value('id')
            : null;

        return ['department_id' => $departmentId, 'dept_role_id' => $deptRoleId];
    }

    private function unitsFor(User $actor)
    {
        return ($actor->isQaSectionHead() || $actor->isGaSectionHead()) ? collect() : MaintenanceUnit::with('groups')->get();
    }

    public function index()
    {
        $actor  = Auth::user();
        $users  = $this->scopeQuery($actor)->with(['unit', 'group'])->orderBy('role')->orderBy('name')->get();
        $units  = $this->unitsFor($actor);
        return view('admin.users.index', compact('users', 'units'));
    }

    public function create()
    {
        $actor = Auth::user();
        $units = $this->unitsFor($actor);
        $roles = $this->allowedRoles($actor);
        $deptName = $this->actorDepartment($actor)['name'];
        return view('admin.users.create', compact('units', 'roles', 'deptName'));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();
        $roles = $this->allowedRoles($actor);

        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => ['required', Password::min(6)],
            'role'       => 'required|in:' . implode(',', $roles),
            'unit_id'    => 'nullable|exists:maintenance_units,id',
            'group_id'   => 'nullable|exists:maintenance_groups,id',
        ]);
        $validated['department'] = $this->actorDepartment($actor)['label'];

        User::create([
            ...$validated,
            ...$this->engineFields($actor, $validated['role']),
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $actor = Auth::user();
        abort_unless($this->scopeQuery($actor)->whereKey($user->id)->exists(), 403);

        $units = $this->unitsFor($actor);
        $roles = $this->allowedRoles($actor);
        $deptName = $this->actorDepartment($actor)['name'];
        return view('admin.users.edit', compact('user', 'units', 'roles', 'deptName'));
    }

    public function update(Request $request, User $user)
    {
        $actor = Auth::user();
        abort_unless($this->scopeQuery($actor)->whereKey($user->id)->exists(), 403);

        $roles = $this->allowedRoles($actor);

        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'role'       => 'required|in:' . implode(',', $roles),
            'unit_id'    => 'nullable|exists:maintenance_units,id',
            'group_id'   => 'nullable|exists:maintenance_groups,id',
        ]);
        $validated['department'] = $this->actorDepartment($actor)['label'];

        if ($request->filled('password')) {
            $request->validate(['password' => Password::min(6)]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update([...$validated, ...$this->engineFields($actor, $validated['role'])]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        $actor = Auth::user();
        abort_unless($this->scopeQuery($actor)->whereKey($user->id)->exists(), 403);

        if ($user->submittedWorkOrders()->exists() || $user->assignedWorkOrders()->exists()) {
            return back()->with('error', 'User tidak bisa dihapus karena masih ada WO terkait.');
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    // AJAX: get groups by unit
    public function groupsByUnit(MaintenanceUnit $unit)
    {
        return response()->json($unit->groups()->select('id', 'name')->get());
    }
}
