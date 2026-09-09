@extends('layouts.app')
@section('title','Scan Karyawan')
@section('page-title', 'Serah Terima — ' . $department->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ga.checkout.index') }}" class="text-decoration-none">Serah Terima Barang</a></li>
    <li class="breadcrumb-item active">{{ $department->name }}</li>
@endsection

@push('styles')
<style>
    #reader { border-radius: 12px; overflow: hidden; max-width: 400px; margin: 0 auto; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-3">Scan QR karyawan atau masukkan email untuk departemen <strong>{{ $department->name }}</strong></p>

                <div id="reader" class="mb-3"></div>

                <div class="input-group mb-2">
                    <input type="text" id="manualInput" class="form-control" placeholder="Email karyawan / QR code">
                    <button class="btn btn-primary" onclick="lookupEmployee(document.getElementById('manualInput').value)">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>

                <div id="lookupError" class="alert alert-danger py-2 mt-2" style="display:none;font-size:13px;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const departmentId = {{ $department->id }};
let html5QrCode;

function initScanner() {
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => { html5QrCode.stop(); lookupEmployee(decodedText); },
        (err) => {}
    ).catch(err => {});
}

function lookupEmployee(identifier) {
    if (!identifier || !identifier.trim()) return;

    fetch('{{ route("ga.checkout.lookup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ department_id: departmentId, identifier: identifier.trim() })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            const el = document.getElementById('lookupError');
            el.textContent = data.message;
            el.style.display = 'block';
            if (html5QrCode) initScanner();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => { initScanner(); });
document.getElementById('manualInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') lookupEmployee(e.target.value);
});
</script>
@endpush
