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
        <span class="font-semibold">Catatan:</span> {{ $partOrder->request_note }}
    </div>
    @endif
</div>

{{-- QAD approval / PO status --}}
@if ($partOrder->pr_number)
<div class="bg-white rounded-xl shadow-sm p-5 mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <div class="text-xs text-slate-500 mb-1">Status QAD · PR {{ $partOrder->pr_number }}</div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full {{ $partOrder->isApprovedInQad() ? 'bg-green-500' : 'bg-amber-400' }}"></span>
            <span class="text-sm font-semibold {{ $partOrder->isApprovedInQad() ? 'text-green-700' : 'text-amber-700' }}">
                {{ $partOrder->qadApprovalLabel() }}
            </span>
        </div>
    </div>
    {{-- Keep the check available until there's an actual PO number — being
         "approved" and having a PO issued are two separate moments in QAD,
         and only the PO number means there's nothing left to check. --}}
    @unless ($partOrder->qad_po_no)
    <form method="POST" action="{{ route('warehouse.orders.check-po', $partOrder) }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-blue-600 hover:text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition">
            ↻ Cek PO
        </button>
    </form>
    @endunless
</div>
@endif

{{-- Isi PO langsung dari QAD (SDI_getActivePO2, dikunci ke No. PO) — baris
     yang QAD anggap sudah selesai diterima penuh tidak lagi muncul di sini,
     jadi ini gambaran "apa yang masih terbuka" bukan riwayat lengkap. --}}
@if ($partOrder->qad_po_no)
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold text-slate-800 mb-1">Isi PO {{ $partOrder->qad_po_no }} (langsung dari QAD)</h3>
    @if (empty($poLines))
    <p class="text-sm text-slate-500">
        Tidak ada baris terbuka ditemukan untuk PO ini di QAD — kemungkinan semua baris sudah diterima penuh,
        atau datanya belum ter-sync.
    </p>
    @else
    <p class="text-xs text-slate-500 mb-3">Hanya baris yang masih terbuka di QAD yang muncul di sini.</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                    <th class="py-1.5 pr-3">Baris</th>
                    <th class="py-1.5 pr-3">Part</th>
                    <th class="py-1.5 pr-3">Qty Order</th>
                    <th class="py-1.5 pr-3">Qty Diterima</th>
                    <th class="py-1.5 pr-3">UM</th>
                    <th class="py-1.5 pr-3">Jatuh Tempo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach ($poLines as $lineNo => $l)
                <tr>
                    <td class="py-1.5 pr-3 text-slate-500">{{ $lineNo }}</td>
                    <td class="py-1.5 pr-3 text-slate-800">{{ $l['part'] }}</td>
                    <td class="py-1.5 pr-3 text-slate-800">{{ rtrim(rtrim(number_format($l['qty_ord'], 2), '0'), '.') }}</td>
                    <td class="py-1.5 pr-3 text-slate-800">{{ rtrim(rtrim(number_format($l['qty_rcvd'], 2), '0'), '.') }}</td>
                    <td class="py-1.5 pr-3 text-slate-500">{{ $l['um'] }}</td>
                    <td class="py-1.5 pr-3 text-slate-500">{{ $l['due_date'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

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

{{-- Item master catalog --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div>
            <h3 class="font-semibold text-slate-800">Pilih dari Master Data Item</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ number_format($itemMasterCount) }} item aktif dari master QAD — dipakai untuk baris PR.
            </p>
        </div>
        <a href="{{ route('items.index') }}"
            class="text-sm text-blue-600 hover:text-blue-700 font-medium shrink-0">
            Buka Master Data Item →
        </a>
    </div>

    @if ($itemMasterCount === 0)
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
        Master Data Item masih kosong. Buka
        <a href="{{ route('items.index') }}" class="font-semibold underline">Master Data Item</a>
        lalu klik <strong>Sync Sekarang</strong> agar katalog bisa dipilih di sini.
    </div>
    @else
    <form method="GET" action="{{ route('warehouse.orders.show', $partOrder) }}" class="flex gap-2 mb-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama / part number…"
            class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Cari
        </button>
        @if (request('q'))
        <a href="{{ route('warehouse.orders.show', $partOrder) }}"
            class="text-sm text-slate-500 hover:text-blue-600 px-3 py-2">Reset</a>
        @endif
    </form>

    @forelse ($results as $item)
    <form method="POST" action="{{ route('warehouse.orders.add-line', $partOrder) }}"
        class="flex flex-wrap items-end gap-2 border border-slate-200 rounded-lg px-3 py-2 mb-2">
        @csrf
        <input type="hidden" name="mode" value="catalog">
        <input type="hidden" name="qad_item_id" value="{{ $item->id }}">
        <div class="flex-1 min-w-[200px]">
            <div class="text-sm font-medium text-slate-800">{{ $item->description ?: $item->qad_code }}</div>
            <div class="text-xs text-slate-500 font-mono">
                {{ $item->qad_code }}
                @if ($item->part_number) · {{ $item->part_number }} @endif
            </div>
        </div>
        <input type="number" name="quantity" required step="1" min="1" value="1" placeholder="Qty"
            class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="text" name="uom" required placeholder="UOM" value="EA"
            class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
            + Tambah
        </button>
    </form>
    @empty
    <p class="text-sm text-slate-400">
        @if (request('q'))
            Tidak ada item master ditemukan untuk "{{ request('q') }}".
        @else
            Tidak ada item aktif di master data.
        @endif
    </p>
    @endforelse
    @endif
</div>

{{-- Manual add --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold text-slate-800 mb-1">Item Tidak Ditemukan? Tambah Manual</h3>
    <p class="text-xs text-slate-500 mb-3">Untuk item yang belum ada di data master QAD. Item number di QAD akan diisi nama item (tidak boleh kosong).</p>
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
            <input type="date" name="need_date" required value="{{ old('need_date', optional($partOrder->need_date)->toDateString() ?? now()->toDateString()) }}"
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
@if ($partOrder->status === 'pr_created' && ! $partOrder->qad_po_no)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
    <h3 class="font-semibold text-amber-800 mb-1">Menunggu PO dari QAD</h3>
    <p class="text-sm text-amber-700">
        Penerimaan barang baru bisa dicatat setelah PR ini disetujui dan QAD menerbitkan No. PO —
        cek statusnya di kartu "Status QAD" di atas.
    </p>
</div>
@elseif ($partOrder->status === 'pr_created' && ! $canReceive)
<div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
    <h3 class="font-semibold text-slate-800 mb-1">Menunggu Barang Diterima</h3>
    <p class="text-sm text-slate-600">
        PO {{ $partOrder->qad_po_no }} sudah terbit di QAD, tapi kamu belum terdaftar sebagai penerima barang
        (belum ada login QAD sendiri) — hanya bisa memantau statusnya di sini.
        Hubungi admin kalau kamu memang bertugas menerima barang untuk order ini.
    </p>
</div>
@elseif ($partOrder->status === 'pr_created')
<div class="bg-white rounded-xl shadow-sm p-5">
    <h3 class="font-semibold text-slate-800 mb-1">Konfirmasi Barang Diterima</h3>
    <p class="text-xs text-slate-500 mb-4">
        Isi jumlah yang benar-benar datang secara fisik per item — boleh sebagian dulu, sisanya bisa
        menyusul. Tercatat langsung ke PO {{ $partOrder->qad_po_no }} di QAD.
        @if ($partOrder->expected_arrival)
        Estimasi tiba {{ $partOrder->expected_arrival->format('d M Y') }}.
        @endif
    </p>

    <form method="POST" action="{{ route('warehouse.receive', $partOrder) }}" class="space-y-4">
        @csrf
        <div class="divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
            @foreach ($partOrder->lines as $line)
            @php
                $already = $receivedByLineId[$line->id] ?? 0.0;
                $remaining = max(0, $line->quantity - $already);
            @endphp
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 {{ $remaining <= 0 ? 'bg-green-50/50' : '' }}">
                <div class="flex-1 min-w-[180px]">
                    <div class="text-sm font-medium text-slate-800">{{ $line->description }}</div>
                    <div class="text-xs text-slate-500">
                        Dipesan {{ $line->quantity }} {{ $line->uom }}
                        @if ($already > 0) · sudah diterima {{ $already }} {{ $line->uom }} @endif
                    </div>
                </div>
                <input type="hidden" name="items[{{ $loop->index }}][line_id]" value="{{ $line->id }}">
                @if ($remaining <= 0)
                <span class="text-xs font-medium text-green-700 shrink-0">✓ Lengkap</span>
                @else
                <div class="shrink-0">
                    <label class="block text-[11px] text-slate-400 mb-0.5">Diterima sekarang</label>
                    <input type="number" name="items[{{ $loop->index }}][qty_received]" step="1" min="0" max="{{ $remaining }}" value="{{ $remaining }}"
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

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            ✓ Catat Penerimaan
        </button>
    </form>
</div>
@elseif ($partOrder->status === 'received')
<div class="bg-green-50 border border-green-200 rounded-xl p-5">
    <h3 class="font-semibold text-green-800 mb-1">Barang Sudah Diterima</h3>
    <p class="text-sm text-green-700">
        Diterima {{ $partOrder->received_at?->format('d M Y, H:i') }} — PO {{ $partOrder->qad_po_no }}.
        WO ini otomatis lanjut ke tahap berikutnya.
    </p>
</div>
@endif

@endsection
