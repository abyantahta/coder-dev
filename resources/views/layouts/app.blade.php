<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CODER') — CODER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <script>
        (function () {
            try {
                if (localStorage.getItem('sidebar-hidden') === '1') {
                    document.documentElement.classList.add('sb-hidden');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="bg-slate-100 font-sans">

<div class="flex h-screen overflow-hidden">

    {{-- ── Sidebar ── --}}
    <aside id="sidebar"
        class="w-64 bg-slate-900 text-white flex flex-col shrink-0">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h5M8 15.5h3.5M8.3 18.4l1.1 1.1 2-2.5" />
                </svg>
            </div>
            <div>
                <div class="font-bold text-base tracking-wide">CODER</div>
                <div class="text-slate-400 text-xs">Control Work Order</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            @php $user = auth()->user(); @endphp

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            {{-- Work Orders --}}
            @if (!$user->isMember() || true)
            <a href="{{ route('work-orders.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('work-orders.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Work Orders
            </a>
            @endif

            {{-- Buat WO (untuk non-maintenance, bukan warehouse, bukan QA staff) --}}
            @if (!$user->isMaintenanceStaff() && !$user->isWarehouseMtc() && !$user->isQaStaff())
            <a href="{{ route('work-orders.create') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('work-orders.create') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v16m8-8H4" />
                </svg>
                Buat Work Order
            </a>
            @endif

            {{-- Warehouse MTC --}}
            @if ($user->isWarehouseMtc() || $user->isSectionHead())
            <a href="{{ route('warehouse.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('warehouse.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                Warehouse MTC
            </a>
            @endif

            {{-- QA nav --}}
            @if ($user->isQaGroupHead() || $user->isQaSectionHead())
            <div class="pt-3 pb-1">
                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">QA</div>
            </div>
            <a href="{{ route('performance.qa') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('performance.qa') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Performance QA
            </a>
            @endif

            {{-- Performance (maintenance only) --}}
            @if ($user->isMaintenanceStaff() && !$user->isMember())
            <a href="{{ route('performance.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('performance.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Performance (SR)
            </a>
            @endif

            {{-- Dept Admin (Superuser only) --}}
            @if ($user->isDeptSuperuser())
            <div class="pt-3 pb-1">
                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Dept Admin</div>
            </div>
            <a href="{{ route('dept-admin.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('dept-admin.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Konfigurasi Dept
            </a>
            @endif

            {{-- Super Admin (IT Superadmin only) --}}
            @if ($user->isSuperAdmin())
            <div class="pt-3 pb-1">
                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Super Admin</div>
            </div>
            <a href="{{ route('superadmin.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('superadmin.index') ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Departments
            </a>
            <a href="{{ route('superadmin.users.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('superadmin.users.*') ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Semua Users
            </a>
            @endif

            {{-- Admin (Section Head MTC / QA) --}}
            @if ($user->isSectionHead() || $user->isQaSectionHead())
            <div class="pt-3 pb-1">
                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</div>
            </div>
            <a href="{{ route('admin.users.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Manajemen User
            </a>
            @endif
            @if ($user->isSectionHead())
            <a href="{{ route('admin.units.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs('admin.units.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Unit & Group
            </a>
            @endif

        </nav>

        {{-- User info --}}
        <div class="px-4 py-4 border-t border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-700 rounded-full flex items-center justify-center text-sm font-bold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-400 truncate">{{ \App\Models\User::roleLabel(auth()->user()->role) }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout"
                        class="text-slate-400 hover:text-red-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main Content ── --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top bar --}}
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" type="button" title="Tampilkan/Sembunyikan Sidebar"
                    class="p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-slate-800">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-500">
                <span>{{ auth()->user()->department }}</span>
                <span class="text-slate-300">|</span>
                <span>{{ now()->format('d M Y') }}</span>
            </div>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 mb-2">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto px-6 pb-6 pt-2">
            @yield('content')
        </main>
    </div>

</div>

<script>
    (function () {
        var toggleBtn = document.getElementById('sidebar-toggle');
        if (!toggleBtn) return;
        toggleBtn.addEventListener('click', function () {
            var hidden = document.documentElement.classList.toggle('sb-hidden');
            try { localStorage.setItem('sidebar-hidden', hidden ? '1' : '0'); } catch (e) {}
        });
    })();
</script>
@stack('scripts')
</body>
</html>
