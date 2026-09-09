@extends('layouts.app')
@section('title', isset($department->id) ? 'Edit Departemen' : 'Tambah Departemen')
@section('page-title', isset($department->id) ? 'Edit Departemen' : 'Tambah Departemen')
@section('breadcrumb')
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item"><a href="{{ route('master.departments.index') }}" class="text-decoration-none">Departemen</a></li>
    <li class="breadcrumb-item active">{{ isset($department->id) ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-building me-2 text-primary"></i>
                {{ isset($department->id) ? 'Edit' : 'Tambah' }} Departemen
            </div>
            <div class="card-body">
                <form action="{{ isset($department->id) ? route('master.departments.update', $department->id) : route('master.departments.store') }}"
                      method="POST">
                    @csrf
                    @if(isset($department->id)) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Departemen <span class="text-danger">*</span></label>
                        <input type="text" name="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code', $department->code) }}"
                               placeholder="contoh: PROD, MTC, WH"
                               style="text-transform:uppercase;"
                               maxlength="20">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Kode singkat departemen (maks. 20 karakter)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $department->name) }}"
                               placeholder="contoh: Produksi, Maintenance">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                   value="1" {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isActive">Aktif</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan
                        </button>
                        <a href="{{ route('master.departments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
