<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('department')->latest()->get();
        return view('master.users.index', compact('users'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        return view('master.users.form', ['user' => new User(), 'departments' => $departments, 'approverDepartmentIds' => []]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'npk'           => 'required|string|max:20|unique:users,npk',
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6|confirmed',
            'role'          => 'required|in:' . implode(',', User::ROLES),
            'department_id' => 'nullable|exists:departments,id',
            'photo'         => 'nullable|image|max:2048',
            'phone'         => 'nullable|string|max:20',
            'qad_approver_code' => 'nullable|string|max:30',
            'qad_password' => 'nullable|string|max:100',
            'qad_route_to_apr' => 'nullable|string|max:30',
            'qad_route_to_buyer' => 'nullable|string|max:30',
            'qad_requested_by' => 'nullable|string|max:30',
            'qad_end_user_id' => 'nullable|string|max:30',
            'approver_department_ids' => 'nullable|array',
            'approver_department_ids.*' => 'exists:departments,id',
        ]);

        $data = $request->only('npk', 'name', 'email', 'phone', 'role', 'department_id', 'qad_approver_code', 'qad_route_to_apr', 'qad_route_to_buyer', 'qad_requested_by', 'qad_end_user_id') + [
            'password'  => Hash::make($request->password),
            'qr_code'   => Str::uuid(),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('qad_password')) {
            $data['qad_password'] = $request->qad_password;
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos/users', 'public');
        }

        $user = User::create($data);
        $user->approverDepartments()->sync($request->input('approver_department_ids', []));

        return redirect()->route('master.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $departments = Department::where('is_active', true)->get();
        $approverDepartmentIds = $user->approverDepartments()->pluck('departments.id')->all();
        return view('master.users.form', compact('user', 'departments', 'approverDepartmentIds'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'npk'           => 'required|string|max:20|unique:users,npk,' . $user->id,
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'password'      => 'nullable|string|min:6|confirmed',
            'role'          => 'required|in:' . implode(',', User::ROLES),
            'department_id' => 'nullable|exists:departments,id',
            'photo'         => 'nullable|image|max:2048',
            'phone'         => 'nullable|string|max:20',
            'qad_approver_code' => 'nullable|string|max:30',
            'qad_password' => 'nullable|string|max:100',
            'qad_route_to_apr' => 'nullable|string|max:30',
            'qad_route_to_buyer' => 'nullable|string|max:30',
            'qad_requested_by' => 'nullable|string|max:30',
            'qad_end_user_id' => 'nullable|string|max:30',
            'approver_department_ids' => 'nullable|array',
            'approver_department_ids.*' => 'exists:departments,id',
        ]);

        $data = $request->only('npk', 'name', 'email', 'phone', 'role', 'department_id', 'qad_approver_code', 'qad_route_to_apr', 'qad_route_to_buyer', 'qad_requested_by', 'qad_end_user_id') + [
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->filled('qad_password')) {
            $data['qad_password'] = $request->qad_password;
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) Storage::disk('public')->delete($user->photo);
            $data['photo'] = $request->file('photo')->store('photos/users', 'public');
        }

        $user->update($data);
        $user->approverDepartments()->sync($request->input('approver_department_ids', []));

        return redirect()->route('master.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        if ($user->photo) Storage::disk('public')->delete($user->photo);
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    public function regenerateQr(User $user)
    {
        $user->update(['qr_code' => Str::uuid()]);
        return back()->with('success', 'QR Code berhasil di-regenerate.');
    }

    public function qrImage(User $user)
    {
        $svg = QrCode::format('svg')->size(300)->margin(1)->generate($user->qr_code);
        return response($svg)->header('Content-Type', 'image/svg+xml');
    }

    public function qrDownload(User $user)
    {
        $svg      = QrCode::format('svg')->size(400)->margin(2)->generate($user->qr_code);
        $filename = 'QR_' . $user->npk . '_' . str_replace(' ', '_', $user->name) . '.svg';
        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
