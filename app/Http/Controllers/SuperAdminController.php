<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function index()
    {
        $departments = Department::withCount(['roles', 'categories', 'approvalSteps', 'users', 'workOrders'])
            ->orderBy('name')
            ->get();

        $stats = [
            'total_depts' => $departments->count(),
            'total_users' => User::count(),
            'total_wos'   => WorkOrder::count(),
        ];

        return view('superadmin.index', compact('departments', 'stats'));
    }

    // ── Departments ───────────────────────────────────────────────────────────

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'code'                => 'required|string|max:10|unique:departments,code',
            'slug'                => 'required|string|max:50|unique:departments,slug|alpha_dash',
            'description'         => 'nullable|string|max:255',
            'color'               => 'required|in:blue,green,purple,red,orange,teal,slate',
            'has_warehouse'       => 'boolean',
            'has_unit_structure'  => 'boolean',
        ]);

        $data['is_active']          = true;
        $data['has_warehouse']      = $request->boolean('has_warehouse');
        $data['has_unit_structure'] = $request->boolean('has_unit_structure');

        Department::create($data);

        return redirect()->route('superadmin.index')->with('success', "Department {$data['name']} berhasil dibuat.");
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'code'                => ['required','string','max:10', Rule::unique('departments','code')->ignore($department->id)],
            'slug'                => ['required','string','max:50','alpha_dash', Rule::unique('departments','slug')->ignore($department->id)],
            'description'         => 'nullable|string|max:255',
            'color'               => 'required|in:blue,green,purple,red,orange,teal,slate',
            'is_active'           => 'boolean',
            'has_warehouse'       => 'boolean',
            'has_unit_structure'  => 'boolean',
        ]);

        $data['is_active']          = $request->boolean('is_active');
        $data['has_warehouse']      = $request->boolean('has_warehouse');
        $data['has_unit_structure'] = $request->boolean('has_unit_structure');

        $department->update($data);

        return redirect()->route('superadmin.index')->with('success', "Department {$department->name} berhasil diupdate.");
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->workOrders()->exists()) {
            return back()->with('error', 'Department tidak bisa dihapus karena masih ada WO terkait.');
        }
        if ($department->users()->exists()) {
            return back()->with('error', 'Department tidak bisa dihapus karena masih ada user terkait.');
        }

        $name = $department->name;
        $department->delete();

        return redirect()->route('superadmin.index')->with('success', "Department {$name} berhasil dihapus.");
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $users = User::with(['dept', 'deptRole'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->dept_id, fn ($q) => $q->where('department_id', $request->dept_id))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments  = Department::where('is_active', true)->orderBy('name')->get();
        $rolesByDept  = $departments->mapWithKeys(fn ($d) =>
            [$d->id => $d->roles()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'key'])]
        );

        return view('superadmin.users', compact('users', 'departments', 'rolesByDept'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'dept_role_id'  => 'nullable|exists:department_roles,id',
            'role'          => 'nullable|string|max:50',
            'is_superadmin' => 'boolean',
        ]);

        $data['password']     = Hash::make($data['password']);
        $data['is_superadmin'] = $request->boolean('is_superadmin');
        $data['department']   = optional(Department::find($data['department_id']))->name ?? 'IT';

        User::create($data);

        return redirect()->route('superadmin.users.index')->with('success', "User {$data['name']} berhasil dibuat.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'password'      => 'nullable|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'dept_role_id'  => 'nullable|exists:department_roles,id',
            'role'          => 'nullable|string|max:50',
            'is_superadmin' => 'boolean',
        ]);

        if ($data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_superadmin'] = $request->boolean('is_superadmin');
        $data['department']    = optional(Department::find($data['department_id']))->name ?? ($user->department ?? 'IT');

        $user->update($data);

        return redirect()->route('superadmin.users.index')->with('success', "User {$user->name} berhasil diupdate.");
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if ($user->submittedWorkOrders()->exists() || $user->assignedWorkOrders()->exists()) {
            return back()->with('error', 'User tidak bisa dihapus karena masih ada WO terkait.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('superadmin.users.index')->with('success', "User {$name} berhasil dihapus.");
    }
}
