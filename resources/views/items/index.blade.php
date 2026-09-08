@extends('layouts.app')
@section('title', 'Master Data Item')
@section('page-title', 'Master Data Item')

@php
    $syncInFlight = in_array($syncStatus['status'] ?? null, ['queued', 'running'], true);
    $syncBannerClass = match ($syncStatus['status'] ?? null) {
        'ok' => 'border-green-200 bg-green-50 text-green-800',
        'failed' => 'border-red-200 bg-red-50 text-red-800',
        default => 'border-sky-200 bg-sky-50 text-sky-900',
    };
@endphp

@section('content')

{{-- Sync status / action --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold text-slate-800">Sinkronisasi Item QAD</h2>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $totalItems }} item tersimpan.
                @if ($lastSyncedAt)
                    Terakhir sync {{ \Illuminate\Support\Carbon::parse($lastSyncedAt)->diffForHumans() }}
                    ({{ \Illuminate\Support\Carbon::parse($lastSyncedAt)->format('d M Y H:i') }}).
                @else
                    Belum pernah disinkronkan.
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('items.sync') }}">
            @csrf
            <button type="submit" @if ($syncInFlight) disabled @endif
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ $syncInFlight ? 'Sedang sync…' : 'Sync Sekarang' }}
            </button>
        </form>
    </div>

    @if ($syncStatus['message'] ?? null)
    <div class="mt-4 rounded-lg border px-4 py-3 text-sm {{ $syncBannerClass }}">
        <span class="font-medium capitalize">{{ $syncStatus['status'] }}</span>
        — {{ $syncStatus['message'] }}
        @if ($syncInFlight)
        <span class="ms-1 text-xs opacity-80">(auto-refresh tiap 5 detik)</span>
        @endif
    </div>
    @endif
</div>

@if ($syncInFlight)
<script>
    setTimeout(() => window.location.reload(), 5000);
</script>
@endif

{{-- Search --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <form method="GET" action="{{ route('items.index') }}" class="flex gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama / part number…"
            class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Cari
        </button>
        @if (request('q'))
        <a href="{{ route('items.index') }}"
            class="text-sm text-slate-500 hover:text-blue-600 px-3 py-2">Reset</a>
        @endif
    </form>
</div>

{{-- Item table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <th class="px-5 py-3">Kode</th>
                    <th class="px-5 py-3">Deskripsi</th>
                    <th class="px-5 py-3">Part Number</th>
                    <th class="px-5 py-3">Group</th>
                    <th class="px-5 py-3">Prod Line</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Aktif</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($items as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono text-slate-800">{{ $item->qad_code }}</td>
                    <td class="px-5 py-3 text-slate-700">{{ $item->description ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500 font-mono">{{ $item->part_number ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $item->qad_group ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $item->prod_line ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $item->qad_status ?: '—' }}</td>
                    <td class="px-5 py-3">
                        @if ($item->is_active)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Aktif</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Nonaktif</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                        @if (request('q'))
                            Tidak ada item ditemukan untuk "{{ request('q') }}".
                        @else
                            Belum ada data item. Klik "Sync Sekarang" untuk menarik data dari QAD.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $items->links() }}
    </div>
    @endif
</div>

@endsection
