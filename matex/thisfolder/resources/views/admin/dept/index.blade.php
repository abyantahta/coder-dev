@extends('layouts.app')
@section('title', 'Dept Admin — ' . $dept->name)
@section('page-title', 'Dept Admin — ' . $dept->name)

@section('content')

@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
@endif

{{-- Dept header --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg {{ match($dept->color) {
        'blue' => 'bg-blue-600', 'green' => 'bg-green-600', 'purple' => 'bg-purple-600',
        'red' => 'bg-red-600', 'orange' => 'bg-orange-600', 'teal' => 'bg-teal-600', default => 'bg-slate-600'
    } }}">
        {{ $dept->code }}
    </div>
    <div class="flex-1">
        <h2 class="font-bold text-slate-800 text-lg">{{ $dept->name }}</h2>
        <p class="text-slate-500 text-sm">{{ $dept->description }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('dept-admin.roles.index') }}"
            class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Kelola Roles
        </a>
        <a href="{{ route('dept-admin.categories.index') }}"
            class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Kelola Kategori
        </a>
        <a href="{{ route('dept-admin.steps.index') }}"
            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Alur Approval
        </a>
        <a href="{{ route('dept-admin.qad-config.index') }}"
            class="inline-flex items-center gap-1.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Konfigurasi QAD
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Roles --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Roles ({{ $roles->count() }})</h3>
            <a href="{{ route('dept-admin.roles.index') }}" class="text-xs text-blue-600 hover:underline">Kelola →</a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach ($roles as $role)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-slate-800">{{ $role->name }}</span>
                    @if ($role->is_superuser)
                        <span class="ml-1.5 text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Superuser</span>
                    @endif
                    <div class="text-xs text-slate-400 font-mono">{{ $role->key }}</div>
                </div>
                <span class="text-xs text-slate-500">{{ $role->users_count }} user</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Categories --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Kategori WO ({{ $cats->count() }})</h3>
            <a href="{{ route('dept-admin.categories.index') }}" class="text-xs text-blue-600 hover:underline">Kelola →</a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach ($cats as $cat)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-slate-800">{{ $cat->name }}</span>
                    <span class="ml-1.5 text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">{{ $cat->leadtime_days }} HK</span>
                </div>
                <span class="text-xs text-slate-500">{{ $cat->work_orders_count }} WO</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Approval Steps --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Alur Approval ({{ $steps->count() }} step)</h3>
            <a href="{{ route('dept-admin.steps.index') }}" class="text-xs text-blue-600 hover:underline">Kelola →</a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach ($steps as $step)
            <div class="px-5 py-3 flex items-center gap-3">
                <span class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-xs font-bold text-slate-600">{{ $step->step_order }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">{{ $step->name }}</div>
                    <div class="text-xs text-slate-400">{{ $step->actorRole?->name ?? ucfirst(str_replace('_', ' ', $step->step_type)) }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

@endsection
