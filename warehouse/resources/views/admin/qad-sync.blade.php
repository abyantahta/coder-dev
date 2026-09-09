@extends('layouts.app')
@section('title', 'Sync QAD')
@section('page-title', 'Sinkronisasi QAD')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengaturan</li>
    <li class="breadcrumb-item active">Sync QAD</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Sync Data dari QAD (QXtend)</div>
            <div class="card-body">
                <p class="text-muted" style="font-size:13px;">
                    Sync akan mengambil data item master dan stok terbaru dari QAD via QXtend REST API.
                </p>
                <div class="alert alert-info d-flex gap-2 align-items-center mb-3">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        Pastikan konfigurasi QXtend di <code>.env</code> sudah benar sebelum melakukan sync.
                    </div>
                </div>
                <form action="{{ route('master.items.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Mulai sync item dari QAD? Proses bisa memakan waktu.')">
                        <i class="bi bi-cloud-download me-2"></i>Sync Item Master & Stok
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-gear me-2 text-primary"></i>Konfigurasi QXtend</div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Base URL</td><td>{{ config('services.qxtend.base_url') ?: '<span class="text-danger">Belum dikonfigurasi</span>' }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Username</td><td>{{ config('services.qxtend.username') ?: '<span class="text-muted">-</span>' }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Domain</td><td>{{ config('services.qxtend.domain') }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Timeout</td><td>{{ config('services.qxtend.timeout') }}s</td></tr>
                </table>
                <p class="text-muted mb-0" style="font-size:12px;">
                    Edit konfigurasi di file <code>.env</code> (QXTEND_BASE_URL, QXTEND_USERNAME, dll.)
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
