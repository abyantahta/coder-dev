<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Email atau password salah.')->withInput(['email' => $request->email]);
        }

        if (!$user->is_active) {
            return back()->with('error', 'Akun Anda tidak aktif. Hubungi Administrator.');
        }

        Auth::login($user, $request->boolean('remember'));

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function scanQrForm()
    {
        return view('auth.scan-qr');
    }

    public function scanQr(Request $request)
    {
        $request->validate(['qr_code' => 'required|string']);

        $user = User::where('qr_code', $request->qr_code)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'QR Code tidak valid atau akun tidak aktif.'], 404);
        }

        Auth::login($user);

        return response()->json([
            'success'  => true,
            'message'  => 'Selamat datang, ' . $user->name,
            'redirect' => route('transactions.goods-out.scan'),
            'user'     => [
                'name'       => $user->name,
                'npk'        => $user->npk,
                'department' => $user->department?->name,
                'photo'      => $user->photo ? asset('storage/' . $user->photo) : asset('assets/images/user/avatar-1.jpg'),
            ],
        ]);
    }
}
