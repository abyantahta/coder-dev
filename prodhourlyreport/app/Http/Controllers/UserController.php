<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Line;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'role' => $request->string('role')->trim()->toString() ?: null,
            'line_id' => $request->integer('line_id') ?: null,
        ];

        $users = User::query()
            ->with('lines:id,name')
            ->when($filters['search'], function ($query, string $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when($filters['role'], fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['line_id'], function ($query, int $lineId) {
                $query->whereHas('lines', fn ($q) => $q->where('lines.id', $lineId));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active']);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'lines' => Line::orderBy('name')->get(['id', 'name']),
            'roleOptions' => UserRole::options(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
            'line_ids' => ['array'],
            'line_ids.*' => ['exists:lines,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->lines()->sync($data['line_ids'] ?? []);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
            'line_ids' => ['array'],
            'line_ids.*' => ['exists:lines,id'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
            ...(filled($data['password'] ?? null) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        $user->lines()->sync($data['line_ids'] ?? []);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
