@extends('layouts.app')
@section('title', 'Edit Item')
@section('page-title', 'Edit Item')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('master.items.index') }}" class="text-decoration-none">Item</a></li>
    <li class="breadcrumb-item active">Edit: {{ $item->item_code }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Edit Item: {{ $item->item_code }}</div>
            <div class="card-body">
                <form action="{{ route('master.items.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Item</label>
                        <input type="text" class="form-control" value="{{ $item->item_code }}" readonly>
                        <div class="form-text text-muted">Kode item berasal dari QAD, tidak bisa diubah</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description', $item->description) }}">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Satuan</label>
                            <input type="text" name="unit" class="form-control" value="{{ old('unit', $item->unit) }}" placeholder="PCS, KG, LTR...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category', $item->category) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Harga Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-semibold" style="font-size:12px;">Rp</span>
                                <input type="number" name="price" class="form-control"
                                       value="{{ old('price', $item->price) }}"
                                       placeholder="0" min="0" step="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mata Uang</label>
                            <select name="currency" class="form-select">
                                <option value="IDR" {{ old('currency', $item->currency) === 'IDR' ? 'selected' : '' }}>IDR</option>
                                <option value="USD" {{ old('currency', $item->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Foto Item</label>
                        @if($item->photo)
                        <div class="mb-2">
                            <img src="{{ asset('storage/'.$item->photo) }}" class="img-thumbnail" style="height:100px;object-fit:cover;">
                        </div>
                        @endif
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_memo" id="isMemo"
                                       {{ old('is_memo', $item->is_memo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isMemo">
                                    Item Memo <small class="text-muted fw-normal">(diluar inventory QAD)</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActive">Item Aktif</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                        <a href="{{ route('master.items.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
