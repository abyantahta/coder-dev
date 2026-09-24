@extends('layouts.app')
@section('title', 'PR / PO')
@section('page-title', 'PR / PO')

@section('content')

<div class="flex items-center justify-between gap-3 mb-4">
    <a href="{{ route('warehouse.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-blue-600">
        ← Kembali ke Dashboard
    </a>
    <a href="{{ route('warehouse.standalone.create') }}"
        class="js-open-standalone-create inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-lg">
        + Buat PR Mandiri
    </a>
</div>

{{-- Tabs --}}
<div class="inline-flex items-center gap-1 bg-slate-100 rounded-xl p-1 mb-6">
    @foreach (['pr' => 'PR', 'po' => 'PO'] as $key => $label)
    <a href="{{ route('warehouse.pr-po-history', ['tab' => $key]) }}"
        class="px-4 py-1.5 rounded-lg text-sm font-medium transition {{ $tab === $key ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

@if ($tab === 'pr')
{{-- ═══════════════════════ Tab: PR ═══════════════════════ --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
    @foreach (['all' => 'Semua', 'pending' => 'Belum Disetujui', 'approved' => 'Approval', 'has_po' => 'Sudah Jadi PO', 'closed' => 'Sudah Closed'] as $key => $label)
    <a href="{{ route('warehouse.pr-po-history', ['tab' => 'pr', 'status' => $key]) }}"
        class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $prFilter === $key ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        {{ $label }} ({{ $prCounts[$key] ?? 0 }})
    </a>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <th class="px-4 py-3 w-8"></th>
                    <th class="px-4 py-3">No. PR</th>
                    <th class="px-4 py-3">Asal</th>
                    <th class="px-4 py-3">Status QAD</th>
                    <th class="px-4 py-3">Tgl PR</th>
                    <th class="px-4 py-3">Item</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($prOrders as $order)
                <tr id="pph-row-pr-{{ $order->id }}" role="button" tabindex="0"
                    aria-expanded="false" aria-controls="pph-lines-pr-{{ $order->id }}"
                    onclick="pphToggle('pr-{{ $order->id }}', event)"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); pphToggle('pr-{{ $order->id }}', event); }"
                    class="hover:bg-slate-50 cursor-pointer">
                    <td class="px-4 py-3 text-slate-400">
                        <svg id="pph-chevron-pr-{{ $order->id }}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </td>
                    <td class="px-4 py-3 font-mono text-slate-700">{{ $order->pr_number }}</td>
                    <td class="px-4 py-3">
                        @if ($order->workOrder)
                        <a href="{{ route('work-orders.show', $order->workOrder) }}" class="text-blue-600 hover:underline font-medium" onclick="event.stopPropagation()">
                            {{ $order->workOrder->wo_number }}
                        </a>
                        @else
                        <span class="text-slate-500 font-medium">PR Mandiri</span>
                        @endif
                        <div class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($order->displayTitle(), 40) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 text-xs {{ $order->isApprovedInQad() ? 'text-green-700' : 'text-amber-700' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $order->isApprovedInQad() ? 'bg-green-500' : 'bg-amber-400' }}"></span>
                            {{ $order->qadApprovalLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $order->pr_date?->format('d M Y') ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $order->lines->count() }} item</td>
                </tr>
                <tr id="pph-lines-pr-{{ $order->id }}" class="hidden">
                    <td></td>
                    <td colspan="5" class="px-4 pb-4">
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">Item</th>
                                        <th class="px-3 py-2">Part Code</th>
                                        <th class="px-3 py-2">Qty</th>
                                        <th class="px-3 py-2">UM</th>
                                        <th class="px-3 py-2">No. PO</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($order->lines as $line)
                                    <tr>
                                        <td class="px-3 py-2 text-slate-800">{{ $line->description }}</td>
                                        <td class="px-3 py-2 font-mono text-slate-500">{{ $line->part_code ?: '—' }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $line->quantity }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $line->uom }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $line->qad_po_no ?: '—' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="px-3 py-3 text-center text-slate-400">Tidak ada item.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Tidak ada PR untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($prOrders->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">{{ $prOrders->links() }}</div>
    @endif
</div>

@else
{{-- ═══════════════════════ Tab: PO ═══════════════════════ --}}
<p class="text-xs text-slate-500 mb-3">
    Setiap No. PO yang pernah muncul dari PR di atas. Klik baris untuk lihat seluruh item-nya; klik "Receive" untuk mencatat penerimaan langsung dari sini.
</p>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <th class="px-4 py-3 w-8"></th>
                    <th class="px-4 py-3">No. PO</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($poRows as $i => $row)
                @php
                    $receivedCount = $row->items->filter(fn ($it) => $it->is_received)->count();
                @endphp
                <tr id="pph-row-po-{{ $i }}" role="button" tabindex="0"
                    aria-expanded="false" aria-controls="pph-lines-po-{{ $i }}"
                    onclick="pphToggle('po-{{ $i }}', event)"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); pphToggle('po-{{ $i }}', event); }"
                    class="hover:bg-slate-50 cursor-pointer">
                    <td class="px-4 py-3 text-slate-400">
                        <svg id="pph-chevron-po-{{ $i }}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </td>
                    <td class="px-4 py-3 font-mono text-slate-700">{{ $row->po_no }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $row->items->count() }} item</td>
                    <td class="px-4 py-3">
                        @if ($row->fully_received)
                        <span class="text-xs font-medium text-green-700">✓ Sudah Diterima</span>
                        @else
                        <span class="text-xs text-amber-700">{{ $receivedCount }} / {{ $row->items->count() }} diterima</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($row->fully_received)
                        <span class="text-xs text-slate-400">—</span>
                        @elseif ($row->can_receive)
                        <button type="button" class="js-open-po-receive text-xs font-semibold text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg"
                            data-open-modal="po-receive-modal-{{ $i }}" onclick="event.stopPropagation()">
                            Receive
                        </button>
                        @else
                        <span class="text-xs text-slate-400">Menunggu Penerima</span>
                        @endif
                    </td>
                </tr>
                <tr id="pph-lines-po-{{ $i }}" class="hidden">
                    <td></td>
                    <td colspan="4" class="px-4 pb-4">
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">Item</th>
                                        <th class="px-3 py-2">Qty Dipesan</th>
                                        <th class="px-3 py-2">Qty Diterima</th>
                                        <th class="px-3 py-2">UM</th>
                                        <th class="px-3 py-2">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($row->items as $item)
                                    <tr>
                                        <td class="px-3 py-2 text-slate-800">{{ $item->line->description }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $item->line->quantity }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ rtrim(rtrim(number_format($item->qty_received, 2), '0'), '.') }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $item->line->uom }}</td>
                                        <td class="px-3 py-2">
                                            @if ($item->is_received)
                                            <span class="text-xs font-medium text-green-700">Sudah Diterima</span>
                                            @elseif ($item->qty_received > 0)
                                            <span class="text-xs font-medium text-amber-700">Diterima Sebagian</span>
                                            @else
                                            <span class="text-xs text-slate-400">Belum Diterima</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada PO.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($poRows->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">{{ $poRows->links() }}</div>
    @endif
</div>

{{-- Receive popups — one per PO row that still needs receiving --}}
@foreach ($poRows as $i => $row)
@if (! $row->fully_received && $row->can_receive)
<div id="po-receive-modal-{{ $i }}" class="pph-modal hidden" role="dialog" aria-modal="true">
    <div class="pph-modal-backdrop" data-close-modal></div>
    <div class="flex items-start justify-center p-4 sm:items-center sm:p-6" style="position:relative;z-index:1;min-height:100%;">
        <div class="pph-modal-panel w-full max-w-lg rounded-xl bg-white shadow-lg overflow-y-auto" style="max-height:min(90vh,720px)">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Receive — PO {{ $row->po_no }}</h2>
                <button type="button" data-close-modal class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('warehouse.pr-po-history.receive') }}" class="space-y-4 p-5">
                @csrf
                <input type="hidden" name="po_no" value="{{ $row->po_no }}">
                <div class="divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
                    @foreach ($row->items as $j => $item)
                    @php $remaining = max(0, $item->line->quantity - $item->qty_received); @endphp
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3 {{ $item->is_received ? 'bg-green-50/50' : '' }}">
                        <div class="flex-1 min-w-[160px]">
                            <div class="text-sm font-medium text-slate-800">{{ $item->line->description }}</div>
                            <div class="text-xs text-slate-500">
                                Dipesan {{ $item->line->quantity }} {{ $item->line->uom }}
                                @if ($item->qty_received > 0) · sudah diterima {{ rtrim(rtrim(number_format($item->qty_received, 2), '0'), '.') }} {{ $item->line->uom }} @endif
                            </div>
                        </div>
                        <input type="hidden" name="items[{{ $j }}][line_id]" value="{{ $item->line->id }}">
                        @if ($item->is_received)
                        <input type="hidden" name="items[{{ $j }}][qty_received]" value="0">
                        <span class="text-xs font-medium text-green-700 shrink-0">✓ Lengkap</span>
                        @else
                        <div class="shrink-0">
                            <label class="block text-[11px] text-slate-400 mb-0.5">Diterima sekarang</label>
                            <input type="number" name="items[{{ $j }}][qty_received]" step="1" min="0" max="{{ $remaining }}" value="{{ $remaining }}"
                                class="w-24 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Catatan (opsional)</label>
                    <textarea name="note" rows="2" placeholder="Catatan receiving…"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                        Receive
                    </button>
                    <button type="button" data-close-modal class="text-sm text-slate-500 hover:text-slate-700">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endif

<style>
    .pph-modal { position: fixed; inset: 0; z-index: 99999; }
    .pph-modal-backdrop {
        position: absolute; inset: 0;
        background: rgb(18 22 28 / .48);
        opacity: 0;
        transition: opacity .22s cubic-bezier(.4, 0, .2, 1);
    }
    .pph-modal-panel {
        opacity: 0;
        transform: translateY(18px) scale(.96);
        transition: opacity .22s cubic-bezier(.4, 0, .2, 1), transform .32s cubic-bezier(.16, 1, .3, 1);
    }
    .pph-modal.is-open .pph-modal-backdrop { opacity: 1; }
    .pph-modal.is-open .pph-modal-panel { opacity: 1; transform: translateY(0) scale(1); }
    @media (prefers-reduced-motion: reduce) {
        .pph-modal-backdrop, .pph-modal-panel { transition: none; }
    }
</style>

@push('scripts')
<script>
function pphToggle(id, event) {
    if (event && event.target.closest('a, button')) return;
    const child = document.getElementById('pph-lines-' + id);
    const chevron = document.getElementById('pph-chevron-' + id);
    const parent = document.getElementById('pph-row-' + id);
    if (!child) return;
    const isHidden = child.classList.toggle('hidden');
    if (chevron) chevron.style.transform = isHidden ? '' : 'rotate(90deg)';
    if (parent) parent.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
}

(function () {
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => requestAnimationFrame(() => modal.classList.add('is-open')));
    }
    function closeModal(modal) {
        modal.classList.remove('is-open');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 220);
    }
    document.querySelectorAll('[data-open-modal]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(el.dataset.openModal);
        });
    });
    document.querySelectorAll('.pph-modal [data-close-modal]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(el.closest('.pph-modal'));
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.pph-modal.is-open').forEach(closeModal);
    });
})();
</script>
@endpush

@endsection
