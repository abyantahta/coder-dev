@extends('layouts.app')
@section('title','Budget Kebutuhan GA')
@section('page-title','Budget per Item / Departemen — ' . now()->translatedFormat('F Y'))
@section('breadcrumb')
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Budget</li>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Set Budget Default Baru</div>
    <div class="card-body">
        <form action="{{ route('ga.budget.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Departemen</label>
                <select name="department_id" class="form-select form-select-sm select2-dept" required>
                    <option value="">-- Pilih --</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Item</label>
                <select name="procurement_item_id" class="form-select form-select-sm select2-item" required>
                    <option value="">-- Pilih --</option>
                    @foreach($items as $i)
                    <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->item_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Budget Default / Bulan</label>
                <input type="number" step="0.01" min="0" name="default_budget" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-cash-coin me-2 text-primary"></i>Budget vs Actual Bulan Ini</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Departemen</th><th>Item</th><th>Default</th><th>Top-up Bulan Ini</th>
                        <th>Effective Budget</th><th>Actual</th><th>Sisa</th><th width="200"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $b)
                    <tr class="{{ $b->remaining < 0 ? 'table-danger' : '' }}">
                        <td>{{ $b->department->name }}</td>
                        <td>{{ $b->procurementItem->name }} <span class="text-muted">({{ $b->procurementItem->item_code }})</span></td>
                        <td>Rp {{ number_format($b->default_budget, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($b->effective - $b->default_budget, 0, ',', '.') }}</td>
                        <td class="fw-semibold">Rp {{ number_format($b->effective, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($b->consumed, 0, ',', '.') }}</td>
                        <td class="fw-bold {{ $b->remaining < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($b->remaining, 0, ',', '.') }}
                            @if($b->remaining < 0)<span class="badge bg-danger ms-1">Over Budget</span>@endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#topup-{{ $b->id }}">
                                <i class="bi bi-plus-lg"></i> Top-up
                            </button>
                        </td>
                    </tr>
                    <tr class="collapse" id="topup-{{ $b->id }}">
                        <td colspan="8" class="bg-light">
                            <form action="{{ route('ga.budget.topup', $b->id) }}" method="POST" class="row g-2 align-items-end py-2">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label" style="font-size:11px;">Jumlah Top-up</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" style="font-size:11px;">Catatan</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Alasan penambahan budget...">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success btn-sm w-100">Simpan</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada budget yang diset</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.select2-dept, .select2-item').select2({ theme: 'bootstrap-5' });
</script>
@endpush
