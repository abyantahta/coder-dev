@extends('layouts.app')
@section('title', 'Akses Ditolak')
@section('content')
<div class="text-center py-5">
    <div style="font-size:72px;color:#dc3545;">403</div>
    <h4>Akses Ditolak</h4>
    <p class="text-muted">Anda tidak memiliki hak akses ke halaman ini.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke Dashboard</a>
</div>
@endsection
