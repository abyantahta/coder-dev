<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CODER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <div class="bg-blue-600 rounded-lg inline-flex items-center justify-center w-16 h-16 mb-4">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h5M8 15.5h3.5M8.3 18.4l1.1 1.1 2-2.5" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-800">CODER</h1>
            <p class="text-slate-500 mt-1 text-sm">Control Work Order — PT Sankei Dharma Indonesia</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-xl shadow-sm p-8">
            <h2 class="text-xl font-semibold text-slate-800 mb-6">Masuk ke Akun</h2>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">Email</label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email') }}"
                        required autofocus
                        class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="nama@sankei.com"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">Password</label>
                    <input
                        id="password" name="password" type="password"
                        required
                        class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="••••••••"
                    >
                </div>

                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox"
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="remember" class="ml-2 text-sm text-slate-600">Ingat saya</label>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold py-2.5 transition text-sm">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-slate-500 text-xs mt-6">© {{ date('Y') }} PT Sankei Dharma Indonesia</p>
    </div>

</body>
</html>
