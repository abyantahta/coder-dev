@extends('layouts.auth')
@section('title', 'Scan QR Code')

@push('styles')
<style>
    #reader { border-radius: 12px; overflow: hidden; }
    #reader video { border-radius: 12px; }
    #reader__scan_region { border-radius: 12px; }
    .qr-result { display: none; }
    .manual-input { display: none; }
</style>
@endpush

@section('content')
<div class="auth-card">
    <a href="{{ route('login') }}" class="btn btn-sm btn-light mb-3">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>

    <div class="auth-logo">
        <i class="bi bi-qr-code-scan"></i>
    </div>
    <h5 class="text-center fw-bold mb-1">Scan QR Code</h5>
    <p class="text-center text-muted mb-4" style="font-size:13px;">
        Arahkan kamera ke QR Code pada ID Card Anda
    </p>

    <div id="scanArea">
        <div id="reader" style="width:100%;"></div>
        <div class="text-center mt-2">
            <button class="btn btn-sm btn-link text-muted" onclick="showManualInput()">
                <i class="bi bi-keyboard me-1"></i>Input Manual
            </button>
        </div>
    </div>

    <div class="manual-input" id="manualInputArea">
        <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px;">QR Code / NPK</label>
            <input type="text" id="manualQrInput" class="form-control text-center"
                   placeholder="Masukkan kode QR atau NPK" autofocus>
        </div>
        <button class="btn btn-primary w-100 mb-2" onclick="processQrCode(document.getElementById('manualQrInput').value)">
            <i class="bi bi-check-circle me-2"></i>Verifikasi
        </button>
        <button class="btn btn-link w-100 text-muted" onclick="showScanner()">
            <i class="bi bi-camera me-1"></i>Kembali ke Scanner
        </button>
    </div>

    <div class="qr-result text-center p-3" id="qrResult">
        <div class="mb-3">
            <img id="userPhoto" src="" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;border:3px solid #4680ff;">
        </div>
        <h6 class="fw-bold mb-1" id="userName"></h6>
        <div class="text-muted mb-1" style="font-size:12px;" id="userNpk"></div>
        <div class="badge bg-primary" id="userDept"></div>
        <div class="mt-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span class="text-muted" style="font-size:13px;">Mengalihkan...</span>
        </div>
    </div>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:12px;">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;

function initScanner() {
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => {
            html5QrCode.stop();
            processQrCode(decodedText);
        },
        (err) => {}
    ).catch(err => {
        showManualInput();
    });
}

function processQrCode(code) {
    if (!code.trim()) return;

    fetch('{{ route("auth.scan-qr.post") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ qr_code: code.trim() })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('scanArea').style.display = 'none';
            document.getElementById('manualInputArea').style.display = 'none';
            document.getElementById('qrResult').style.display = 'block';
            document.getElementById('userName').textContent = data.user.name;
            document.getElementById('userNpk').textContent = 'NPK: ' + data.user.npk;
            document.getElementById('userDept').textContent = data.user.department || '-';
            document.getElementById('userPhoto').src = data.user.photo;
            setTimeout(() => { window.location.href = data.redirect; }, 1500);
        } else {
            Swal.fire({ icon: 'error', title: 'Akses Ditolak', text: data.message, confirmButtonColor: '#4680ff' })
                .then(() => { if (html5QrCode) initScanner(); });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan. Coba lagi.', confirmButtonColor: '#4680ff' });
    });
}

function showManualInput() {
    if (html5QrCode) html5QrCode.stop().catch(() => {});
    document.getElementById('scanArea').style.display = 'none';
    document.getElementById('manualInputArea').style.display = 'block';
    document.getElementById('manualQrInput').focus();
}

function showScanner() {
    document.getElementById('scanArea').style.display = 'block';
    document.getElementById('manualInputArea').style.display = 'none';
    initScanner();
}

document.addEventListener('DOMContentLoaded', () => { initScanner(); });

document.getElementById('manualQrInput')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') processQrCode(e.target.value);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@endsection
