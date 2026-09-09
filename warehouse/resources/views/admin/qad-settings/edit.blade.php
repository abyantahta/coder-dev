@extends('layouts.app')
@section('title','Setting URL QAD')
@section('page-title','Setting URL QAD')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengaturan</li>
    <li class="breadcrumb-item active">URL QAD</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-hdd-network me-2 text-primary"></i>URL Koneksi QAD</div>
            <div class="card-body">
                <form action="{{ route('admin.qad-settings.update') }}" method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL QXI (QdocWebService)</label>
                        <input type="text" name="qad_soap_url" class="form-control @error('qad_soap_url') is-invalid @enderror"
                               value="{{ old('qad_soap_url', $setting->qad_soap_url) }}"
                               placeholder="{{ config('services.qad_soap.url') }}">
                        @error('qad_soap_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" style="font-size:11px;">
                            Dipakai untuk kirim PR, approval, dan penerimaan PO ke QAD.
                            Kosongkan untuk pakai default dari server: <code>{{ config('services.qad_soap.url') }}</code>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL WSA (Web Service Adapter)</label>
                        <input type="text" name="qad_wsa_url" class="form-control @error('qad_wsa_url') is-invalid @enderror"
                               value="{{ old('qad_wsa_url', $setting->qad_wsa_url) }}"
                               placeholder="{{ config('services.qad_soap.wsa_url') }}">
                        @error('qad_wsa_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" style="font-size:11px;">
                            Dipakai untuk auto-detect No. PO dari requisition (SDI_getPRtoPO_).
                            Kosongkan untuk pakai default dari server: <code>{{ config('services.qad_soap.wsa_url') }}</code>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="alert alert-warning mb-0" style="font-size:13px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Ganti URL ini kalau host/port server QAD berubah (misal port <code>24079</code>).
            Username/password QAD tetap dikelola lewat konfigurasi server (<code>.env</code>), tidak lewat sini.
        </div>
    </div>
</div>
@endsection
