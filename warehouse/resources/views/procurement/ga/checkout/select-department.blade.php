@extends('layouts.app')
@section('title','Serah Terima Barang')
@section('page-title','Serah Terima Barang — Pilih Departemen')
@section('breadcrumb')
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Serah Terima Barang</li>
@endsection

@section('content')
<div class="row g-3">
    @forelse($departments as $dept)
    <div class="col-md-4 col-lg-3">
        <a href="{{ route('ga.checkout.scan', ['department_id' => $dept->id]) }}" class="text-decoration-none">
            <div class="card h-100 text-center py-4">
                <i class="bi bi-building fs-1 text-primary mb-2"></i>
                <div class="fw-semibold text-dark">{{ $dept->name }}</div>
                <div class="text-muted" style="font-size:12px;">
                    {{ $dept->items_available }} item tersedia untuk diambil
                </div>
            </div>
        </a>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-5">Belum ada departemen aktif.</div>
    @endforelse
</div>
@endsection
