@extends('layouts.app')
@section('title','Buat Permintaan Pengadaan')
@section('page-title','Permintaan Pengadaan ATK')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('procurement.requests.index') }}" class="text-decoration-none">Permintaan Saya</a></li>
    <li class="breadcrumb-item active">Buat Permintaan</li>
@endsection

@push('styles')
<style>
/* ===== LAYOUT ===== */
.shop-layout { display:grid; grid-template-columns:230px 1fr 360px; gap:16px; align-items:start; }
@media(max-width:1200px){ .shop-layout { grid-template-columns:200px 1fr 360px; } }
@media(max-width:992px){ .shop-layout { grid-template-columns:1fr; } }

/* ===== CATEGORY TREE ===== */
.cat-tree { padding:8px; }
.cat-tree-node { border-radius:8px; margin-bottom:2px; }
.cat-tree-row {
    display:flex; align-items:center; gap:6px; padding:8px 10px;
    border-radius:8px; cursor:pointer; font-size:13px; font-weight:500;
    background:#f4f5f8; color:#495057; transition:background .15s;
}
.cat-tree-row:hover { background:#e9ecef; }
.cat-tree-row.active { background:#4680ff; color:#fff; }
.cat-tree-row .chevron { font-size:11px; width:14px; flex-shrink:0; display:inline-block; transition:transform .15s; color:#6c757d; }
.cat-tree-row.active .chevron { color:#fff; }
.cat-tree-row .chevron.rotated { transform:rotate(90deg); }
.cat-tree-row .chevron.invisible-chevron { visibility:hidden; }
.cat-tree-children { margin-left:14px; padding-left:8px; border-left:1px dashed #dee2e6; margin-top:2px; display:none; }
.cat-tree-children.show { display:block; }
.cat-tree-children .cat-tree-row { background:transparent; font-weight:400; padding:6px 10px; }
.cat-tree-children .cat-tree-row:hover { background:#e9ecef; }
.cat-tree-children .cat-tree-row.active { background:#4680ff; color:#fff; }

/* ===== ITEM CARDS ===== */
.item-card {
    border:2px solid transparent; border-radius:12px;
    cursor:pointer; transition:all .2s;
    background:white; position:relative; overflow:hidden;
}
.item-card:hover { border-color:#4680ff; box-shadow:0 4px 16px rgba(70,128,255,.15); transform:translateY(-2px); }
.item-card.in-cart { border-color:#198754; background:#f0fff4; }
.item-card .card-img { width:100%; aspect-ratio:1; object-fit:cover; border-radius:8px 8px 0 0; }
.item-card .no-photo {
    width:100%; aspect-ratio:1; background:linear-gradient(135deg,#f0f2f5,#e9ecef);
    display:flex; align-items:center; justify-content:center;
    border-radius:8px 8px 0 0; color:#adb5bd; font-size:32px;
}
.item-card .card-body-inner { padding:10px 12px 12px; }
.item-card .item-name { font-size:13px; font-weight:600; line-height:1.3; margin-bottom:4px; }
.item-card .item-code { font-size:10px; color:#6c757d; }
.item-card .item-cat  { font-size:10px; }
.item-card .in-cart-badge {
    position:absolute; top:8px; right:8px;
    background:#198754; color:white; border-radius:50%;
    width:24px; height:24px; display:none;
    align-items:center; justify-content:center; font-size:12px;
}
.item-card.in-cart .in-cart-badge { display:flex; }
.item-card .frequent-badge {
    position:absolute; top:8px; left:8px; z-index:1;
    background:#fff8e1; color:#b8860b; border:1px solid #f0c94a;
    border-radius:20px; padding:2px 8px; font-size:9px; font-weight:600;
}

/* ===== CATEGORY PILLS ===== */
.cat-pill {
    padding:5px 14px; border-radius:20px; font-size:12px; font-weight:500;
    border:1.5px solid #dee2e6; background:white; cursor:pointer;
    white-space:nowrap; transition:all .15s;
}
.cat-pill:hover { border-color:#4680ff; color:#4680ff; }
.cat-pill.active { background:#4680ff; border-color:#4680ff; color:white; }

/* ===== CART ===== */
.cart-item-row { border-bottom:1px solid #f0f2f5; padding:8px 0; }
.cart-item-row:last-child { border-bottom:none; }
.qty-ctrl { display:flex; align-items:center; gap:4px; }
.qty-ctrl button { width:26px; height:26px; border:1px solid #dee2e6; background:white; border-radius:6px; font-size:13px; cursor:pointer; }
.qty-ctrl button:hover { background:#f0f2f5; }
.qty-ctrl input { width:50px; height:26px; text-align:center; border:1px solid #dee2e6; border-radius:6px; font-size:12px; }

/* ===== ITEM MODAL ===== */
.modal-item-img { width:100%; aspect-ratio:1; object-fit:cover; border-radius:12px; }

/* ===== SEARCH ===== */
.search-wrapper { position:relative; }
.search-wrapper .bi-search { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#6c757d; }
.search-wrapper input { padding-left:36px; border-radius:10px; }

/* ===== LOADING ===== */
.skeleton-card { background:#f0f2f5; border-radius:12px; animation:shimmer 1.2s infinite; aspect-ratio:1; }
@keyframes shimmer { 0%,100%{opacity:.6;} 50%{opacity:1;} }
</style>
@endpush

@section('content')
<form id="procurementForm" method="POST" action="{{ route('procurement.requests.store') }}" enctype="multipart/form-data">
@csrf

<div class="shop-layout">

    {{-- ===== KIRI: Tree Kategori ===== --}}
    <div>
        <div class="card">
            <div class="card-header fw-semibold" style="font-size:13px;">
                <i class="bi bi-tags me-2 text-primary"></i>Categories
            </div>
            <div class="card-body cat-tree" id="categoryTree">
                <div class="cat-tree-row active" data-cat="all" onclick="setCategory('all', this, false)">
                    <i class="bi bi-chevron-right chevron invisible-chevron"></i>Semua
                </div>
                {{-- sub-tree diisi via JS --}}
            </div>
        </div>
    </div>

    {{-- ===== TENGAH: Katalog Item ===== --}}
    <div>
        {{-- Search --}}
        <div class="card mb-3">
            <div class="card-body pb-2">
                <div class="search-wrapper mb-3">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control"
                           placeholder="Cari nama atau kode item ATK...">
                </div>
            </div>
        </div>

        {{-- Item Grid --}}
        <div class="row g-2" id="itemGrid">
            <div class="col-12 text-center text-muted py-5">
                <div class="spinner-border text-primary mb-2"></div>
                <p class="mb-0">Memuat katalog...</p>
            </div>
        </div>
    </div>

    {{-- ===== KANAN: Info + Cart ===== --}}
    <div class="sticky-top" style="top:86px;">

        {{-- Info Permintaan --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold" style="font-size:14px;">
                <i class="bi bi-info-circle me-2 text-primary"></i>Info Permintaan
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Keperluan / Tujuan <span class="text-danger">*</span></label>
                    <input type="text" name="purpose"
                           class="form-control form-control-sm @error('purpose') is-invalid @enderror"
                           value="{{ old('purpose', $defaultPurpose) }}">
                    <div class="form-text" style="font-size:11px;">Default otomatis bulan depan — bisa diedit bebas.</div>
                    @error('purpose')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Departemen</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->department?->name ?? '-' }}" disabled readonly>
                    <div class="form-text" style="font-size:11px;">Otomatis sesuai departemen Anda di data user.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Catatan</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" style="font-size:12px;">Lampiran (opsional)</label>
                    <input type="file" id="attachmentInput" name="attachments[]" class="form-control form-control-sm @error('attachments.*') is-invalid @enderror"
                           accept=".jpg,.jpeg,.pdf,.xlsx,.xls" multiple>
                    <div class="form-text" style="font-size:11px;">JPG, PDF, atau Excel — maks 5 file, 10MB per file. Foto otomatis dikompres.</div>
                    @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div id="attachmentList" class="mt-2"></div>
                </div>
            </div>
        </div>

        {{-- Cart --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold" style="font-size:14px;"><i class="bi bi-cart3 me-2 text-primary"></i>Keranjang</span>
                <span class="badge bg-primary rounded-pill" id="cartBadge">0</span>
            </div>

            <div class="card-body p-0" id="cartBody" style="max-height:320px;overflow-y:auto;">
                <div id="emptyCart" class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="bi bi-cart" style="font-size:28px;"></i>
                    <p class="mt-2 mb-0">Klik item untuk menambahkan</p>
                </div>
            </div>

            <div class="card-footer" id="cartFooter" style="display:none;">
                {{-- Total Estimasi --}}
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="text-muted" style="font-size:12px;">Estimasi Total</span>
                    <span class="fw-bold text-success" id="cartTotal" style="font-size:14px;">Rp 0</span>
                </div>
                <hr class="my-2">
                <div class="p-2 bg-light rounded mb-3" style="font-size:11px;">
                    <div class="d-flex gap-1 align-items-center flex-wrap">
                        <span class="badge bg-secondary px-2">Anda</span>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <span class="badge bg-info px-2">Section</span>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <span class="badge bg-primary px-2">GA</span>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <span class="badge bg-success px-2">Direktur</span>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <span class="badge bg-dark px-2">QAD</span>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="action" value="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send me-1"></i>Ajukan Permintaan
                    </button>
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-save me-1"></i>Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

{{-- Item Detail Modal --}}
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-body p-0">
                <div style="position:relative;">
                    <div id="modalPhotoContainer"></div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-2 bg-white rounded-circle p-2" data-bs-dismiss="modal"></button>
                    <span id="modalCategory" class="badge bg-primary position-absolute bottom-0 start-0 m-2"></span>
                </div>
                <div class="p-4">
                    <div class="fw-bold mb-1" style="font-size:16px;" id="modalName"></div>
                    <div class="text-muted mb-2" style="font-size:12px;" id="modalCode"></div>
                    <div class="text-muted mb-2" style="font-size:13px;" id="modalDesc"></div>
                    <div class="fw-bold text-success mb-3" style="font-size:18px;" id="modalPrice"></div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="qty-ctrl">
                            <button type="button" onclick="modalAdjustQty(-1)">−</button>
                            <input type="number" id="modalQty" value="1" min="1" step="0.01" oninput="checkModalBudget()">
                            <button type="button" onclick="modalAdjustQty(1)">+</button>
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info fw-semibold" id="modalUom"></span>
                    </div>

                    <input type="text" id="modalNotes" class="form-control form-control-sm mb-3" placeholder="Keterangan (opsional)">

                    <div id="modalBudgetWarning" class="alert alert-warning py-2 mb-3" style="font-size:12px;display:none;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Qty ini melebihi sisa budget departemen bulan ini. Tetap bisa diajukan, tapi perlu persetujuan tambahan budget dari GA.
                    </div>

                    <button class="btn btn-primary w-100" onclick="addFromModal()">
                        <i class="bi bi-cart-plus me-1"></i>Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let allItems    = [];
let cartItems   = [];
let currentItem = null;
let activeCategory = 'all';
let categoryTree = [];

// ===== INIT =====
async function loadCatalog() {
    const [itemsRes, catsRes] = await Promise.all([
        fetch('{{ route("procurement.items.search") }}'),
        fetch('{{ route("procurement.items.categories") }}')
    ]);
    allItems = await itemsRes.json();
    categoryTree = await catsRes.json();

    renderCategoryTree();
    renderGrid(allItems);
}

// ===== CATEGORY TREE =====
function renderCategoryTree() {
    const container = document.getElementById('categoryTree');

    categoryTree.forEach(parent => {
        const hasChildren = parent.children && parent.children.length > 0;

        const node = document.createElement('div');
        node.className = 'cat-tree-node';

        const row = document.createElement('div');
        row.className = 'cat-tree-row';
        row.dataset.cat = parent.id;
        row.innerHTML = `<i class="bi bi-chevron-right chevron ${hasChildren ? '' : 'invisible-chevron'}"></i>${parent.name}`;
        row.onclick = () => setCategory(parent.id, row, true);
        node.appendChild(row);

        if (hasChildren) {
            const chevron = row.querySelector('.chevron');
            const childrenWrap = document.createElement('div');
            childrenWrap.className = 'cat-tree-children';
            childrenWrap.id = `cat-children-${parent.id}`;

            parent.children.forEach(child => {
                const childRow = document.createElement('div');
                childRow.className = 'cat-tree-row';
                childRow.dataset.cat = child.id;
                childRow.innerHTML = `<i class="bi bi-chevron-right chevron invisible-chevron"></i>${child.name}`;
                childRow.onclick = (e) => { e.stopPropagation(); setCategory(child.id, childRow, false); };
                childrenWrap.appendChild(childRow);
            });

            chevron.onclick = (e) => {
                e.stopPropagation();
                childrenWrap.classList.toggle('show');
                chevron.classList.toggle('rotated');
            };

            node.appendChild(childrenWrap);
        }

        container.appendChild(node);
    });
}

function setCategory(catId, rowEl, isParent) {
    activeCategory = catId;

    document.querySelectorAll('.cat-tree-row').forEach(r => r.classList.remove('active'));
    rowEl.classList.add('active');

    if (isParent) {
        const childrenWrap = document.getElementById(`cat-children-${catId}`);
        if (childrenWrap && !childrenWrap.classList.contains('show')) {
            childrenWrap.classList.add('show');
            rowEl.querySelector('.chevron').classList.add('rotated');
        }
    }

    const q = document.getElementById('searchInput').value.trim();
    filterAndRender(q, catId);
}

/** true kalau item ini masuk kategori yang dipilih — termasuk kalau yang
 * dipilih itu parent, item yang category_id-nya salah satu anaknya ikut cocok. */
function categoryMatches(itemCategoryId, selectedCatId) {
    if (selectedCatId === 'all') return true;
    const selId = parseInt(selectedCatId);
    if (itemCategoryId === selId) return true;

    const parent = categoryTree.find(p => p.id === selId);
    return !!(parent && parent.children && parent.children.some(c => c.id === itemCategoryId));
}

function filterAndRender(q, cat) {
    let filtered = allItems;
    if (q) {
        filtered = filtered.filter(i =>
            i.name.toLowerCase().includes(q.toLowerCase()) ||
            i.item_code.toLowerCase().includes(q.toLowerCase())
        );
    }
    if (cat && cat !== 'all') {
        filtered = filtered.filter(i => categoryMatches(i.category_id, cat));
    }
    renderGrid(filtered);
}

let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => filterAndRender(this.value.trim(), activeCategory), 250);
});

// ===== BUDGET =====
function budgetBadge(item) {
    if (item.remaining_budget === null || item.remaining_budget === undefined) return '';
    const over = item.remaining_budget <= 0;
    return `<div class="mt-1" style="font-size:9px;">
        <span class="badge ${over ? 'bg-danger' : 'bg-success'} bg-opacity-10 ${over ? 'text-danger' : 'text-success'}">
            Sisa Budget: Rp ${Math.round(item.remaining_budget).toLocaleString('id-ID')}
        </span>
    </div>`;
}

// ===== RENDER GRID =====
function renderGrid(items) {
    const grid = document.getElementById('itemGrid');

    if (!items.length) {
        grid.innerHTML = `<div class="col-12 text-center text-muted py-5">
            <i class="bi bi-search" style="font-size:36px;"></i>
            <p class="mt-2 mb-0">Tidak ada item ditemukan</p>
        </div>`;
        return;
    }

    const inCartIds = new Set(cartItems.map(c => c.id));

    grid.innerHTML = items.map(item => `
        <div class="col-6 col-md-4 col-xl-3">
            <div class="item-card ${inCartIds.has(item.id) ? 'in-cart' : ''}" onclick="openItemModal(${item.id})">
                <div class="in-cart-badge"><i class="bi bi-check"></i></div>
                ${item.order_count > 0 ? `<span class="frequent-badge"><i class="bi bi-star-fill me-1"></i>Sering Dipesan</span>` : ''}
                ${item.photo
                    ? `<img src="${item.photo}" class="card-img" alt="${item.name}" loading="lazy">`
                    : `<div class="no-photo"><i class="bi bi-box-seam"></i></div>`
                }
                <div class="card-body-inner">
                    <div class="item-name text-truncate" title="${item.name}">${item.name}</div>
                    <div class="item-code mb-1">${item.item_code}</div>
                    <div class="d-flex justify-content-end align-items-center mb-1">
                        <span class="badge bg-info bg-opacity-10 text-info" style="font-size:10px;">${item.uom_code}</span>
                    </div>
                    <div class="fw-bold text-success" style="font-size:12px;">
                        ${item.price > 0
                            ? 'Rp ' + item.price.toLocaleString('id-ID')
                            : '<span class="text-muted fw-normal" style="font-size:10px;">Harga dari QAD</span>'
                        }
                    </div>
                    ${budgetBadge(item)}
                </div>
            </div>
        </div>
    `).join('');
}

// ===== MODAL =====
function openItemModal(itemId) {
    currentItem = allItems.find(i => i.id === itemId);
    if (!currentItem) return;

    const inCart = cartItems.find(c => c.id === itemId);
    document.getElementById('modalName').textContent     = currentItem.name;
    document.getElementById('modalCode').textContent     = currentItem.item_code;
    document.getElementById('modalDesc').textContent     = currentItem.description || '';
    document.getElementById('modalCategory').textContent = currentItem.category;
    document.getElementById('modalUom').textContent      = currentItem.uom_code;
    document.getElementById('modalPrice').textContent    = currentItem.price > 0
        ? 'Rp ' + currentItem.price.toLocaleString('id-ID') + ' / ' + currentItem.uom_code
        : 'Harga dari QAD';
    document.getElementById('modalQty').value            = inCart ? inCart.qty : 1;
    document.getElementById('modalNotes').value          = inCart ? inCart.notes : '';

    document.getElementById('modalPhotoContainer').innerHTML = currentItem.photo
        ? `<img src="${currentItem.photo}" alt="${currentItem.name}" class="modal-item-img">`
        : `<div class="modal-item-img d-flex align-items-center justify-content-center bg-light text-muted" style="font-size:48px;">
               <i class="bi bi-box-seam"></i>
           </div>`;

    new bootstrap.Modal(document.getElementById('itemModal')).show();
    checkModalBudget();
}

function checkModalBudget() {
    const warning = document.getElementById('modalBudgetWarning');
    if (!currentItem || currentItem.remaining_budget === null || currentItem.remaining_budget === undefined) {
        warning.style.display = 'none';
        return;
    }
    const qty = parseFloat(document.getElementById('modalQty').value) || 0;
    const cost = qty * (currentItem.price || 0);
    warning.style.display = cost > currentItem.remaining_budget ? 'block' : 'none';
}

function modalAdjustQty(delta) {
    const input = document.getElementById('modalQty');
    input.value = Math.max(0.01, parseFloat(input.value || 1) + delta);
    checkModalBudget();
}

function addFromModal() {
    if (!currentItem) return;
    const qty   = parseFloat(document.getElementById('modalQty').value) || 1;
    const notes = document.getElementById('modalNotes').value;

    const idx = cartItems.findIndex(c => c.id === currentItem.id);
    if (idx >= 0) {
        cartItems[idx].qty   = qty;
        cartItems[idx].notes = notes;
    } else {
        cartItems.push({ ...currentItem, qty, notes });
    }

    bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
    renderCart();
    refreshGrid();
}

// ===== CART =====
function cartAmountHtml(item) {
    const cost = (item.price || 0) * item.qty;
    const priceHtml = cost > 0 ? `<div class="text-success" style="font-size:10px;">Rp ${cost.toLocaleString('id-ID')}</div>` : '';

    const hasBudget = item.remaining_budget !== null && item.remaining_budget !== undefined;
    const overBudget = hasBudget && cost > item.remaining_budget;
    const badgeHtml = overBudget
        ? `<span class="badge bg-danger bg-opacity-10 text-danger mt-1" style="font-size:9px;">
               <i class="bi bi-exclamation-triangle-fill me-1"></i>Melebihi Budget
           </span>`
        : '';

    return priceHtml + badgeHtml;
}

function renderCart() {
    const body   = document.getElementById('cartBody');
    const footer = document.getElementById('cartFooter');
    const badge  = document.getElementById('cartBadge');

    badge.textContent = cartItems.length;

    if (!cartItems.length) {
        body.innerHTML = `
            <div class="text-center text-muted py-4" style="font-size:13px;">
                <i class="bi bi-cart" style="font-size:28px;"></i>
                <p class="mt-2 mb-0">Klik item untuk menambahkan</p>
            </div>`;
        footer.style.display = 'none';
        document.getElementById('cartTotal').textContent = 'Rp 0';
        return;
    }

    footer.style.display = 'block';

    const total = cartItems.reduce((sum, item) => sum + ((item.price || 0) * item.qty), 0);
    document.getElementById('cartTotal').textContent = total > 0
        ? 'Rp ' + total.toLocaleString('id-ID') : 'Rp —';

    body.innerHTML = cartItems.map((item, idx) => `
        <div class="cart-item-row px-3">
            <input type="hidden" name="items[${idx}][item_id]" value="${item.id}">
            <input type="hidden" name="items[${idx}][uom_id]"  value="${item.uom_id}">
            <input type="hidden" name="items[${idx}][qty]"     value="${item.qty}">
            <input type="hidden" name="items[${idx}][notes]"   value="${item.notes || ''}">

            <div class="d-flex gap-2 align-items-start">
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-semibold text-truncate" style="font-size:12px;">${item.name}</div>
                    <div class="text-muted" style="font-size:10px;">${item.item_code} · ${item.uom_code}</div>
                    <div id="cartAmount${idx}">${cartAmountHtml(item)}</div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0"
                        onclick="removeFromCart(${idx})">
                    <i class="bi bi-trash" style="font-size:12px;"></i>
                </button>
            </div>
            <div class="d-flex gap-2 align-items-center mt-1">
                <div class="qty-ctrl">
                    <button type="button"
                            onclick="event.preventDefault();changeCartQty(${idx},-1)">−</button>
                    <input type="number" value="${item.qty}" min="0.01" step="0.01"
                           onchange="changeCartQtyDirect(${idx},this.value)"
                           style="width:52px;height:26px;text-align:center;border:1px solid #dee2e6;border-radius:6px;font-size:12px;">
                    <button type="button"
                            onclick="event.preventDefault();changeCartQty(${idx},1)">+</button>
                </div>
                <input type="text" value="${item.notes || ''}" placeholder="Ket..."
                       oninput="cartItems[${idx}].notes=this.value"
                       class="form-control form-control-sm flex-grow-1"
                       style="font-size:11px;height:26px;">
            </div>
        </div>
    `).join('');
}

function changeCartQty(idx, delta) {
    if (!cartItems[idx]) return;
    cartItems[idx].qty = Math.max(0.01, Math.round((parseFloat(cartItems[idx].qty) + delta) * 100) / 100);
    renderCart();
    refreshGrid();
}

function changeCartQtyDirect(idx, val) {
    if (!cartItems[idx]) return;
    cartItems[idx].qty = Math.max(0.01, parseFloat(val) || 1);
    // Update total + badge budget baris ini saja, jangan re-render penuh biar fokus input tidak hilang
    const total = cartItems.reduce((s,i) => s + ((i.price||0)*i.qty), 0);
    document.getElementById('cartTotal').textContent = total > 0 ? 'Rp ' + total.toLocaleString('id-ID') : 'Rp —';
    const amountEl = document.getElementById('cartAmount' + idx);
    if (amountEl) amountEl.innerHTML = cartAmountHtml(cartItems[idx]);
}

function removeFromCart(idx) {
    cartItems.splice(idx, 1);
    renderCart();
    refreshGrid();
}

function refreshGrid() {
    const q = document.getElementById('searchInput').value.trim();
    filterAndRender(q, activeCategory);
}

// ===== LAMPIRAN PREVIEW =====
document.getElementById('attachmentInput').addEventListener('change', function() {
    const list = document.getElementById('attachmentList');
    list.innerHTML = '';
    Array.from(this.files).forEach(file => {
        const sizeKb = (file.size / 1024).toFixed(0);
        const icon = file.name.match(/\.pdf$/i) ? 'bi-file-earmark-pdf text-danger'
            : file.name.match(/\.xlsx?$/i) ? 'bi-file-earmark-excel text-success'
            : 'bi-file-earmark-image text-primary';
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 py-1';
        row.style.fontSize = '11px';
        row.innerHTML = `<i class="bi ${icon}"></i><span class="text-truncate flex-grow-1">${file.name}</span><span class="text-muted">${sizeKb} KB</span>`;
        list.appendChild(row);
    });
});

// Validate sebelum submit
document.getElementById('procurementForm').addEventListener('submit', function(e) {
    if (cartItems.length === 0) {
        e.preventDefault();
        Swal.fire({ icon:'warning', title:'Keranjang Kosong', text:'Tambahkan minimal 1 item.' });
    }
});

// ===== LOAD =====
document.addEventListener('DOMContentLoaded', loadCatalog);
</script>
@endpush
