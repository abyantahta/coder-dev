<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\DepartmentQadConfig;
use App\Models\DepartmentRole;
use App\Models\WoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DeptAdminController extends Controller
{
    private function dept(): Department
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            if (request()->query('dept')) {
                session(['superadmin_dept' => (int) request()->query('dept')]);
            }
            $deptId = session('superadmin_dept');
            abort_unless($deptId, 400, 'Pilih department dulu dari panel Super Admin.');
            return Department::findOrFail($deptId);
        }

        abort_unless($user->department_id, 403);
        return $user->dept;
    }

    public function index()
    {
        $dept  = $this->dept();
        $roles = $dept->roles()->withCount('users')->get();
        $cats  = $dept->categories()->withCount('workOrders')->get();
        $steps = $dept->approvalSteps()->with('actorRole')->get();

        return view('admin.dept.index', compact('dept', 'roles', 'cats', 'steps'));
    }

    // ── Roles ─────────────────────────────────────────────────────────────────

    public function roles()
    {
        $dept  = $this->dept();
        $roles = $dept->roles()->withCount('users')->get();

        return view('admin.dept.roles', compact('dept', 'roles'));
    }

    public function storeRole(Request $request)
    {
        $dept = $this->dept();
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'key'          => ['required', 'string', 'max:50', 'alpha_dash',
                               Rule::unique('department_roles')->where('department_id', $dept->id)],
            'is_superuser' => 'boolean',
            'level'        => 'required|integer|min:1|max:99',
            'sort_order'   => 'required|integer|min:0',
        ]);

        $validated['is_superuser'] = $request->boolean('is_superuser');
        $validated['department_id'] = $dept->id;

        DepartmentRole::create($validated);

        return back()->with('success', "Role '{$validated['name']}' berhasil ditambahkan.");
    }

    public function updateRole(Request $request, DepartmentRole $role)
    {
        $this->authorizeRole($role);

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'key'          => ['required', 'string', 'max:50', 'alpha_dash',
                               Rule::unique('department_roles')->where('department_id', $role->department_id)->ignore($role->id)],
            'is_superuser' => 'boolean',
            'level'        => 'required|integer|min:1|max:99',
            'sort_order'   => 'required|integer|min:0',
            'is_active'    => 'boolean',
        ]);

        $validated['is_superuser'] = $request->boolean('is_superuser');
        $validated['is_active']    = $request->boolean('is_active');

        $role->update($validated);

        return back()->with('success', "Role '{$role->name}' berhasil diupdate.");
    }

    public function destroyRole(DepartmentRole $role)
    {
        $this->authorizeRole($role);

        if ($role->users()->exists()) {
            return back()->with('error', "Role '{$role->name}' tidak bisa dihapus karena masih dipakai oleh user.");
        }

        $role->delete();

        return back()->with('success', "Role berhasil dihapus.");
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function categories()
    {
        $dept = $this->dept();
        $cats = $dept->categories()->withCount('workOrders')->get();

        return view('admin.dept.categories', compact('dept', 'cats'));
    }

    public function storeCategory(Request $request)
    {
        $dept = $this->dept();
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:300',
            'leadtime_days' => 'required|integer|min:1|max:90',
            'color'         => 'required|in:blue,green,yellow,red,orange,purple,slate',
            'sort_order'    => 'required|integer|min:0',
        ]);

        $validated['department_id'] = $dept->id;
        WoCategory::create($validated);

        return back()->with('success', "Kategori '{$validated['name']}' berhasil ditambahkan.");
    }

    public function updateCategory(Request $request, WoCategory $category)
    {
        $this->authorizeCategory($category);

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:300',
            'leadtime_days' => 'required|integer|min:1|max:90',
            'color'         => 'required|in:blue,green,yellow,red,orange,purple,slate',
            'sort_order'    => 'required|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $category->update($validated);

        return back()->with('success', "Kategori '{$category->name}' berhasil diupdate.");
    }

    public function destroyCategory(WoCategory $category)
    {
        $this->authorizeCategory($category);

        if ($category->workOrders()->exists()) {
            return back()->with('error', "Kategori '{$category->name}' tidak bisa dihapus karena sudah dipakai oleh WO.");
        }

        $category->delete();

        return back()->with('success', "Kategori berhasil dihapus.");
    }

    // ── Approval Steps ────────────────────────────────────────────────────────

    public function approvalSteps()
    {
        $dept  = $this->dept();
        $steps = $dept->approvalSteps()->with('actorRole')->get();
        $roles = $dept->roles()->where('is_active', true)->get();

        return view('admin.dept.approval-steps', compact('dept', 'steps', 'roles'));
    }

    public function storeStep(Request $request)
    {
        $dept = $this->dept();
        $validated = $request->validate([
            'step_order'         => 'required|integer|min:1',
            'name'               => 'required|string|max:100',
            'actor_role_id'      => 'nullable|exists:department_roles,id',
            'step_type'          => 'required|in:standard,spare_parts_check,assign,material_check,completion,requester_review',
            'can_reject'         => 'boolean',
            'can_forward'        => 'boolean',
            'can_assign'         => 'boolean',
            'assigns_to_role_key'=> 'nullable|string|max:50',
            'action_label'       => 'required|string|max:100',
            'reject_label'       => 'nullable|string|max:100',
            'auto_advance_hours' => 'nullable|integer|min:1|max:720',
            'rework_additional_hours' => 'nullable|integer|min:1|max:720',
        ]);

        $validated['department_id'] = $dept->id;
        $validated['can_reject']    = $request->boolean('can_reject');
        $validated['can_forward']   = $request->boolean('can_forward');
        $validated['can_assign']    = $request->boolean('can_assign');

        ApprovalStep::create($validated);

        return back()->with('success', "Step '{$validated['name']}' berhasil ditambahkan.");
    }

    public function updateStep(Request $request, ApprovalStep $step)
    {
        $this->authorizeStep($step);

        $validated = $request->validate([
            'step_order'         => 'required|integer|min:1',
            'name'               => 'required|string|max:100',
            'actor_role_id'      => 'nullable|exists:department_roles,id',
            'step_type'          => 'required|in:standard,spare_parts_check,assign,material_check,completion,requester_review',
            'can_reject'         => 'boolean',
            'can_forward'        => 'boolean',
            'can_assign'         => 'boolean',
            'assigns_to_role_key'=> 'nullable|string|max:50',
            'action_label'       => 'required|string|max:100',
            'reject_label'       => 'nullable|string|max:100',
            'auto_advance_hours' => 'nullable|integer|min:1|max:720',
            'rework_additional_hours' => 'nullable|integer|min:1|max:720',
        ]);

        $validated['can_reject']  = $request->boolean('can_reject');
        $validated['can_forward'] = $request->boolean('can_forward');
        $validated['can_assign']  = $request->boolean('can_assign');

        $step->update($validated);

        return back()->with('success', "Step '{$step->name}' berhasil diupdate.");
    }

    public function destroyStep(ApprovalStep $step)
    {
        $this->authorizeStep($step);
        $step->delete();

        return back()->with('success', "Step berhasil dihapus.");
    }

    public function reorderSteps(Request $request)
    {
        $dept = $this->dept();
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:approval_steps,id',
        ]);

        foreach ($request->order as $pos => $stepId) {
            ApprovalStep::where('id', $stepId)
                ->where('department_id', $dept->id)
                ->update(['step_order' => $pos + 1]);
        }

        return response()->json(['ok' => true]);
    }

    // ── QAD Config ────────────────────────────────────────────────────────────

    public function qadConfig()
    {
        $dept = $this->dept();
        $config = DepartmentQadConfig::firstOrNew(['department_id' => $dept->id]);

        return view('admin.dept.qad-config', compact('dept', 'config'));
    }

    public function updateQadConfig(Request $request)
    {
        $dept = $this->dept();

        $validated = $request->validate([
            'site_code' => 'required|string|max:20',
            'buyer_code' => 'nullable|string|max:20',
            'approver_code' => 'nullable|string|max:20',
            'end_user_id' => 'nullable|string|max:20',
            'requester_userid' => 'nullable|string|max:20',
        ]);

        DepartmentQadConfig::updateOrCreate(['department_id' => $dept->id], $validated);

        return back()->with('success', 'Konfigurasi QAD berhasil disimpan.');
    }

    // ── Auth helpers ──────────────────────────────────────────────────────────

    private function authorizeRole(DepartmentRole $role): void
    {
        abort_unless($role->department_id === $this->dept()->id, 403);
    }

    private function authorizeCategory(WoCategory $category): void
    {
        abort_unless($category->department_id === $this->dept()->id, 403);
    }

    private function authorizeStep(ApprovalStep $step): void
    {
        abort_unless($step->department_id === $this->dept()->id, 403);
    }
}
