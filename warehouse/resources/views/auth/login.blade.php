@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <img src="{{ asset('assets/images/logo-sdi.png') }}" alt="Logo">
    </div>
    <h5 class="text-center fw-bold mb-1">{{ config('app.name') }}</h5>
    <p class="text-center text-muted mb-4" style="font-size:13px;">Masuk ke akun Anda</p>

    @if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px;">Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="form-control border-start-0 @error('email') is-invalid @enderror"
                    placeholder="email@company.com" autocomplete="email" autofocus>
            </div>
            @error('email')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between">
                <label class="form-label fw-semibold" style="font-size:13px;">Password</label>
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" name="password" id="passwordInput"
                    class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror"
                    placeholder="••••••••" autocomplete="current-password">
                <button type="button" class="input-group-text bg-light border-start-0 cursor-pointer" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
            @error('password')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label text-muted" for="remember" style="font-size:13px;">Ingat saya</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
        </button>
    </form>

    <div class="divider"><span>atau</span></div>

    <a href="{{ route('auth.scan-qr') }}" class="btn btn-outline-secondary w-100">
        <i class="bi bi-qr-code-scan me-2"></i>Masuk dengan QR Code
    </a>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:12px;">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>
</div>

@push('scripts')
<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
@endpush
@endsection
