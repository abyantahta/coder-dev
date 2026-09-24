<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CODER') — CODER</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#12161C">

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
<body class="bg-bone font-sans text-slate-900">

@php
    $user = auth()->user();
    $navBase   = 'nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition';
    $navOn     = 'nav-item-active bg-ember text-white shadow-sm';
    $navOff    = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    $navLabel  = 'px-3 pt-5 pb-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-[0.09em]';
@endphp

<div class="flex h-screen overflow-hidden">

    {{-- ── Sidebar ── --}}
    <aside id="sidebar"
        class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-200 shrink-0">
            <img src="{{ asset('logo.svg') }}" alt="" width="36" height="36" class="w-9 h-9 rounded-[10px] shadow-sm shrink-0">
            <div class="min-w-0">
                <div class="font-bold text-[15px] leading-none tracking-tight text-slate-900">CODER</div>
                <div class="text-[11px] leading-none mt-1.5 text-slate-400">Control Work Order</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
                class="{{ $navBase }} {{ request()->routeIs('dashboard') ? $navOn : $navOff }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            {{-- Work Orders --}}
            @php $woNavOn = request()->routeIs('work-orders.*') && !request()->routeIs('work-orders.create'); @endphp
            <a href="{{ route('work-orders.index', ($woInboxCount ?? 0) > 0 ? ['tab' => 'inbox'] : []) }}"
                class="{{ $navBase }} {{ $woNavOn ? $navOn : $navOff }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="flex-1">Work Orders</span>
                @if (($woInboxCount ?? 0) > 0)
                <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center
                    {{ $woNavOn ? 'bg-white/20 text-white' : 'bg-ember text-white' }}">
                    {{ $woInboxCount > 99 ? '99+' : $woInboxCount }}
                </span>
                @endif
            </a>

            {{-- Buat WO: popup di halaman yang sedang dibuka --}}
            <a href="{{ route('work-orders.create') }}" id="nav-wo-create"
                class="js-open-wo-create {{ $navBase }} {{ $navOff }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v16m8-8H4" />
                </svg>
                Buat Work Order
            </a>

            {{-- Warehouse (MTC staff / Section Head, including GA SH) — submenu --}}
            @if ($user->canActAsWarehouse())
            <details class="group" {{ request()->routeIs('warehouse.*') ? 'open' : '' }}>
                <summary class="{{ $navBase }} {{ request()->routeIs('warehouse.*') ? $navOn : $navOff }} cursor-pointer list-none flex items-center justify-between">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        {{ $user->isGaSectionHead() ? 'Warehouse GA' : 'Warehouse MTC' }}
                    </span>
                    <svg class="w-4 h-4 shrink-0 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </summary>
                <div class="mt-1 ml-8 space-y-0.5">
                    <a href="{{ route('warehouse.index') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('warehouse.index') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('warehouse.pr-po-history') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('warehouse.pr-po-history') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        PR / PO / Receiving
                    </a>
                    <a href="{{ route('warehouse.history') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('warehouse.history') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Riwayat PR
                    </a>
                    <a href="{{ route('warehouse.standalone.create') }}"
                        class="js-open-standalone-create block px-3 py-1.5 rounded-lg text-sm text-slate-500 hover:text-slate-800 hover:bg-slate-50">
                        + Buat PR Mandiri
                    </a>
                </div>
            </details>
            @endif

            {{-- QA nav --}}
            @if ($user->isQaGroupHead() || $user->isQaSectionHead())
            <div class="{{ $navLabel }}">QA</div>
            <a href="{{ route('performance.qa') }}"
                class="{{ $navBase }} {{ request()->routeIs('performance.qa') ? $navOn : $navOff }}">
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
                class="{{ $navBase }} {{ request()->routeIs('performance.*') ? $navOn : $navOff }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Performance (SR)
            </a>
            @endif

            {{-- Dept Admin (Superuser only) --}}
            @if ($user->isDeptSuperuser())
            <div class="{{ $navLabel }}">Dept Admin</div>
            <a href="{{ route('dept-admin.index') }}"
                class="{{ $navBase }} {{ request()->routeIs('dept-admin.*') ? $navOn : $navOff }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Konfigurasi Dept
            </a>
            @endif

            {{-- Master Data — consolidated reference/config data (item
                 catalog, departments, users, unit & group), each sub-link
                 still gated by its own existing permission check. --}}
            @php
                $masterDataRoutes = ['items.*', 'superadmin.index', 'superadmin.users.*', 'admin.users.*', 'admin.units.*'];
                $showMasterData = $user->canActAsWarehouse() || $user->isSuperAdmin() || $user->isSectionHead() || $user->isQaSectionHead();
            @endphp
            @if ($showMasterData)
            <details class="group" {{ request()->routeIs($masterDataRoutes) ? 'open' : '' }}>
                <summary class="{{ $navBase }} {{ request()->routeIs($masterDataRoutes) ? $navOn : $navOff }} cursor-pointer list-none flex items-center justify-between">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7c0-1.657 3.582-3 8-3s8 1.343 8 3m-16 0c0 1.657 3.582 3 8 3s8-1.343 8-3m-16 0v10c0 1.657 3.582 3 8 3s8-1.343 8-3V7m-16 5c0 1.657 3.582 3 8 3s8-1.343 8-3" />
                        </svg>
                        Master Data
                    </span>
                    <svg class="w-4 h-4 shrink-0 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </summary>
                <div class="mt-1 ml-8 space-y-0.5">
                    @if ($user->canActAsWarehouse())
                    <a href="{{ route('items.index') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('items.*') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Item
                    </a>
                    @endif
                    @if ($user->isSuperAdmin())
                    <a href="{{ route('superadmin.index') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('superadmin.index') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Departments
                    </a>
                    <a href="{{ route('superadmin.users.index') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('superadmin.users.*') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Users
                    </a>
                    @endif
                    @if ($user->isSectionHead() || $user->isQaSectionHead())
                    <a href="{{ route('admin.users.index') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('admin.users.*') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Users
                    </a>
                    @endif
                    @if ($user->isSectionHead())
                    <a href="{{ route('admin.units.index') }}"
                        class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('admin.units.*') ? 'font-medium text-slate-900 bg-slate-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        Unit & Group
                    </a>
                    @endif
                </div>
            </details>
            @endif

        </nav>

        {{-- User card --}}
        <div class="px-3 py-3 border-t border-slate-200 shrink-0">
            <div class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-50">
                <div class="w-9 h-9 rounded-full bg-ink text-white flex items-center justify-center text-[11px] font-bold tracking-wide shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-semibold text-slate-800 truncate">{{ $user->name }}</div>
                    <div class="text-[11px] text-slate-400 truncate">{{ \App\Models\User::roleLabel($user->role) }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout"
                        class="p-1.5 rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <header class="bg-white border-b border-slate-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <button id="sidebar-toggle" type="button" title="Tampilkan/Sembunyikan Sidebar"
                    class="p-2 -ml-2 rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h1 class="text-[17px] font-semibold tracking-tight text-slate-900 truncate">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="hidden sm:flex items-center gap-2.5 text-[13px] shrink-0">
                <span class="font-medium text-slate-600">{{ $user->department }}</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-slate-400">{{ now()->format('d M Y') }}</span>
            </div>
        </header>

        {{-- Flash messages --}}
        @if (session('success') || session('error'))
        <div class="px-6 pt-4 space-y-2">
            @if (session('success'))
                <div class="flex items-start gap-2.5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <svg class="w-[18px] h-[18px] shrink-0 mt-px text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <svg class="w-[18px] h-[18px] shrink-0 mt-px text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-9 4a1 1 0 102 0 1 1 0 00-2 0zm.25-8.75a.75.75 0 011.5 0v4.5a.75.75 0 01-1.5 0v-4.5z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto px-6 pb-6 pt-4">
            @yield('content')
        </main>
    </div>

</div>

@include('work-orders._create-modal')
@if ($user->canActAsWarehouse())
@include('warehouse._standalone-create-modal')
@endif

<div id="gloading-bar" aria-hidden="true"></div>
<div id="gloading-overlay" role="status" aria-live="polite" aria-hidden="true">
    <div id="gloading-card">
        <div id="gloading-ring"></div>
        <span id="gloading-text">Memproses…</span>
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

<script>
    // Global "something's happening" indicator: a top progress bar + a
    // blocking overlay, shown on every real form submit and same-page link
    // click across the app. Since almost every action here is a full page
    // reload (no SPA), this is the one place that needs to cover all of it —
    // add data-no-loading to a form/link to opt it out.
    (function () {
        var bar = document.getElementById('gloading-bar');
        var overlay = document.getElementById('gloading-overlay');
        if (!bar || !overlay) return;

        var shown = false;
        var safetyTimer = null;
        var barTimers = [];

        function clearBarTimers() {
            barTimers.forEach(clearTimeout);
            barTimers = [];
        }

        function show(label) {
            if (shown) return;
            shown = true;
            document.getElementById('gloading-text').textContent = label || 'Memproses…';
            overlay.classList.add('is-active');
            overlay.setAttribute('aria-hidden', 'false');
            bar.classList.add('is-active');
            bar.style.width = '0%';
            clearBarTimers();
            // Quick early progress, then ease off — the real completion is
            // the next page load, which we can't measure, so this just
            // reads as "still working" rather than claiming to be exact.
            barTimers.push(setTimeout(function () { bar.style.width = '35%'; }, 30));
            barTimers.push(setTimeout(function () { bar.style.width = '65%'; }, 350));
            barTimers.push(setTimeout(function () { bar.style.width = '85%'; }, 1200));
            // Never trap the user behind the overlay if navigation stalls.
            safetyTimer = setTimeout(hide, 25000);
        }

        function hide() {
            if (!shown) return;
            shown = false;
            clearTimeout(safetyTimer);
            clearBarTimers();
            bar.style.width = '100%';
            overlay.classList.remove('is-active');
            overlay.setAttribute('aria-hidden', 'true');
            setTimeout(function () {
                bar.classList.remove('is-active');
                bar.style.width = '0%';
            }, 300);
        }

        window.appLoading = { show: show, hide: hide };

        function isOptedOut(el) {
            return !!(el.closest && el.closest('[data-no-loading]'));
        }

        // Forms — covers every POST/GET submit (create, update, delete,
        // search, receive, etc.) across the whole app in one place.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement) || e.defaultPrevented || isOptedOut(form)) return;

            show();

            // Disable the clicked submit button so a second click can't fire
            // a duplicate submission — the browser has already captured this
            // submission, so disabling now doesn't affect it.
            var submitter = e.submitter || form.querySelector('button[type="submit"]');
            if (submitter && !submitter.disabled) {
                submitter.disabled = true;
                submitter.classList.add('opacity-60', 'cursor-wait');
            }
        }, false);

        // Same-page link navigations (not modal triggers, downloads,
        // external links, or anchors — e.defaultPrevented catches anything
        // a page's own click handler already intercepted, e.g. modal-open
        // links, since those run first in bubble order).
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var link = e.target.closest('a[href]');
            if (!link || isOptedOut(link)) return;

            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download')) return;
            if (/^(mailto:|tel:|javascript:)/i.test(href)) return;

            try {
                if (new URL(href, window.location.href).origin !== window.location.origin) return;
            } catch (err) { return; }

            show();
        }, false);

        // Always reset on a fresh render — covers normal loads, the
        // bfcache-restored back/forward case, and a validation error
        // redirect landing back on the same page.
        window.addEventListener('pageshow', function () { hide(); });
    })();
</script>
@stack('scripts')
</body>
</html>
