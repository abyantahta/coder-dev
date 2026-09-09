@extends('layouts.app')
@section('title','Konfigurasi QAD per Departemen')
@section('page-title','Konfigurasi QAD per Departemen')
@section('breadcrumb')
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Konfigurasi QAD</li>
@endsection

@section('content')
<div class="alert alert-info" style="font-size:13px;">
    <i class="bi bi-info-circle me-1"></i>
    Field ini dipetakan ke header requisition QAD (<code>rqmShip/rqmSite</code>, <code>routeToBuyer</code>, <code>routeToApr</code>, <code>rqmEndUserid</code>, <code>rqmRqbyUserid</code>).
    Kalau <strong>Site Code</strong> kosong untuk suatu departemen, pengiriman PR ke QAD dari departemen itu akan gagal.
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Departemen</th>
                        <th width="130">Site Code *</th>
                        <th width="130">Buyer Code</th>
                        <th width="130">Approver Code</th>
                        <th width="130">End User ID</th>
                        <th width="150">Requester Userid</th>
                        <th width="90"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $dept)
                    @php $fid = 'qadform-' . $dept->id; @endphp
                    <tr>
                        <td class="align-middle fw-semibold">{{ $dept->name }} <span class="text-muted">({{ $dept->code }})</span></td>
                        <td><input type="text" form="{{ $fid }}" name="site_code" class="form-control form-control-sm" value="{{ old('site_code', $dept->qadConfig?->site_code) }}" required></td>
                        <td><input type="text" form="{{ $fid }}" name="buyer_code" class="form-control form-control-sm" value="{{ old('buyer_code', $dept->qadConfig?->buyer_code) }}"></td>
                        <td><input type="text" form="{{ $fid }}" name="approver_code" class="form-control form-control-sm" value="{{ old('approver_code', $dept->qadConfig?->approver_code) }}"></td>
                        <td><input type="text" form="{{ $fid }}" name="end_user_id" class="form-control form-control-sm" value="{{ old('end_user_id', $dept->qadConfig?->end_user_id) }}"></td>
                        <td><input type="text" form="{{ $fid }}" name="requester_userid" class="form-control form-control-sm" value="{{ old('requester_userid', $dept->qadConfig?->requester_userid) }}"></td>
                        <td>
                            <button type="submit" form="{{ $fid }}" class="btn btn-sm btn-primary w-100"><i class="bi bi-save"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($departments as $dept)
<form id="qadform-{{ $dept->id }}" action="{{ route('admin.qad-config.update', $dept->id) }}" method="POST" class="d-none">
    @csrf @method('PUT')
</form>
@endforeach
@endsection
