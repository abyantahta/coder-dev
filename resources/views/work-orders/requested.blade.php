@extends('layouts.app')
@section('title', 'WO Saya Kirim')
@section('page-title', 'WO Saya Kirim')

@section('content')
@php
    $filterParams = array_filter([
        'search'    => request('search'),
        'dept'      => request('dept'),
        'date_from' => request('date_from'),
        'date_to'   => request('date_to'),
    ], fn ($v) => $v !== null && $v !== '');
    $groupQuery = fn (string $g) => array_merge($filterParams, $g === 'all' ? [] : ['group' => $g]);
    $tabBase = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition';
    $filterControl = 'border border-slate-300 rounded-lg px-3 py-2 text-sm h-10 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500';
    $tabs = [
        'all'      => 'Semua',
        'pending'  => 'Menunggu Diterima',
        'process'  => 'Diproses',
        'review'   => 'Perlu Review Saya',
        'finished' => 'Selesai',
        'closed'   => 'Ditolak / Dibatalkan',
    ];
@endphp

{{-- ── Summary ── --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-5">
    @foreach ([
        ['Total Dikirim', $counts['all'], 'text-slate-800'],
        ['Menunggu Diterima', $counts['pending'], 'text-yellow-600'],
        ['Diproses', $counts['process'], 'text-blue-600'],
        ['Perlu Review Saya', $counts['review'], $counts['review'] > 0 ? 'text-ember' : 'text-slate-400'],
        ['Overdue', $counts['overdue'], $counts['overdue'] > 0 ? 'text-red-600' : 'text-slate-400'],
    ] as [$label, $val, $color])
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="text-xs text-slate-500 mb-1">{{ $label }}</div>
        <div class="text-2xl font-bold {{ $color }}">{{ $val }}</div>
    </div>
    @endforeach
</div>

@if ($counts['review'] > 0 && $group !== 'review')
<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 flex items-center gap-3">
    <span class="text-sm text-orange-800 font-medium">Ada <strong>{{ $counts['review'] }}</strong> WO kiriman kamu yang sudah dikerjakan dan menunggu review.</span>
    <a href="{{ route('work-orders.requested', $groupQuery('review')) }}" class="ml-auto text-sm text-orange-700 font-semibold hover:underline">Review sekarang →</a>
</div>
@endif

{{-- ── Tabs + create ── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
        @foreach ($tabs as $key => $label)
        @php
            $active = $group === $key;
            $highlight = $key === 'review' && $counts['review'] > 0;
        @endphp
        <a href="{{ route('work-orders.requested', $groupQuery($key)) }}"
            class="{{ $tabBase }} {{ $active ? ($highlight ? 'bg-ember text-white shadow-sm' : 'bg-ink text-white shadow-sm') : ($highlight ? 'text-ember hover:bg-white' : 'text-slate-600 hover:bg-white') }}">
            {{ $label }}
            <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center
                {{ $active ? 'bg-white/20 text-white' : ($highlight ? 'bg-ember text-white' : 'bg-slate-200 text-slate-600') }}">
                {{ $counts[$key] }}
            </span>
        </a>
        @endforeach
    </div>
    <a href="{{ route('work-orders.create') }}"
        class="js-open-wo-create inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 h-10 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat WO
    </a>
</div>

{{-- ── Filters ── --}}
<form method="GET" id="req-filter-form" class="bg-white rounded-xl shadow-sm px-4 py-3 mb-4">
    @if ($group !== 'all')
    <input type="hidden" name="group" value="{{ $group }}">
    @endif
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Departemen Tujuan</label>
            <select name="dept" class="{{ $filterControl }} w-48" onchange="this.form.submit()">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $dept)
                <option value="{{ $dept->id }}" {{ (string) request('dept') === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dibuat dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="{{ $filterControl }}" onchange="this.form.submit()">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="{{ $filterControl }}" onchange="this.form.submit()">
        </div>
        <div class="ml-auto">
            <label class="block text-xs font-medium text-slate-600 mb-1">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Judul / No. WO…" class="{{ $filterControl }} w-56">
        </div>
        @if ($filterParams)
        <a href="{{ route('work-orders.requested', $group === 'all' ? [] : ['group' => $group]) }}"
            class="h-10 inline-flex items-center text-sm text-slate-500 hover:text-slate-800">Reset</a>
        @endif
    </div>
</form>

{{-- ── Table ── --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">No. WO</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Judul</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Tujuan</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Status</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Posisi Saat Ini</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Deadline</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Dibuat</th>
                    <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($wos as $wo)
                @php
                    $overdue = $wo->isOverdue();
                    $needsReview = $wo->status === 'completed';
                    $step = $wo->currentStep();
                    $worker = $wo->assignedMember?->name ?? $wo->assignedGroup?->name;
                    $position = match (true) {
                        $wo->status === 'pending' => 'Menunggu diterima '.($wo->targetDepartment?->name ?? 'departemen tujuan'),
                        $needsReview => 'Selesai dikerjakan — menunggu review kamu',
                        in_array($wo->status, ['pending_parts', 'parts_ordered']) => 'Menunggu material'.($worker ? ' · '.$worker : ''),
                        $wo->status === 'finished' => 'Selesai'.($wo->score !== null ? ' · skor '.$wo->score : ''),
                        in_array($wo->status, ['rejected', 'cancelled']) => \Illuminate\Support\Str::limit($wo->rejection_reason ?: '—', 60),
                        default => ($step?->name ?? '—').($worker ? ' · '.$worker : ''),
                    };
                @endphp
                <tr class="hover:bg-slate-50 transition {{ $needsReview ? 'bg-orange-50/40 shadow-[inset_3px_0_0_0_var(--color-ember)]' : ($overdue ? 'bg-red-50/50 shadow-[inset_3px_0_0_0_var(--color-red-500)]' : '') }}">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">
                        <a href="{{ route('work-orders.show', $wo) }}" class="hover:text-blue-700">{{ $wo->wo_number }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('work-orders.show', $wo) }}" class="font-medium text-slate-800 hover:text-blue-700 block max-w-48 truncate" title="{{ $wo->title }}">{{ $wo->title }}</a>
                        @if ($wo->woCategory) <div class="text-xs text-slate-400">{{ $wo->woCategory->name }}</div> @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ $wo->targetDepartment?->name ?? ucfirst($wo->destination) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs min-w-[11rem] {{ $needsReview ? 'text-ember font-semibold' : 'text-slate-600' }}">{{ $position }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($wo->deadline)
                            <span class="{{ $overdue ? 'text-red-600 font-semibold' : 'text-slate-500' }} text-xs">{{ $wo->deadline->format('d M Y, H:i') }}</span>
                            @if ($overdue) <span class="ml-1 text-xs text-red-500">(Overdue)</span> @endif
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">{{ $wo->created_at?->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        @if ($needsReview)
                            <a href="{{ route('work-orders.show', $wo) }}"
                                class="inline-flex items-center bg-ember hover:opacity-90 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">Review →</a>
                        @elseif ($wo->status === 'pending')
                            <button type="button" class="js-req-cancel text-xs font-medium text-red-500 hover:text-red-700"
                                data-action="{{ route('approval.cancel', $wo) }}"
                                data-label="{{ $wo->wo_number }} — {{ $wo->title }}">Batalkan</button>
                        @else
                            <a href="{{ route('work-orders.show', $wo) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Detail →</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-slate-400 py-10">
                        @if ($counts['all'] === 0 && ! $filterParams)
                            Kamu belum pernah mengirim Work Order.
                            <a href="{{ route('work-orders.create') }}" class="js-open-wo-create text-blue-600 hover:underline">Buat WO pertama</a>
                        @else
                            Tidak ada WO yang cocok dengan filter ini.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($wos->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">{{ $wos->links() }}</div>
    @endif
</div>

{{-- Batalkan WO — satu popup untuk semua baris; action diisi dari tombol yang diklik --}}
<style>
    #req-cancel-modal { position: fixed; inset: 0; z-index: 99999; }
    #req-cancel-backdrop { position: absolute; inset: 0; background: rgb(18 22 28 / .48); }
</style>
<div id="req-cancel-modal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="req-cancel-title">
    <div id="req-cancel-backdrop" data-req-cancel-close></div>
    <div class="flex items-center justify-center p-4" style="position:relative;z-index:1;min-height:100%;pointer-events:none;">
        <form method="POST" id="req-cancel-form" action="" class="w-full max-w-md rounded-xl bg-white shadow-lg" style="pointer-events:auto;">
            @csrf
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h2 id="req-cancel-title" class="text-base font-semibold text-slate-900">Batalkan Work Order</h2>
                <button type="button" data-req-cancel-close class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <p class="text-sm text-slate-600">WO <strong id="req-cancel-label" class="text-slate-800"></strong> belum diterima departemen tujuan, jadi masih bisa kamu batalkan.</p>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Alasan pembatalan</label>
                    <textarea name="reason" required rows="3" maxlength="500"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-3">
                <button type="button" data-req-cancel-close class="text-sm text-slate-500 hover:text-slate-700">Kembali</button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Batalkan WO</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('req-cancel-modal');
    const form = document.getElementById('req-cancel-form');
    if (!modal || !form) return;
    const reason = form.querySelector('textarea[name="reason"]');

    function open(btn) {
        form.action = btn.dataset.action;
        document.getElementById('req-cancel-label').textContent = btn.dataset.label;
        reason.value = '';
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        reason.focus();
    }
    function close() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    document.querySelectorAll('.js-req-cancel').forEach((btn) => btn.addEventListener('click', () => open(btn)));
    modal.querySelectorAll('[data-req-cancel-close]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
})();
</script>
@endpush
