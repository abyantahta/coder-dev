@extends('layouts.app')
@section('title', 'Kembalikan Barang')
@section('page-title', 'Kembalikan Barang ke Warehouse')
@section('breadcrumb')
    <li class="breadcrumb-item">Transaksi</li>
    <li class="breadcrumb-item active">Kembalikan Barang</li>
@endsection

@push('styles')
<style>
    .scan-box { border: 2px dashed #0dcaf0; border-radius: 12px; padding: 20px; background: #f0feff; }
    #barcodeInput { font-size: 16px; letter-spacing: 2px; text-transform: uppercase; }
    .item-cart-row { animation: slideIn .3s ease; }
    @keyframes slideIn { from { opacity:0; transform:translateX(-10px); } to { opacity:1; transform:translateX(0); } }
</style>
@endpush

@section('content')
<div class="row g-3">
    {{-- Scanner Panel --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-return-left me-2 text-primary"></i>Scan Barang yang Dikembalikan
            </div>
            <div class="card-body">
                <div class="scan-box mb-3">
                    <label class="form-label fw-semibold mb-2">Scan / Input Barcode</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-upc text-info"></i></span>
                        <input type="text" id="barcodeInput" class="form-control"
                               placeholder="Scan atau ketik kode item..." autocomplete="off" autofocus>
                        <button class="btn btn-info text-white" onclick="lookupItem()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-outline-secondary btn-sm w-100 mb-3" id="toggleCamera" onclick="toggleCamera()">
                    <i class="bi bi-camera me-1"></i>Aktifkan Kamera
                </button>
                <div id="cameraReader" style="display:none;" class="mb-3"></div>
            </div>
        </div>

        {{-- Item Info --}}
        <div class="card mt-3" id="itemCard" style="display:none;">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-box-seam me-2 text-info"></i>Info Item</span>
                <button class="btn btn-sm btn-light" onclick="clearItem()"><i class="bi bi-x"></i></button>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-4">
                        <img id="itemPhoto" src="" class="img-fluid rounded" style="aspect-ratio:1;object-fit:cover;">
                    </div>
                    <div class="col-8">
                        <div class="fw-bold" style="font-size:13px;" id="itemCode"></div>
                        <div class="text-muted mb-2" style="font-size:12px;" id="itemDesc"></div>
                        <div class="p-2 bg-success bg-opacity-10 rounded">
                            <div class="text-muted" style="font-size:11px;">STOK SAAT INI</div>
                            <div class="fw-bold text-success" style="font-size:18px;" id="stockQty"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-semibold mb-1">Qty Dikembalikan</label>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group" style="width:140px;">
                            <button class="btn btn-outline-secondary btn-sm" onclick="adjustQty(-1)">−</button>
                            <input type="number" id="qtyInput" class="form-control text-center" value="1" min="0.01" step="0.01">
                            <button class="btn btn-outline-secondary btn-sm" onclick="adjustQty(1)">+</button>
                        </div>
                        <span class="text-muted" id="unitLabel" style="font-size:13px;"></span>
                    </div>
                    <input type="text" id="itemNotes" class="form-control mt-2" placeholder="Alasan / catatan (opsional)">
                    <button class="btn btn-info text-white w-100 mt-2" onclick="addToCart()">
                        <i class="bi bi-plus-circle me-1"></i>Tambah ke Daftar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cart --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2 text-primary"></i>Daftar Pengembalian</span>
                <span class="badge bg-info" id="cartCount">0 item</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                <table class="table table-hover mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                        <tr>
                            <th>Item</th>
                            <th width="110">Qty Kembali</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyRow">
                            <td colspan="3" class="text-center text-muted py-5">
                                <i class="bi bi-arrow-return-left" style="font-size:32px;"></i>
                                <p class="mt-2 mb-0">Scan barcode item yang akan dikembalikan</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer" id="cartFooter" style="display:none;">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Pengembalian</label>
                    <input type="text" id="returnNotes" class="form-control"
                           placeholder="Alasan pengembalian (opsional)">
                </div>
                <button class="btn btn-info text-white w-100" onclick="submitReturn()">
                    <i class="bi bi-arrow-return-left me-2"></i>Kembalikan ke Warehouse
                </button>
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
                    <div style="width:72px;height:72px;background:#cff4fc;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="bi bi-arrow-return-left text-info" style="font-size:32px;"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Pengembalian Berhasil!</h5>
                <p class="text-muted" style="font-size:13px;">Barang berhasil dikembalikan. Stok warehouse telah diperbarui.</p>
                <div class="bg-light rounded p-2 mb-3">
                    <div class="text-muted" style="font-size:11px;">No. Transaksi</div>
                    <div class="fw-bold text-info" id="successTransNo"></div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary flex-grow-1" onclick="resetPage()">
                        <i class="bi bi-arrow-repeat me-1"></i>Kembalikan Lagi
                    </button>
                    <a class="btn btn-info text-white flex-grow-1" href="{{ route('transactions.my-transactions.index') }}">
                        <i class="bi bi-list me-1"></i>Riwayat
                    </a>
                </div>
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
let scanner = null;

document.getElementById('barcodeInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') lookupItem();
});

async function lookupItem() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) return;

    const res  = await fetch(`{{ route('transactions.goods-return.item-info') }}?barcode=${encodeURIComponent(barcode)}`);
    const data = await res.json();

    if (data.success) {
        currentItem = data.item;
        document.getElementById('itemCode').textContent  = data.item.item_code;
        document.getElementById('itemDesc').textContent  = data.item.description;
        document.getElementById('itemPhoto').src         = data.item.photo;
        document.getElementById('stockQty').textContent  = data.item.qty_on_hand + ' ' + (data.item.unit || '');
        document.getElementById('unitLabel').textContent = data.item.unit || '';
        document.getElementById('qtyInput').value        = 1;
        document.getElementById('itemNotes').value       = '';
        document.getElementById('itemCard').style.display = 'block';
    } else {
        Swal.fire({ icon: 'error', title: 'Tidak Ditemukan', text: data.message, confirmButtonColor: '#0dcaf0' });
    }
}

function clearItem() {
    currentItem = null;
    document.getElementById('itemCard').style.display = 'none';
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeInput').focus();
}

function adjustQty(delta) {
    const input = document.getElementById('qtyInput');
    input.value = Math.max(0.01, (parseFloat(input.value) || 1) + delta);
}

function addToCart() {
    if (!currentItem) return;
    const qty   = parseFloat(document.getElementById('qtyInput').value) || 1;
    const notes = document.getElementById('itemNotes').value;
    const idx   = cartItems.findIndex(c => c.item_id === currentItem.id);

    if (idx >= 0) {
        cartItems[idx].qty += qty;
    } else {
        cartItems.push({
            item_id:     currentItem.id,
            item_code:   currentItem.item_code,
            description: currentItem.description,
            unit:        currentItem.unit,
            qty,
            notes
        });
    }
    renderCart();
    clearItem();
}

function renderCart() {
    const tbody  = document.getElementById('cartBody');
    const footer = document.getElementById('cartFooter');
    const count  = document.getElementById('cartCount');

    if (!cartItems.length) {
        tbody.innerHTML = '';
        tbody.appendChild(document.getElementById('emptyRow'));
        footer.style.display = 'none';
        count.textContent = '0 item';
        return;
    }

    tbody.innerHTML = cartItems.map((item, idx) => `
        <tr class="item-cart-row">
            <td>
                <div class="fw-semibold">${item.description}</div>
                <div class="text-muted" style="font-size:11px;">${item.item_code}</div>
                ${item.notes ? `<div class="text-info" style="font-size:11px;"><i class="bi bi-chat-text me-1"></i>${item.notes}</div>` : ''}
            </td>
            <td>
                <div class="fw-bold text-info">${item.qty} ${item.unit || ''}</div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');

    footer.style.display = 'block';
    count.textContent = cartItems.length + ' item';
}

function removeItem(idx) { cartItems.splice(idx, 1); renderCart(); }

async function submitReturn() {
    if (!cartItems.length) return;

    const result = await Swal.fire({
        title: 'Kembalikan Barang?',
        text: `${cartItems.length} item akan dikembalikan ke warehouse dan stok akan ditambahkan.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        confirmButtonText: 'Ya, Kembalikan',
        cancelButtonText: 'Batal'
    });
    if (!result.isConfirmed) return;

    const res  = await fetch('{{ route("transactions.goods-return.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            notes: document.getElementById('returnNotes').value,
            items: cartItems.map(i => ({ item_id: i.item_id, qty: i.qty, notes: i.notes }))
        })
    });
    const data = await res.json();

    if (data.success) {
        document.getElementById('successTransNo').textContent = data.trans_no;
        new bootstrap.Modal(document.getElementById('successModal')).show();
        cartItems = [];
        renderCart();
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
    }
}

function resetPage() {
    bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeInput').focus();
}

function toggleCamera() {
    const div = document.getElementById('cameraReader');
    if (div.style.display === 'none') {
        div.style.display = 'block';
        scanner = new Html5Qrcode('cameraReader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 200, height: 200 } },
            code => {
                document.getElementById('barcodeInput').value = code;
                lookupItem();
            }, () => {}
        );
        document.getElementById('toggleCamera').innerHTML = '<i class="bi bi-camera-video-off me-1"></i>Matikan Kamera';
    } else {
        if (scanner) scanner.stop().catch(() => {});
        div.style.display = 'none';
        document.getElementById('toggleCamera').innerHTML = '<i class="bi bi-camera me-1"></i>Aktifkan Kamera';
    }
}
</script>
@endpush
