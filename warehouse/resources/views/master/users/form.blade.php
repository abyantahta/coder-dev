@extends('layouts.app')
@section('title', isset($user->id) ? 'Edit User' : 'Tambah User')
@section('page-title', isset($user->id) ? 'Edit User' : 'Tambah User')
@section('breadcrumb')
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item"><a href="{{ route('master.users.index') }}" class="text-decoration-none">User</a></li>
    <li class="breadcrumb-item active">{{ isset($user->id) ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person me-2 text-primary"></i>
                {{ isset($user->id) ? 'Edit User: ' . $user->name : 'Tambah User Baru' }}
            </div>
            <div class="card-body">
                <form action="{{ isset($user->id) ? route('master.users.update', $user->id) : route('master.users.store') }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(isset($user->id)) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NPK <span class="text-danger">*</span></label>
                            <input type="text" name="npk"
                                   class="form-control @error('npk') is-invalid @enderror"
                                   value="{{ old('npk', $user->npk) }}"
                                   placeholder="contoh: EMP001">
                            @error('npk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   placeholder="Nama lengkap">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   placeholder="email@company.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. WhatsApp</label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="62812xxxxxxx (untuk notifikasi WA)">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Departemen</label>
                            <select name="department_id" class="form-select select2">
                                <option value="">-- Pilih Departemen --</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jabatan <span class="text-danger">*</span></label>
                            <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" onchange="toggleQadApproverField(this.value)">
                                <option value="user"      {{ old('role', $user->role) === 'user'      ? 'selected' : '' }}>User</option>
                                <option value="section"   {{ old('role', $user->role) === 'section'   ? 'selected' : '' }}>Section / Kasi</option>
                                <option value="manager"   {{ old('role', $user->role) === 'manager'   ? 'selected' : '' }}>Manager</option>
                                <option value="director"  {{ old('role', $user->role) === 'director'  ? 'selected' : '' }}>Direktur</option>
                                <option value="purchasing" {{ old('role', $user->role) === 'purchasing' ? 'selected' : '' }}>Purchasing</option>
                                <option value="ga"        {{ old('role', $user->role) === 'ga'        ? 'selected' : '' }}>General Affair</option>
                                <option value="admin"     {{ old('role', $user->role) === 'admin'     ? 'selected' : '' }}>Admin Warehouse</option>
                                @if(auth()->user()->isSuperAdmin())
                                <option value="superadmin" {{ old('role', $user->role) === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                @endif
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 {{ old('role', $user->role) === 'section' ? '' : 'd-none' }}" id="approverDeptField">
                            <label class="form-label fw-semibold">Departemen Approval Tambahan</label>
                            <select name="approver_department_ids[]" class="form-select select2" multiple>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ in_array($dept->id, old('approver_department_ids', $approverDepartmentIds)) ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text" style="font-size:11px;">Selain departemen utama di atas, user ini juga bisa approve request dari departemen yang dipilih di sini (kasus kasi rangkap jabatan).</div>
                        </div>

                        <div class="col-md-6 {{ old('role', $user->role) === 'director' ? '' : 'd-none' }}" id="qadApproverField">
                            <label class="form-label fw-semibold">Kode Approver QAD</label>
                            <input type="text" name="qad_approver_code"
                                   class="form-control @error('qad_approver_code') is-invalid @enderror"
                                   value="{{ old('qad_approver_code', $user->qad_approver_code) }}"
                                   placeholder="contoh: agung">
                            @error('qad_approver_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">User id QAD dipakai saat approve/deny PR ke QAD (khusus Direktur).</div>
                        </div>

                        <div class="col-md-6 {{ old('role', $user->role) === 'director' ? '' : 'd-none' }}" id="qadPasswordField">
                            <label class="form-label fw-semibold">Password QAD Direktur</label>
                            <input type="password" name="qad_password"
                                   class="form-control @error('qad_password') is-invalid @enderror"
                                   placeholder="{{ isset($user->id) && $user->qad_password ? 'kosongkan jika tidak diubah' : '' }}">
                            @error('qad_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">
                                Password login QAD milik Direktur ini (disimpan terenkripsi). QAD mewajibkan approve/deny requisition pakai kredensial approver asli, bukan akun service.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Route to Approver (QAD)</label>
                            <input type="text" name="qad_route_to_apr"
                                   class="form-control @error('qad_route_to_apr') is-invalid @enderror"
                                   value="{{ old('qad_route_to_apr', $user->qad_route_to_apr) }}"
                                   placeholder="contoh: agung">
                            @error('qad_route_to_apr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">Approver tujuan saat user ini bikin PR ke QAD. Kosongkan untuk pakai default departemen.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Route to Buyer (QAD)</label>
                            <input type="text" name="qad_route_to_buyer"
                                   class="form-control @error('qad_route_to_buyer') is-invalid @enderror"
                                   value="{{ old('qad_route_to_buyer', $user->qad_route_to_buyer) }}"
                                   placeholder="contoh: iwan">
                            @error('qad_route_to_buyer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">Buyer tujuan saat user ini bikin PR ke QAD. Kosongkan untuk pakai default departemen.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Requested By (QAD User ID)</label>
                            <input type="text" name="qad_requested_by"
                                   class="form-control @error('qad_requested_by') is-invalid @enderror"
                                   value="{{ old('qad_requested_by', $user->qad_requested_by) }}"
                                   placeholder="contoh: tri">
                            @error('qad_requested_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">User id QAD yang tercatat sebagai "Requested By" saat user ini bikin PR. Kosongkan untuk pakai default departemen.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End User (QAD)</label>
                            <input type="text" name="qad_end_user_id"
                                   class="form-control @error('qad_end_user_id') is-invalid @enderror"
                                   value="{{ old('qad_end_user_id', $user->qad_end_user_id) }}"
                                   placeholder="contoh: IT">
                            @error('qad_end_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" style="font-size:11px;">Kode End User QAD saat user ini bikin PR. Kosongkan untuk pakai default departemen.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Foto</label>
                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror"
                                   accept="image/*" onchange="previewPhoto(this)">
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(isset($user->id) && $user->photo)
                            <img src="{{ asset('storage/'.$user->photo) }}" id="photoPreview"
                                 class="mt-2 rounded" style="height:60px;object-fit:cover;">
                            @else
                            <img src="" id="photoPreview" class="mt-2 rounded d-none" style="height:60px;object-fit:cover;">
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Password {{ isset($user->id) ? '(kosongkan jika tidak diubah)' : '' }}
                                @if(!isset($user->id))<span class="text-danger">*</span>@endif
                            </label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="••••••••">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation"
                                   class="form-control" placeholder="••••••••">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActive">User Aktif</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan
                        </button>
                        <a href="{{ route('master.users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.select2').select2({ theme: 'bootstrap-5' });

function toggleQadApproverField(role) {
    document.getElementById('qadApproverField').classList.toggle('d-none', role !== 'director');
    document.getElementById('qadPasswordField').classList.toggle('d-none', role !== 'director');
    document.getElementById('approverDeptField').classList.toggle('d-none', role !== 'section');
}

function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
