<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    private const QA_ROLES  = ['qa_section_head', 'qa_group_head', 'qa_member'];
    private const MTC_ROLES = ['section_head', 'unit_head', 'group_head', 'member', 'warehouse_mtc', 'user'];

    private function allowedRoles(User $actor): array
    {
        return $actor->isQaSectionHead() ? self::QA_ROLES : self::MTC_ROLES;
    }

    private function scopeQuery(User $actor)
    {
        return $actor->isQaSectionHead()
            ? User::where('department', 'QA')
            : User::where('department', '!=', 'QA');
    }

    public function index()
    {
        $actor  = Auth::user();
        $users  = $this->scopeQuery($actor)->with(['unit', 'group'])->orderBy('role')->orderBy('name')->get();
        $units  = $actor->isQaSectionHead() ? collect() : MaintenanceUnit::with('groups')->get();
        return view('admin.users.index', compact('users', 'units'));
    }

    public function create()
    {
        $actor = Auth::user();
        $units = $actor->isQaSectionHead() ? collect() : MaintenanceUnit::with('groups')->get();
        $roles = $this->allowedRoles($actor);
        return view('admin.users.create', compact('units', 'roles'));
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
            'department' => 'required|string|max:50',
            'unit_id'    => 'nullable|exists:maintenance_units,id',
            'group_id'   => 'nullable|exists:maintenance_groups,id',
        ]);

        if ($actor->isQaSectionHead()) {
            $validated['department'] = 'QA';
        }

        User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $actor = Auth::user();
        abort_unless($this->scopeQuery($actor)->whereKey($user->id)->exists(), 403);

        $units = $actor->isQaSectionHead() ? collect() : MaintenanceUnit::with('groups')->get();
        $roles = $this->allowedRoles($actor);
        return view('admin.users.edit', compact('user', 'units', 'roles'));
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
            'department' => 'required|string|max:50',
            'unit_id'    => 'nullable|exists:maintenance_units,id',
            'group_id'   => 'nullable|exists:maintenance_groups,id',
        ]);

        if ($actor->isQaSectionHead()) {
            $validated['department'] = 'QA';
        }

        if ($request->filled('password')) {
            $request->validate(['password' => Password::min(6)]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        $actor = Auth::user();
        abort_unless($this->scopeQuery($actor)->whereKey($user->id)->exists(), 403);

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    // AJAX: get groups by unit
    public function groupsByUnit(MaintenanceUnit $unit)
    {
        return response()->json($unit->groups()->select('id', 'name')->get());
    }
}
