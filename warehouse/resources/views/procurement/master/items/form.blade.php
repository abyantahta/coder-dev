@extends('layouts.app')
@section('title', isset($item->id) ? 'Edit Item ATK' : 'Tambah Item ATK')
@section('page-title', isset($item->id) ? 'Edit Item ATK' : 'Tambah Item ATK')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('procurement.items.index') }}" class="text-decoration-none">Item ATK</a></li>
    <li class="breadcrumb-item active">{{ isset($item->id) ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>{{ isset($item->id) ? 'Edit' : 'Tambah' }} Item ATK</div>
            <div class="card-body">
                <form action="{{ isset($item->id) ? route('procurement.items.update', $item->id) : route('procurement.items.store') }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(isset($item->id)) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Kode Item</label>
                            @if(isset($item->id))
                                <input type="text" class="form-control bg-light" value="{{ $item->item_code }}" readonly>
                                <div class="form-text" style="font-size:11px;">Kode item tidak bisa diubah.</div>
                            @else
                                <div class="input-group">
                                    <select name="prefix" id="prefixSelect" class="form-select @error('prefix') is-invalid @enderror" style="max-width:110px;" onchange="updateCodePreview()">
                                        @forelse($prefixes as $p)
                                        <option value="{{ $p->code }}" {{ old('prefix') === $p->code ? 'selected' : '' }}>{{ $p->code }}</option>
                                        @empty
                                        <option value="">-- Belum ada --</option>
                                        @endforelse
                                    </select>
                                    <input type="text" class="form-control bg-light" id="codePreview" value="{{ $nextItemCode ?? '-' }}" readonly>
                                </div>
                                @error('prefix')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text" style="font-size:11px;">Pilih prefix — nomor urut otomatis lanjut sesuai prefix. Kelola daftar prefix di <a href="{{ route('procurement.item-prefixes.index') }}" target="_blank">Master Prefix Kode</a>.</div>
                            @endif
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Nama Item <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $item->name) }}" placeholder="Nama item ATK">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">UOM <span class="text-danger">*</span></label>
                            <select name="uom_id" class="form-select @error('uom_id') is-invalid @enderror">
                                <option value="">-- Pilih Satuan --</option>
                                @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}" {{ old('uom_id', $item->uom_id) == $uom->id ? 'selected' : '' }}>
                                    {{ $uom->code }} — {{ $uom->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('uom_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">-- Tanpa Kategori --</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">Kelola daftar kategori di <a href="{{ route('procurement.categories.index') }}">Master Kategori Item</a>.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Harga per Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price" min="0" step="1"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $item->price) }}" placeholder="0">
                            </div>
                            @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">Estimasi harga — dipakai buat hitung budget vs actual.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Spesifikasi atau keterangan tambahan">{{ old('description', $item->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Foto Item</label>
                            @if(isset($item->id) && $item->photo)
                            <div class="mb-2"><img src="{{ asset('storage/'.$item->photo) }}" class="img-thumbnail" style="height:70px;object-fit:cover;"></div>
                            @endif
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        @if(isset($item->id))
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActive">Item Aktif</label>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                        <a href="{{ route('procurement.items.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@if(!isset($item->id))
@push('scripts')
<script>
function updateCodePreview() {
    const prefix = document.getElementById('prefixSelect').value;
    const preview = document.getElementById('codePreview');
    if (!prefix) { preview.value = '-'; return; }

    fetch('{{ route("procurement.item-prefixes.next-code") }}?prefix=' + encodeURIComponent(prefix))
        .then(r => r.json())
        .then(data => { preview.value = data.code; })
        .catch(() => { preview.value = '-'; });
}
</script>
@endpush
@endif
