@extends('layouts.app')
@section('title','Serah Terima Barang')
@section('page-title', 'Serah Terima: ' . $employee->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ga.checkout.index') }}" class="text-decoration-none">Serah Terima Barang</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body d-flex align-items-center gap-3">
        <img src="{{ $employee->photo ? asset('storage/'.$employee->photo) : asset('assets/images/user/avatar-1.jpg') }}"
             class="rounded-circle" style="width:56px;height:56px;object-fit:cover;">
        <div>
            <div class="fw-bold">{{ $employee->name }}</div>
            <div class="text-muted" style="font-size:12px;">{{ $employee->npk }} — {{ $employee->department?->name }}</div>
        </div>
    </div>
</div>

<form action="{{ route('ga.checkout.store', $employee->id) }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Item yang Bisa Diserahkan</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th>Sisa Permintaan</th><th>Tersedia di Dept</th><th width="150">Qty Diserahkan</th></tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $i => $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row->detail->item->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $row->detail->item->item_code }}</div>
                                <input type="hidden" name="items[{{ $i }}][procurement_request_detail_id]" value="{{ $row->detail->id }}">
                            </td>
                            <td>{{ number_format($row->remaining, 2) }}</td>
                            <td>{{ number_format($row->available, 2) }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="{{ $row->available }}"
                                       name="items[{{ $i }}][qty]" class="form-control form-control-sm"
                                       value="{{ $row->available }}">
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada barang yang bisa diserahkan untuk karyawan ini saat ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($lines->count())
        <div class="card-footer bg-transparent d-flex gap-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi serah terima barang ke {{ $employee->name }}?')">
                <i class="bi bi-check-lg me-1"></i>Konfirmasi Serah Terima
            </button>
            <a href="{{ route('ga.checkout.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
        @endif
    </div>
</form>
@endsection
