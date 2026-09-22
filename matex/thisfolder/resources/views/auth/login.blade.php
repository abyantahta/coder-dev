<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CODER</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#12161C">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bone font-sans flex items-center justify-center p-4 relative overflow-x-hidden">

    {{-- Warm ambient wash --}}
    <div aria-hidden="true"
         class="pointer-events-none absolute inset-0"
         style="background:
            radial-gradient(60rem 32rem at 50% -12%, rgb(242 121 11 / .10), transparent 70%),
            radial-gradient(40rem 24rem at 105% 105%, rgb(91 100 114 / .10), transparent 70%);">
    </div>

    <div class="w-full max-w-[26rem] relative">

        {{-- Brand --}}
        <div class="text-center mb-8">
            <img src="{{ asset('logo.svg') }}" alt="" width="60" height="60"
                 class="w-15 h-15 mx-auto rounded-2xl shadow-md" style="width:60px;height:60px">
            <h1 class="text-[28px] font-bold tracking-tight text-slate-900 mt-5">CODER</h1>
            <p class="text-slate-500 mt-1.5 text-sm">Control Work Order</p>
            <p class="text-slate-400 mt-0.5 text-xs">PT Sankei Dharma Indonesia</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-md p-7 sm:p-8">
            <h2 class="text-base font-semibold text-slate-900">Masuk ke Akun</h2>
            <p class="text-[13px] text-slate-500 mt-1 mb-6">Gunakan email kantor yang terdaftar.</p>

            @if ($errors->any())
                <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <svg class="w-[18px] h-[18px] shrink-0 mt-px text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-9 4a1 1 0 102 0 1 1 0 00-2 0zm.25-8.75a.75.75 0 011.5 0v4.5a.75.75 0 01-1.5 0v-4.5z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5" for="email">Email</label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email') }}"
                        required autofocus autocomplete="username"
                        class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 transition
                               focus:outline-none focus:border-flame focus:ring-2 focus:ring-flame/25"
                        placeholder="nama@sankei.com"
                    >
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5" for="password">Password</label>
                    <input
                        id="password" name="password" type="password"
                        required autocomplete="current-password"
                        class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 transition
                               focus:outline-none focus:border-flame focus:ring-2 focus:ring-flame/25"
                        placeholder="••••••••"
                    >
                </div>

                <label for="remember" class="flex items-center gap-2.5 cursor-pointer select-none w-fit">
                    <input id="remember" name="remember" type="checkbox"
                        class="w-4 h-4 rounded border-slate-300 text-ember focus:ring-2 focus:ring-flame/30">
                    <span class="text-[13px] text-slate-600">Ingat saya</span>
                </label>

                <button type="submit"
                    class="w-full bg-ember hover:bg-[#A8430A] active:bg-[#8A3708] text-white rounded-xl font-semibold py-3 text-sm shadow-sm transition
                           focus:outline-none focus:ring-2 focus:ring-flame/40 focus:ring-offset-2 focus:ring-offset-white">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-slate-400 text-xs mt-7">© {{ date('Y') }} PT Sankei Dharma Indonesia</p>
    </div>

</body>
</html>
