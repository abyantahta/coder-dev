@extends('layouts.app')
@section('title', 'Detail Pengadaan Parts')
@section('page-title', 'Pengadaan Parts — ' . $partOrder->workOrder->wo_number)

@section('content')

<a href="{{ route('work-orders.show', $partOrder->workOrder) }}"
    class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-blue-600 mb-4">
    ← Kembali ke {{ $partOrder->workOrder->wo_number }}
</a>

@if (session('success'))
<div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-4">
    {{ session('success') }}
</div>
@endif

{{-- Header card --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <div class="flex items-start justify-between gap-3 mb-2">
        <div>
            <h2 class="font-semibold text-slate-800">{{ $partOrder->workOrder->title }}</h2>
            <div class="text-xs text-slate-500 mt-0.5">
                {{ $partOrder->workOrder->wo_number }} · {{ $partOrder->workOrder->requester->name }}
                ({{ $partOrder->workOrder->requester->department }})
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($partOrder->workOrder->priority) }}">
                {{ ucfirst($partOrder->workOrder->priority) }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WoPartOrder::statusColor($partOrder->status) }}">
                {{ \App\Models\WoPartOrder::statusLabel($partOrder->status) }}
            </span>
        </div>
    </div>
    @if ($partOrder->request_note)
    <div class="text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-2">
        <span class="font-semibold">Catatan Unit Head:</span> {{ $partOrder->request_note }}
    </div>
    @endif
</div>

{{-- Chosen lines --}}
<div class="bg-white rounded-xl shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Item Terpilih</h3>
    </div>
    <div class="divide-y divide-slate-50">
        @forelse ($partOrder->lines as $line)
        <div class="px-5 py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="text-sm font-medium text-slate-800">
                    {{ $line->description }}
                    @if ($line->is_custom)
                    <span class="text-xs text-slate-400 font-normal">(manual)</span>
                    @endif
                </div>
                <div class="text-xs text-slate-500">
                    @if ($line->part_code)<span class="font-mono">{{ $line->part_code }}</span> · @endif
                    {{ $line->quantity }} {{ $line->uom }}
                </div>
            </div>
            @if ($partOrder->status === 'pending_warehouse')
            <form method="POST" action="{{ route('warehouse.orders.remove-line', [$partOrder, $line]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-400 hover:text-red-600 text-sm shrink-0">Hapus</button>
            </form>
            @endif
        </div>
        @empty
        <div class="px-5 py-6 text-center text-slate-400 text-sm">Belum ada item dipilih.</div>
        @endforelse
    </div>
</div>

@if ($partOrder->status === 'pending_warehouse')

{{-- Search QAD catalog --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold text-slate-800 mb-3">Cari Sparepart QAD</h3>
    <form method="GET" action="{{ route('warehouse.orders.show', $partOrder) }}" class="flex gap-2 mb-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama part…"
            class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Cari
        </button>
    </form>

    @if (request('q'))
        @forelse ($results as $item)
        <form method="POST" action="{{ route('warehouse.orders.add-line', $partOrder) }}"
            class="flex flex-wrap items-end gap-2 border border-slate-200 rounded-lg px-3 py-2 mb-2">
            @csrf
            <input type="hidden" name="mode" value="catalog">
            <input type="hidden" name="qad_item_id" value="{{ $item->id }}">
            <div class="flex-1 min-w-[200px]">
                <div class="text-sm font-medium text-slate-800">{{ $item->description ?: $item->qad_code }}</div>
                <div class="text-xs text-slate-500 font-mono">{{ $item->qad_code }}</div>
            </div>
            <input type="number" name="quantity" required step="1" min="1" value="1" placeholder="Qty"
                class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="text" name="uom" required placeholder="UOM"
                class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                + Tambah
            </button>
        </form>
        @empty
        <p class="text-sm text-slate-400">Tidak ada item ditemukan untuk "{{ request('q') }}".</p>
        @endforelse
    @endif
</div>

{{-- Manual add --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold text-slate-800 mb-1">Item Tidak Ditemukan? Tambah Manual</h3>
    <p class="text-xs text-slate-500 mb-3">Untuk item yang belum ada di data master QAD.</p>
    <form method="POST" action="{{ route('warehouse.orders.add-line', $partOrder) }}" class="flex flex-wrap items-end gap-2">
        @csrf
        <input type="hidden" name="mode" value="custom">
        <input type="text" name="description" required placeholder="Nama / deskripsi item"
            class="flex-1 min-w-[200px] border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="number" name="quantity" required step="1" min="1" value="1" placeholder="Qty"
            class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="text" name="uom" required placeholder="UOM"
            class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
            + Tambah Manual
        </button>
    </form>
</div>

{{-- Create PR --}}
<div class="bg-white rounded-xl shadow-sm p-5">
    <h3 class="font-semibold text-slate-800 mb-1">Buat PR</h3>
    <p class="text-xs text-slate-500 mb-3">No. PR akan otomatis diisi oleh QAD (SDI_CreatePR) — tidak perlu diketik manual.</p>
    @if ($partOrder->qad_response && $partOrder->status === 'pending_warehouse')
    <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-xs text-red-700 mb-3">
        Percobaan sebelumnya gagal: {{ $partOrder->qad_response }}
    </div>
    @endif
    @if ($partOrder->lines->isEmpty())
    <p class="text-sm text-slate-400">Tambahkan minimal 1 item sparepart terlebih dahulu.</p>
    @else
    <form method="POST" action="{{ route('warehouse.create-pr', $partOrder->workOrder) }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Butuh Tanggal <span class="text-red-500">*</span></label>
            <input type="date" name="need_date" required value="{{ old('need_date', optional($partOrder->need_date)->toDateString()) }}"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-slate-400 mt-1">Berlaku untuk seluruh item di PR ini (bukan per item).</p>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Catatan (opsional)</label>
            <textarea name="warehouse_note" rows="2"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('warehouse_note', $partOrder->warehouse_note) }}</textarea>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition">
            {{ $partOrder->qad_response && $partOrder->status === 'pending_warehouse' ? 'Buat Ulang PR' : 'Buat PR' }}
        </button>
    </form>
    @endif
</div>

@endif

{{-- Confirm goods received --}}
@if ($partOrder->status === 'pr_created')
<div class="bg-white rounded-xl shadow-sm p-5">
    <h3 class="font-semibold text-slate-800 mb-1">Konfirmasi Barang Diterima</h3>
    <p class="text-xs text-slate-500 mb-3">
        @if ($partOrder->expected_arrival)
        Estimasi tiba {{ $partOrder->expected_arrival->format('d M Y') }}.
        @endif
        Klik setelah barang benar-benar diterima secara fisik.
    </p>
    <form method="POST" action="{{ route('warehouse.receive', $partOrder) }}" class="flex flex-wrap gap-2">
        @csrf
        <input type="text" name="note" placeholder="Catatan receiving (opsional)"
            class="flex-1 min-w-[200px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
        <button type="submit"
            class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            ✓ Barang Sudah Diterima
        </button>
    </form>
</div>
@elseif ($partOrder->status === 'received')
<div class="bg-green-50 border border-green-200 rounded-xl p-5">
    <h3 class="font-semibold text-green-800 mb-1">Barang Sudah Diterima</h3>
    <p class="text-sm text-green-700">
        Diterima {{ $partOrder->received_at?->format('d M Y, H:i') }}.
        WO ini otomatis lanjut ke tahap berikutnya.
    </p>
</div>
@endif

@endsection
