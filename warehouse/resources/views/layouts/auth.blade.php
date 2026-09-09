<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            display: flex;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #ffffff;
        }

        .auth-shell {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ===== LEFT: illustration panel ===== */
        .auth-illustration {
            flex: 1 1 50%;
            position: relative;
            background: #f4f5f8;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .auth-illustration .doodle-wrap {
            position: absolute;
            top: -30px;
            left: -30px;
            width: 560px;
            max-width: 82%;
        }
        .auth-brand {
            position: relative;
            z-index: 1;
            padding: 0 56px 56px;
        }
        .auth-brand h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1a1f3a;
            margin: 0 0 8px;
        }
        .auth-brand p {
            font-size: 14px;
            color: #6c7280;
            max-width: 380px;
            margin: 0;
            line-height: 1.6;
        }

        /* ===== RIGHT: form panel ===== */
        .auth-content {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
        }
        .auth-card {
            width: 100%;
            max-width: 380px;
        }
        .auth-logo {
            width: 64px; height: 64px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            overflow: hidden;
            background: #f4f5f8;
        }
        .auth-logo img { width: 100%; height: 100%; object-fit: contain; }
        .auth-logo i { font-size: 28px; color: #4680ff; }

        @media (max-width: 900px) {
            .auth-illustration { display: none; }
            .auth-content { flex: 1 1 100%; }
        }

        .form-control, .input-group-text {
            border-radius: 10px !important;
            border-color: #e9ecef;
            padding: 10px 14px;
        }
        .form-control:focus {
            border-color: #4680ff;
            box-shadow: 0 0 0 3px rgba(70,128,255,.12);
        }
        .btn-primary {
            background: #4680ff;
            border-color: #4680ff;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover { background: #3a6edc; border-color: #3a6edc; }
        .divider { position: relative; text-align: center; margin: 20px 0; }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%; left: 0; right: 0;
            height: 1px;
            background: #e9ecef;
        }
        .divider span {
            position: relative;
            background: white;
            padding: 0 12px;
            color: #6c757d;
            font-size: 13px;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="auth-shell">
        <div class="auth-illustration">
            <div class="doodle-wrap">
                @include('layouts.partials.auth-doodle')
            </div>
            <div class="auth-brand">
                <h1>{{ config('app.name') }}</h1>
                <p>Kelola permintaan, persetujuan, dan stok ATK perusahaan dalam satu sistem yang simpel dan rapi.</p>
            </div>
        </div>
        <div class="auth-content">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
