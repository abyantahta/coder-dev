<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Jabatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register',[
            'roles' => Role::select('id','name')->get(),
            'departments' => Department::select('id','name')->get(),
            'jabatans' => Jabatan::select('id','name')->get(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'jabatan_id' => 'required|exists:jabatans,id',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|string|'
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => intval($request->role),
            'jabatan_id' => $request->jabatan_id,
            'department_id' => $request->department_id,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return redirect(route('items.index', absolute: false));
    }
}
