@extends('layouts.app')
@section('title', 'Scan & Ambil Barang')
@section('page-title', 'Scan & Ambil Barang')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transactions.goods-out.index') }}" class="text-decoration-none">Keluar Barang</a></li>
    <li class="breadcrumb-item active">Scan Barang</li>
@endsection

@push('styles')
<style>
    .scan-box { border: 2px dashed #4680ff; border-radius: 12px; padding: 20px; background: #f8f9ff; }
    .item-card { transition: all .2s; cursor: default; }
    .item-cart-row { animation: slideIn .3s ease; }
    @keyframes slideIn { from { opacity:0; transform:translateX(-10px); } to { opacity:1; transform:translateX(0); } }
    .qty-input { width: 80px; text-align:center; }
    #barcodeInput { font-size: 16px; letter-spacing: 2px; text-transform: uppercase; }
    .stock-badge { font-size: 15px; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="row g-3">
    {{-- Scanner Panel --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-upc-scan me-2 text-primary"></i>Scan Barcode Item
            </div>
            <div class="card-body">
                <div class="scan-box mb-3">
                    <label class="form-label fw-semibold mb-2">Scan / Input Barcode Item</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-upc text-primary"></i></span>
                        <input type="text" id="barcodeInput" class="form-control"
                               placeholder="Scan atau ketik kode item..."
                               autocomplete="off" autofocus>
                        <button class="btn btn-primary" onclick="lookupItem()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="text-muted mt-2" style="font-size:12px;">
                        <i class="bi bi-info-circle me-1"></i>Tekan Enter atau klik tombol untuk cari item
                    </div>
                </div>

                {{-- Camera Scanner Toggle --}}
                <button class="btn btn-outline-secondary btn-sm w-100 mb-3" id="toggleCamera" onclick="toggleCameraScanner()">
                    <i class="bi bi-camera me-1"></i>Aktifkan Kamera Scanner
                </button>
                <div id="cameraReader" class="mb-3" style="display:none;"></div>
            </div>
        </div>

        {{-- Item Info Popup --}}
        <div class="card" id="itemInfoCard" style="display:none;">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-box-seam me-2 text-primary"></i>Info Item</span>
                <button class="btn btn-sm btn-light" onclick="clearItemInfo()"><i class="bi bi-x"></i></button>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-4">
                        <img id="itemPhoto" src="" class="img-fluid rounded" style="aspect-ratio:1;object-fit:cover;">
                    </div>
                    <div class="col-8">
                        <div class="fw-bold mb-1" id="itemCode" style="font-size:13px;"></div>
                        <div class="text-muted mb-2" id="itemDesc" style="font-size:12px;"></div>
                        <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary" id="itemUnit"></span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary" id="itemCategory"></span>
                        </div>
                        <div class="p-2 rounded" id="stockInfoBox">
                            <div class="text-muted" style="font-size:11px;">STOK TERSEDIA</div>
                            <div class="stock-badge" id="stockQty"></div>
                        </div>
                    </div>
                </div>

                <div id="outOfStockAlert" class="alert alert-danger mt-2 mb-2 py-2 d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>STOK HABIS!</strong> Item ini tidak dapat diambil saat ini.
                </div>
                <div id="lowStockAlert" class="alert alert-warning mt-2 mb-2 py-2 d-none">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Stok di bawah minimum. Perlu order ke Purchasing.
                </div>

                <div id="addToCartSection" class="mt-3">
                    <div class="d-flex gap-2 align-items-center">
                        <label class="form-label fw-semibold mb-0">Qty:</label>
                        <div class="input-group" style="width:130px;">
                            <button class="btn btn-outline-secondary btn-sm" onclick="adjustQty(-1)">−</button>
                            <input type="number" id="qtyInput" class="form-control qty-input" value="1" min="1">
                            <button class="btn btn-outline-secondary btn-sm" onclick="adjustQty(1)">+</button>
                        </div>
                        <span class="text-muted" id="qtyUnit" style="font-size:13px;"></span>
                    </div>
                    <input type="text" id="itemNotes" class="form-control mt-2" placeholder="Catatan (opsional)">
                    <button class="btn btn-primary w-100 mt-2" id="addToCartBtn" onclick="addToCart()">
                        <i class="bi bi-cart-plus me-1"></i>Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cart Panel --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart3 me-2 text-primary"></i>Keranjang</span>
                <span class="badge bg-primary" id="cartCount">0 item</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                <table class="table table-hover mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                        <tr>
                            <th>Item</th>
                            <th width="100">Qty</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyCartRow">
                            <td colspan="3" class="text-center text-muted py-5">
                                <i class="bi bi-cart" style="font-size:32px;"></i>
                                <p class="mt-2 mb-0">Belum ada item. Scan barcode untuk menambahkan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer" id="cartFooter" style="display:none;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Departemen</label>
                        <select id="deptSelect" class="form-select select2-dept">
                            <option value="{{ auth()->user()->department_id }}">
                                {{ auth()->user()->department?->name ?? '-- Pilih Departemen --' }}
                            </option>
                            @foreach(\App\Models\Department::where('is_active', true)->get() as $dept)
                            <option value="{{ $dept->id }}" {{ auth()->user()->department_id == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Catatan Transaksi</label>
                        <input type="text" id="transNotes" class="form-control" placeholder="Catatan (opsional)">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success w-100" onclick="submitTransaction()">
                            <i class="bi bi-check-circle me-2"></i>Keluarkan Barang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Success Modal --}}
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <div style="width:72px;height:72px;background:#d4edda;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="bi bi-check-lg text-success" style="font-size:36px;"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Pengambilan Berhasil!</h5>
                <p class="text-muted" style="font-size:13px;">
                    Barang berhasil dikeluarkan dan stok telah dikurangi.
                </p>
                <div class="bg-light rounded p-2 mb-3">
                    <div class="text-muted" style="font-size:11px;">No. Transaksi</div>
                    <div class="fw-bold text-primary" id="successTransNo"></div>
                </div>
                <button class="btn btn-primary w-100" id="successBtn">
                    <i class="bi bi-eye me-1"></i>Lihat Detail
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let cartItems = [];
let currentItem = null;
let cameraScanner = null;

// ===== BARCODE LOOKUP =====
document.getElementById('barcodeInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') lookupItem();
});

async function lookupItem() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) return;

    try {
        const res = await fetch(`{{ route('transactions.goods-out.item-info') }}?barcode=${encodeURIComponent(barcode)}`, {
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showItemInfo(data.item);
        } else {
            Swal.fire({ icon: 'error', title: 'Item Tidak Ditemukan', text: data.message, confirmButtonColor: '#4680ff' });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mencari item. Coba lagi.' });
    }
}

function showItemInfo(item) {
    currentItem = item;
    document.getElementById('itemCode').textContent  = item.item_code;
    document.getElementById('itemDesc').textContent  = item.description;
    document.getElementById('itemUnit').textContent  = item.unit || '-';
    document.getElementById('itemCategory').textContent = item.category || '-';
    document.getElementById('itemPhoto').src         = item.photo;
    document.getElementById('qtyUnit').textContent   = item.unit || '';
    document.getElementById('qtyInput').value        = 1;
    document.getElementById('itemNotes').value       = '';

    const stockBox = document.getElementById('stockInfoBox');
    const stockQty = document.getElementById('stockQty');
    const outAlert = document.getElementById('outOfStockAlert');
    const lowAlert = document.getElementById('lowStockAlert');
    const addBtn   = document.getElementById('addToCartBtn');

    stockQty.textContent = `${item.qty_on_hand} ${item.unit || ''}`;

    if (item.is_out_of_stock) {
        stockBox.className = 'p-2 rounded bg-danger bg-opacity-10';
        stockQty.className = 'stock-badge text-danger';
        outAlert.classList.remove('d-none');
        lowAlert.classList.add('d-none');
        addBtn.disabled = true;
        addBtn.textContent = 'Stok Habis';
    } else if (item.is_below_min) {
        stockBox.className = 'p-2 rounded bg-warning bg-opacity-10';
        stockQty.className = 'stock-badge text-warning';
        outAlert.classList.add('d-none');
        lowAlert.classList.remove('d-none');
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Tambah ke Keranjang';
    } else {
        stockBox.className = 'p-2 rounded bg-success bg-opacity-10';
        stockQty.className = 'stock-badge text-success';
        outAlert.classList.add('d-none');
        lowAlert.classList.add('d-none');
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Tambah ke Keranjang';
    }

    document.getElementById('itemInfoCard').style.display = 'block';
}

function clearItemInfo() {
    currentItem = null;
    document.getElementById('itemInfoCard').style.display = 'none';
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeInput').focus();
}

// ===== CART =====
function adjustQty(delta) {
    const input = document.getElementById('qtyInput');
    const current = parseFloat(input.value) || 1;
    input.value = Math.max(1, current + delta);
}

function addToCart() {
    if (!currentItem) return;
    const qty   = parseFloat(document.getElementById('qtyInput').value) || 1;
    const notes = document.getElementById('itemNotes').value;

    const existingIdx = cartItems.findIndex(c => c.item_id === currentItem.id);
    if (existingIdx >= 0) {
        cartItems[existingIdx].qty_requested += qty;
        renderCart();
    } else {
        cartItems.push({ item_id: currentItem.id, item_code: currentItem.item_code,
            description: currentItem.description, unit: currentItem.unit,
            qty_requested: qty, notes: notes });
        renderCart();
    }
    clearItemInfo();
}

function renderCart() {
    const tbody  = document.getElementById('cartBody');
    const footer = document.getElementById('cartFooter');
    const empty  = document.getElementById('emptyCartRow');
    const count  = document.getElementById('cartCount');

    if (cartItems.length === 0) {
        tbody.innerHTML = '';
        tbody.appendChild(empty);
        footer.style.display = 'none';
        count.textContent = '0 item';
        return;
    }

    tbody.innerHTML = cartItems.map((item, idx) => `
        <tr class="item-cart-row">
            <td>
                <div class="fw-semibold">${item.description}</div>
                <div class="text-muted" style="font-size:11px;">${item.item_code}</div>
            </td>
            <td>
                <div class="input-group" style="width:110px;">
                    <button class="btn btn-outline-secondary btn-sm" onclick="changeCartQty(${idx}, -1)">−</button>
                    <input type="number" class="form-control form-control-sm text-center"
                           value="${item.qty_requested}" min="0.01" step="0.01"
                           onchange="updateCartQty(${idx}, this.value)" style="width:50px;">
                    <button class="btn btn-outline-secondary btn-sm" onclick="changeCartQty(${idx}, 1)">+</button>
                </div>
                <div class="text-muted mt-1" style="font-size:11px;">${item.unit || ''}</div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${idx})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');

    footer.style.display = 'block';
    count.textContent = cartItems.length + ' item';
}

function changeCartQty(idx, delta) {
    cartItems[idx].qty_requested = Math.max(0.01, cartItems[idx].qty_requested + delta);
    renderCart();
}
function updateCartQty(idx, val) { cartItems[idx].qty_requested = parseFloat(val) || 0.01; }
function removeFromCart(idx) { cartItems.splice(idx, 1); renderCart(); }

// ===== SUBMIT =====
async function submitTransaction() {
    if (cartItems.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Keranjang Kosong', text: 'Tambahkan item terlebih dahulu.' });
        return;
    }

    const result = await Swal.fire({
        title: 'Konfirmasi Pengambilan?',
        text: `${cartItems.length} item akan dikeluarkan dari warehouse.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4680ff',
        confirmButtonText: 'Ya, Keluarkan',
        cancelButtonText: 'Batal'
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetch('{{ route("transactions.goods-out.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                department_id: document.getElementById('deptSelect').value,
                notes: document.getElementById('transNotes').value,
                items: cartItems
            })
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('successTransNo').textContent = data.trans_no;
            document.getElementById('successBtn').onclick = () => { window.location.href = data.redirect; };
            new bootstrap.Modal(document.getElementById('successModal')).show();
            cartItems = [];
            renderCart();
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan. Coba lagi.' });
    }
}

// ===== CAMERA SCANNER =====
function toggleCameraScanner() {
    const camDiv = document.getElementById('cameraReader');
    if (camDiv.style.display === 'none') {
        camDiv.style.display = 'block';
        cameraScanner = new Html5Qrcode("cameraReader");
        cameraScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 200, height: 200 } },
            (code) => {
                document.getElementById('barcodeInput').value = code;
                lookupItem();
            },
            () => {}
        );
        document.getElementById('toggleCamera').innerHTML = '<i class="bi bi-camera-video-off me-1"></i>Matikan Kamera';
    } else {
        if (cameraScanner) cameraScanner.stop().catch(() => {});
        camDiv.style.display = 'none';
        document.getElementById('toggleCamera').innerHTML = '<i class="bi bi-camera me-1"></i>Aktifkan Kamera Scanner';
    }
}

$('.select2-dept').select2({ theme: 'bootstrap-5' });
</script>
@endpush
