@extends('layouts.app')
@section('title', 'Item Memo')
@section('page-title', 'Transaksi Item Memo')
@section('breadcrumb')
    <li class="breadcrumb-item">Transaksi</li>
    <li class="breadcrumb-item active">Item Memo</li>
@endsection
@section('page-actions')
    <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#memoModal" onclick="setMemoType('memo_in')">
        <i class="bi bi-journal-plus me-1"></i>Memo Masuk
    </button>
    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#memoModal" onclick="setMemoType('memo_out')">
        <i class="bi bi-journal-minus me-1"></i>Memo Keluar
    </button>
@endsection

@section('content')
<div class="row g-3 mb-4">
    {{-- Memo Stock Cards --}}
    @foreach($memoItems as $item)
    <div class="col-md-4 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 align-items-start">
                    <div class="avatar-sm bg-info bg-opacity-10 d-flex align-items-center justify-content-center text-info flex-shrink-0">
                        <i class="bi bi-journal"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate" style="font-size:13px;">{{ $item->description }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $item->item_code }}</div>
                        <div class="mt-1">
                            <span class="fw-bold" style="font-size:18px;">{{ number_format($item->memoStock?->qty_on_hand ?? 0, 0) }}</span>
                            <span class="text-muted" style="font-size:12px;"> {{ $item->unit }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-journal-text me-2 text-primary"></i>Riwayat Transaksi Memo</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="memoTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Item</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td>
                            @if($trx->trans_type === 'memo_in')
                                <span class="badge bg-success"><i class="bi bi-plus-lg me-1"></i>Masuk</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-dash-lg me-1"></i>Keluar</span>
                            @endif
                        </td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td>{{ $trx->user->name }}</td>
                        <td>
                            @foreach($trx->details as $d)
                            <div style="font-size:12px;">{{ $d->item->description }}: <strong>{{ number_format($d->qty_approved, 0) }} {{ $d->item->unit }}</strong></div>
                            @endforeach
                        </td>
                        <td class="text-muted" style="font-size:12px;">{{ $trx->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi memo</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>

{{-- Memo Transaction Modal --}}
<div class="modal fade" id="memoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="memoModalTitle">Transaksi Memo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="memoType">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Transaksi</label>
                    <input type="text" id="memoNotes" class="form-control" placeholder="Catatan (opsional)">
                </div>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr><th>Item Memo</th><th width="130">Qty</th><th>Catatan</th></tr>
                    </thead>
                    <tbody>
                        @foreach($memoItems as $item)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->description }}</div>
                                <div class="text-muted" style="font-size:11px;">
                                    Stok: <strong>{{ number_format($item->memoStock?->qty_on_hand ?? 0, 0) }} {{ $item->unit }}</strong>
                                </div>
                                <input type="hidden" class="memo-item-id" value="{{ $item->id }}">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm memo-qty"
                                       min="0" step="0.01" value="0" placeholder="0">
                                <small class="text-muted">{{ $item->unit }}</small>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm memo-note" placeholder="Catatan">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitMemo()">
                    <i class="bi bi-save me-1"></i>Simpan Transaksi
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($transactions->count())
$('#memoTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' }
});
@endif

function setMemoType(type) {
    document.getElementById('memoType').value = type;
    document.getElementById('memoModalTitle').textContent = type === 'memo_in' ? 'Memo Masuk' : 'Memo Keluar';
    document.querySelectorAll('.memo-qty').forEach(q => q.value = 0);
}

async function submitMemo() {
    const type  = document.getElementById('memoType').value;
    const notes = document.getElementById('memoNotes').value;
    const items = [];

    document.querySelectorAll('tbody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.memo-qty')?.value || 0);
        if (qty > 0) {
            items.push({
                item_id: row.querySelector('.memo-item-id').value,
                qty:     qty,
                notes:   row.querySelector('.memo-note').value,
            });
        }
    });

    if (!items.length) {
        Swal.fire({ icon: 'warning', title: 'Input Qty', text: 'Isi qty minimal 1 item.' }); return;
    }

    const res = await fetch('{{ route("transactions.memo.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ trans_type: type, notes, items })
    });
    const data = await res.json();

    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, confirmButtonColor: '#4680ff' })
            .then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
    }
}
</script>
@endpush
