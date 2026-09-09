@extends('layouts.app')
@section('title', 'Input Masuk Barang dari QAD')
@section('page-title', 'Input Masuk Barang dari QAD Receipt')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transactions.goods-in.index') }}" class="text-decoration-none">Masuk Barang</a></li>
    <li class="breadcrumb-item active">Input dari QAD</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-cloud-download me-2 text-primary"></i>Pilih Receipt QAD</div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:12px;">Dari Tanggal</label>
                        <input type="date" id="dateFrom" class="form-control form-control-sm"
                               value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:12px;">Sampai Tanggal</label>
                        <input type="date" id="dateTo" class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <button class="btn btn-primary btn-sm w-100 mb-3" onclick="loadReceipts()">
                    <i class="bi bi-search me-1"></i>Cari Receipt QAD
                </button>

                <div id="receiptList" class="list-group">
                    <div class="text-center text-muted py-3" style="font-size:13px;">
                        Klik "Cari Receipt" untuk memuat data dari QAD
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card" id="receiptDetailCard" style="display:none;">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-ul me-2 text-primary"></i>Detail Receipt: <strong id="selectedReceiptNo"></strong></span>
                <button class="btn btn-sm btn-light" onclick="clearReceiptDetail()"><i class="bi bi-x"></i></button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0" id="receiptDetailTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                            <th>Item</th>
                            <th>Qty Diterima</th>
                            <th>Satuan</th>
                        </tr>
                    </thead>
                    <tbody id="receiptDetailBody"></tbody>
                </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Departemen Tujuan <span class="text-danger">*</span></label>
                    <select id="departmentSelect" class="form-select">
                        <option value="">-- Pilih Departemen --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Barang akan masuk ke stok departemen ini.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan</label>
                    <input type="text" id="receiptNotes" class="form-control" placeholder="Catatan penerimaan (opsional)">
                </div>
                <button class="btn btn-success w-100" onclick="submitGoodsIn()">
                    <i class="bi bi-check-circle me-2"></i>Catat Penerimaan Barang
                </button>
            </div>
        </div>

        <div id="noReceiptSelected" class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-arrow-left-circle" style="font-size:36px;"></i>
                <p class="mt-2 mb-0">Pilih receipt dari panel kiri untuk melihat detail</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedReceiptNo = null;
let receiptLines = [];

async function loadReceipts() {
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;

    document.getElementById('receiptList').innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span style="font-size:13px;">Memuat dari QAD...</span>
        </div>`;

    const res = await fetch(`{{ route('transactions.goods-in.qad-receipts') }}?from=${from}&to=${to}`);
    const data = await res.json();

    if (!data.data.length) {
        document.getElementById('receiptList').innerHTML = `<div class="text-center text-muted py-3" style="font-size:13px;">Tidak ada receipt ditemukan</div>`;
        return;
    }

    document.getElementById('receiptList').innerHTML = data.data.map(r => `
        <a href="#" class="list-group-item list-group-item-action" onclick="selectReceipt('${r.receipt_no}')">
            <div class="d-flex justify-content-between">
                <span class="fw-semibold text-primary">${r.receipt_no}</span>
                <small class="text-muted">${r.receipt_date}</small>
            </div>
            <small class="text-muted">${r.vendor || '-'} | PO: ${r.po_no || '-'}</small>
        </a>
    `).join('');
}

async function selectReceipt(receiptNo) {
    selectedReceiptNo = receiptNo;
    document.getElementById('selectedReceiptNo').textContent = receiptNo;
    document.getElementById('receiptDetailBody').innerHTML = `
        <tr><td colspan="4" class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat detail...
        </td></tr>`;

    document.getElementById('receiptDetailCard').style.display = 'block';
    document.getElementById('noReceiptSelected').style.display = 'none';

    const res = await fetch(`{{ route('transactions.goods-in.receipt-detail') }}?receipt_no=${receiptNo}`);
    const data = await res.json();
    receiptLines = data.data;

    document.getElementById('receiptDetailBody').innerHTML = receiptLines.map((line, idx) => `
        <tr>
            <td><input type="checkbox" class="line-check" value="${idx}" checked></td>
            <td>
                <div class="fw-semibold">${line.description}</div>
                <div class="text-muted" style="font-size:11px;">${line.item_code}</div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm qty-input" data-idx="${idx}"
                       value="${line.qty_received}" min="0.01" step="0.01" style="width:90px;">
            </td>
            <td>${line.unit}</td>
        </tr>
    `).join('');
}

function clearReceiptDetail() {
    selectedReceiptNo = null;
    document.getElementById('receiptDetailCard').style.display = 'none';
    document.getElementById('noReceiptSelected').style.display = 'block';
}

function toggleAll(cb) {
    document.querySelectorAll('.line-check').forEach(c => c.checked = cb.checked);
}

async function submitGoodsIn() {
    const selectedLines = [];
    document.querySelectorAll('.line-check:checked').forEach(cb => {
        const idx = parseInt(cb.value);
        const qty = parseFloat(document.querySelector(`.qty-input[data-idx="${idx}"]`).value);
        selectedLines.push({ item_code: receiptLines[idx].item_code, qty_received: qty });
    });

    if (!selectedLines.length) {
        Swal.fire({ icon: 'warning', title: 'Pilih Item', text: 'Centang minimal 1 item.' }); return;
    }

    const departmentId = document.getElementById('departmentSelect').value;
    if (!departmentId) {
        Swal.fire({ icon: 'warning', title: 'Pilih Departemen', text: 'Pilih departemen tujuan barang ini.' }); return;
    }

    const result = await Swal.fire({
        title: 'Catat Penerimaan?',
        text: `${selectedLines.length} item dari receipt ${selectedReceiptNo} akan dicatat masuk.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4680ff',
        confirmButtonText: 'Ya, Catat',
        cancelButtonText: 'Batal'
    });
    if (!result.isConfirmed) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("transactions.goods-in.store") }}';
    form.innerHTML = `
        <input name="_token" value="{{ csrf_token() }}">
        <input name="qad_receipt_no" value="${selectedReceiptNo}">
        <input name="department_id" value="${departmentId}">
        <input name="notes" value="${document.getElementById('receiptNotes').value}">
        ${selectedLines.map((l, i) => `
            <input name="items[${i}][item_code]" value="${l.item_code}">
            <input name="items[${i}][qty_received]" value="${l.qty_received}">
        `).join('')}
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
