<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — <?php echo e(config('app.name')); ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <style>
        :root {
            --pc-sidebar-width: 260px;
            --pc-header-height: 70px;
            --pc-sidebar-bg: #1a1f3a;
            --pc-sidebar-color: #a9b7d0;
            --pc-sidebar-active: #4680ff;
            --pc-header-bg: #ffffff;
            --pc-body-bg: #f4f7fa;
            --pc-card-bg: #ffffff;
            --pc-primary: #4680ff;
            --pc-primary-dark: #3a6edc;
        }

        * { box-sizing: border-box; }
        body {
            background: var(--pc-body-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            color: #343a40;
            margin: 0;
        }

        /* ===== SIDEBAR ===== */
        .pc-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--pc-sidebar-width);
            background: var(--pc-sidebar-bg);
            z-index: 1050;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width 0.3s ease;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }
        .pc-sidebar::-webkit-scrollbar { width: 4px; }
        .pc-sidebar::-webkit-scrollbar-track { background: transparent; }
        .pc-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }

        .pc-sidebar .sidebar-logo {
            display: flex;
            align-items: center;
            padding: 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            height: var(--pc-header-height);
        }
        .pc-sidebar .sidebar-logo .logo-icon {
            width: 42px; height: 42px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            background: white;
            padding: 2px;
        }
        .pc-sidebar .sidebar-logo .logo-icon img {
            width: 100%; height: 100%; object-fit: contain; border-radius: 50%;
        }
        .pc-sidebar .sidebar-logo .logo-text {
            margin-left: 12px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            white-space: nowrap;
        }
        .pc-sidebar .sidebar-logo .logo-sub {
            font-size: 11px;
            color: var(--pc-sidebar-color);
            font-weight: 400;
        }

        .sidebar-nav { padding: 16px 0; }
        .sidebar-caption {
            padding: 12px 20px 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(169,183,208,.5);
        }

        .sidebar-item { list-style: none; }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--pc-sidebar-color);
            text-decoration: none;
            border-radius: 0;
            transition: all .2s;
            position: relative;
            gap: 12px;
        }
        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.06);
        }
        .sidebar-link.active {
            color: white;
            background: rgba(70,128,255,.15);
        }
        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--pc-primary);
            border-radius: 0 2px 2px 0;
        }
        .sidebar-link .pc-micon {
            font-size: 18px;
            width: 24px;
            flex-shrink: 0;
        }
        .sidebar-link .pc-mtext { font-size: 13.5px; font-weight: 500; }
        .sidebar-link .pc-arrow {
            margin-left: auto;
            font-size: 11px;
            transition: transform .2s;
        }
        .sidebar-link[aria-expanded="true"] .pc-arrow { transform: rotate(90deg); }
        .sidebar-link .badge-pill {
            margin-left: auto;
            font-size: 10px;
            padding: 2px 7px;
        }

        .pc-submenu {
            background: rgba(0,0,0,.15);
            list-style: none;
            padding: 4px 0;
        }
        .pc-submenu .sidebar-link {
            padding: 8px 20px 8px 56px;
            font-size: 13px;
        }

        /* ===== HEADER ===== */
        .pc-header {
            position: fixed;
            top: 0; right: 0;
            left: var(--pc-sidebar-width);
            height: var(--pc-header-height);
            background: var(--pc-header-bg);
            border-bottom: 1px solid #e9ecef;
            z-index: 1040;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            transition: left 0.3s ease;
        }
        .pc-header .header-toggle {
            width: 36px; height: 36px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            color: #6c757d;
        }
        .pc-header .header-toggle:hover { background: #f0f2f5; }
        .pc-header .header-search {
            flex: 1;
            max-width: 320px;
        }
        .pc-header .header-search .form-control {
            border: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 8px;
            padding-left: 36px;
            font-size: 13px;
        }
        .pc-header .header-search .search-icon {
            position: absolute;
            left: 10px; top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .pc-header .ms-auto { margin-left: auto !important; }
        .header-user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }
        .header-notification-btn {
            width: 36px; height: 36px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            color: #6c757d;
            position: relative;
            display: flex; align-items: center; justify-content: center;
        }
        .header-notification-btn:hover { background: #f0f2f5; }
        .header-notification-btn .badge-dot {
            position: absolute;
            top: 5px; right: 5px;
            width: 8px; height: 8px;
            background: #dc3545;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* ===== MAIN CONTENT ===== */
        .pc-container {
            margin-left: var(--pc-sidebar-width);
            margin-top: var(--pc-header-height);
            min-height: calc(100vh - var(--pc-header-height));
            transition: margin-left 0.3s ease;
        }
        .pc-content {
            padding: 24px;
        }

        /* ===== BREADCRUMB ===== */
        .page-header {
            margin-bottom: 24px;
        }
        .page-header h4 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 4px;
        }
        .breadcrumb {
            margin: 0;
            padding: 0;
            background: none;
            font-size: 12px;
        }
        .breadcrumb-item + .breadcrumb-item::before { color: #adb5bd; }

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f2f5;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 15px;
        }
        .card-body { padding: 20px; }

        /* ===== STAT CARDS ===== */
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }
        .stat-card .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .stat-card .stat-value { font-size: 28px; font-weight: 700; }
        .stat-card .stat-label { font-size: 13px; color: #6c757d; }
        .stat-card .stat-change { font-size: 12px; }

        /* ===== TABLES ===== */
        .table { font-size: 13.5px; }
        .table th { font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; }
        .table td { vertical-align: middle; }

        /* ===== BADGE STATUS ===== */
        .badge { font-size: 11px; font-weight: 500; padding: 4px 10px; border-radius: 6px; }

        /* ===== BUTTONS ===== */
        .btn-sm { font-size: 12px; padding: 5px 12px; }

        /* ===== MOBILE OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 1049;
        }

        /* ===== SIDEBAR COLLAPSED ===== */
        body.sidebar-collapsed .pc-sidebar { width: 70px; }
        body.sidebar-collapsed .pc-sidebar .logo-text,
        body.sidebar-collapsed .pc-sidebar .logo-sub,
        body.sidebar-collapsed .pc-sidebar .pc-mtext,
        body.sidebar-collapsed .pc-sidebar .pc-arrow,
        body.sidebar-collapsed .pc-sidebar .sidebar-caption,
        body.sidebar-collapsed .pc-sidebar .badge-pill { display: none; }
        body.sidebar-collapsed .pc-sidebar .sidebar-link { justify-content: center; padding: 12px; }
        body.sidebar-collapsed .pc-sidebar .pc-micon { width: auto; }
        body.sidebar-collapsed .pc-header { left: 70px; }
        body.sidebar-collapsed .pc-container { margin-left: 70px; }

        @media (max-width: 992px) {
            .pc-sidebar { left: calc(0px - var(--pc-sidebar-width)); }
            .pc-header { left: 0; }
            .pc-container { margin-left: 0; }
            body.sidebar-open .pc-sidebar { left: 0; }
            body.sidebar-open .sidebar-overlay { display: block; }
        }

        /* ===== UTILS ===== */
        .avatar-sm { width: 36px; height: 36px; border-radius: 8px; }
        .avatar-md { width: 48px; height: 48px; border-radius: 10px; }
        .text-primary { color: var(--pc-primary) !important; }
        .btn-primary { background: var(--pc-primary); border-color: var(--pc-primary); }
        .btn-primary:hover { background: var(--pc-primary-dark); border-color: var(--pc-primary-dark); }
        .bg-primary { background: var(--pc-primary) !important; }
        .border-primary { border-color: var(--pc-primary) !important; }

        /* QR Scanner modal */
        #qr-reader { width: 100% !important; }
        #qr-reader video { border-radius: 8px; }

        /* ===== GLOBAL LOADING OVERLAY ===== */
        #globalLoadingOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.65);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        #globalLoadingOverlay.active { display: flex; }
        #globalLoadingOverlay .spinner-border { width: 3rem; height: 3rem; }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>


<div id="globalLoadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 fw-semibold text-primary" style="font-size:13px;">Memproses...</div>
    </div>
</div>


<div class="modal fade" id="attachmentPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header">
                <h6 class="modal-title fw-bold text-truncate" id="attachmentPreviewTitle">Lampiran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="attachmentPreviewBody" style="min-height:200px;"></div>
            <div class="modal-footer border-0">
                <a href="#" id="attachmentPreviewDownload" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Tab Baru
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<nav class="pc-sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <img src="<?php echo e(asset('assets/images/logo-sdi.png')); ?>" alt="Logo">
        </div>
        <div class="ms-2">
            <div class="logo-text">General Affairs</div>
            <div class="logo-sub">Inquiry &amp; Needs System</div>
        </div>
    </div>

    <ul class="sidebar-nav list-unstyled mb-0">

        
        <li class="sidebar-item">
            <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-grid-1x2-fill"></i>
                <span class="pc-mtext">Dashboard</span>
            </a>
        </li>

        
        <?php if(auth()->user()->isAdmin()): ?>
        <li class="sidebar-caption">Master Data</li>

        <li class="sidebar-item">
            <a href="#masterMenu" class="sidebar-link <?php echo e(request()->is('master/*') ? 'active' : ''); ?>"
               data-bs-toggle="collapse" aria-expanded="<?php echo e(request()->is('master/*') ? 'true' : 'false'); ?>">
                <i class="pc-micon bi bi-database-fill"></i>
                <span class="pc-mtext">Master Data</span>
                <i class="pc-arrow bi bi-chevron-right"></i>
            </a>
            <ul class="pc-submenu collapse list-unstyled <?php echo e(request()->is('master/*') ? 'show' : ''); ?>" id="masterMenu">
                <li class="sidebar-item">
                    <a href="<?php echo e(route('master.departments.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('master.departments.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-building"></i>
                        <span class="pc-mtext">Departemen</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo e(route('master.users.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('master.users.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-people-fill"></i>
                        <span class="pc-mtext">User Management</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo e(route('master.items.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('master.items.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-box-seam"></i>
                        <span class="pc-mtext">Item</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->isAdmin()): ?>
        <li class="sidebar-caption">Transaksi</li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('transactions.goods-out.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('transactions.goods-out.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-box-arrow-up-right"></i>
                <span class="pc-mtext">Keluar Barang</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('transactions.goods-return.scan')); ?>" class="sidebar-link <?php echo e(request()->routeIs('transactions.goods-return.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-arrow-return-left"></i>
                <span class="pc-mtext">Kembalikan Barang</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('transactions.goods-in.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('transactions.goods-in.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-box-arrow-in-down-right"></i>
                <span class="pc-mtext">Masuk Barang</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('transactions.memo.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('transactions.memo.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-journal-text"></i>
                <span class="pc-mtext">Item Memo</span>
            </a>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->isAdmin()): ?>
        <li class="sidebar-caption">Admin</li>

        <li class="sidebar-item">
            <a href="<?php echo e(route('admin.approvals.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.approvals.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-check2-circle"></i>
                <span class="pc-mtext">Approval</span>
                <?php $pendingApproval = \App\Models\Transaction::where('status','pending')->where('trans_type','!=','goods_out')->count(); ?>
                <?php if($pendingApproval > 0): ?>
                    <span class="badge bg-danger badge-pill ms-auto"><?php echo e($pendingApproval); ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?php echo e(route('admin.min-stock.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.min-stock.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-bar-chart-fill"></i>
                <span class="pc-mtext">Stok Minimum</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?php echo e(route('admin.purchase-requests.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.purchase-requests.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-file-earmark-text-fill"></i>
                <span class="pc-mtext">Purchase Request</span>
            </a>
        </li>
        <?php endif; ?>

        
        <li class="sidebar-caption">Pengadaan ATK</li>

        <li class="sidebar-item">
            <a href="<?php echo e(route('procurement.requests.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.requests.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-cart3"></i>
                <span class="pc-mtext">Permintaan Saya</span>
            </a>
        </li>

        
        <?php if(auth()->user()->isPurchasing() || auth()->user()->isAdmin()): ?>
        <li class="sidebar-item">
            <a href="<?php echo e(route('admin.purchase-requests.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.purchase-requests.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-bag-check"></i>
                <span class="pc-mtext">PR Siap Diproses</span>
                <?php $qadCount = \App\Models\PurchaseRequest::where('status','sent_to_qad')->count(); ?>
                <?php if($qadCount > 0): ?>
                    <span class="badge bg-primary badge-pill ms-auto"><?php echo e($qadCount); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->role === 'section' || auth()->user()->isSuperAdmin()): ?>
        <li class="sidebar-item">
            <a href="<?php echo e(route('procurement.approvals.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.approvals.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-check2-square"></i>
                <span class="pc-mtext">Approval Kebutuhan GA</span>
                <?php
                    $pendingSection = \App\Models\ProcurementRequest::where('status','pending_section')
                        ->when(!auth()->user()->isSuperAdmin(), fn($q) => $q->whereIn('department_id', auth()->user()->sectionApprovalDepartmentIds()))
                        ->count();
                ?>
                <?php if($pendingSection > 0): ?>
                    <span class="badge bg-danger badge-pill ms-auto"><?php echo e($pendingSection); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->isGa() || auth()->user()->isSuperAdmin()): ?>
        <li class="sidebar-item">
            <a href="<?php echo e(route('ga.review.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('ga.review.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-clipboard2-check"></i>
                <span class="pc-mtext">Review Kebutuhan GA</span>
                <?php $pendingGa = \App\Models\ProcurementRequest::where('status','pending_ga')->count(); ?>
                <?php if($pendingGa > 0): ?>
                    <span class="badge bg-danger badge-pill ms-auto"><?php echo e($pendingGa); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('ga.requests.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('ga.requests.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-file-earmark-text"></i>
                <span class="pc-mtext">Riwayat PR</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('ga.receiving.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('ga.receiving.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-box-seam-fill"></i>
                <span class="pc-mtext">Terima &amp; Distribusi Barang</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('ga.checkout.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('ga.checkout.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-upc-scan"></i>
                <span class="pc-mtext">Serah Terima Barang</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('ga.budget.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('ga.budget.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-cash-coin"></i>
                <span class="pc-mtext">Budget</span>
            </a>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->isDirector() || auth()->user()->isSuperAdmin()): ?>
        <li class="sidebar-item">
            <a href="<?php echo e(route('director.purchase-requests.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('director.purchase-requests.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-award"></i>
                <span class="pc-mtext">Konfirmasi PR (Direktur)</span>
                <?php $pendingDirector = \App\Models\PurchaseRequest::where('source','ga_aggregation')->where('status','sent_to_qad')->count(); ?>
                <?php if($pendingDirector > 0): ?>
                    <span class="badge bg-danger badge-pill ms-auto"><?php echo e($pendingDirector); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->isAdmin()): ?>
        <li class="sidebar-item">
            <a href="#procurementMaster" class="sidebar-link <?php echo e(request()->routeIs('procurement.uom.*') || request()->routeIs('procurement.items.*') || request()->routeIs('procurement.purposes.*') || request()->routeIs('procurement.categories.*') || request()->routeIs('procurement.item-prefixes.*') ? 'active' : ''); ?>"
               data-bs-toggle="collapse" aria-expanded="<?php echo e(request()->routeIs('procurement.uom.*') || request()->routeIs('procurement.items.*') || request()->routeIs('procurement.purposes.*') || request()->routeIs('procurement.categories.*') || request()->routeIs('procurement.item-prefixes.*') ? 'true' : 'false'); ?>">
                <i class="pc-micon bi bi-database-fill"></i>
                <span class="pc-mtext">Master Pengadaan</span>
                <i class="pc-arrow bi bi-chevron-right"></i>
            </a>
            <ul class="pc-submenu collapse list-unstyled <?php echo e(request()->routeIs('procurement.uom.*') || request()->routeIs('procurement.items.*') || request()->routeIs('procurement.purposes.*') || request()->routeIs('procurement.categories.*') || request()->routeIs('procurement.item-prefixes.*') ? 'show' : ''); ?>" id="procurementMaster">
                <li class="sidebar-item">
                    <a href="<?php echo e(route('procurement.uom.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.uom.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-rulers"></i>
                        <span class="pc-mtext">Master UOM</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo e(route('procurement.items.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.items.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-box-seam"></i>
                        <span class="pc-mtext">Item ATK</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo e(route('procurement.purposes.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.purposes.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-list-check"></i>
                        <span class="pc-mtext">Kebutuhan ATK</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo e(route('procurement.categories.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.categories.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-tags"></i>
                        <span class="pc-mtext">Kategori Item</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo e(route('procurement.item-prefixes.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('procurement.item-prefixes.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-hash"></i>
                        <span class="pc-mtext">Prefix Kode Item</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        
        <?php if(auth()->user()->isSuperAdmin()): ?>
        <li class="sidebar-caption">Pengaturan</li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('admin.qad-sync')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.qad-sync') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-arrow-repeat"></i>
                <span class="pc-mtext">Sync QAD</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo e(route('admin.qad-config.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.qad-config.*') ? 'active' : ''); ?>">
                <i class="pc-micon bi bi-diagram-3"></i>
                <span class="pc-mtext">Konfigurasi QAD Dept</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#pengaturanTambahan" class="sidebar-link <?php echo e(request()->routeIs('admin.qad-settings.*') ? 'active' : ''); ?>"
               data-bs-toggle="collapse" aria-expanded="<?php echo e(request()->routeIs('admin.qad-settings.*') ? 'true' : 'false'); ?>">
                <i class="pc-micon bi bi-gear-wide-connected"></i>
                <span class="pc-mtext">Pengaturan Tambahan</span>
                <i class="pc-arrow bi bi-chevron-right"></i>
            </a>
            <ul class="pc-submenu collapse list-unstyled <?php echo e(request()->routeIs('admin.qad-settings.*') ? 'show' : ''); ?>" id="pengaturanTambahan">
                <li class="sidebar-item">
                    <a href="<?php echo e(route('admin.qad-settings.edit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.qad-settings.*') ? 'active' : ''); ?>">
                        <i class="pc-micon bi bi-hdd-network"></i>
                        <span class="pc-mtext">URL QAD (QXI/WSA)</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

    </ul>
</nav>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<header class="pc-header">
    <button class="header-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i class="bi bi-list"></i>
    </button>

    <div class="d-none d-md-block ms-2 text-muted" style="font-size:13px;">
        <i class="bi bi-calendar3 me-1"></i><?php echo e(now()->isoFormat('dddd, D MMMM Y')); ?>

    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
        
        <?php $pendingCount = \App\Models\Transaction::where('status','pending')->where('trans_type','!=','goods_out')->count(); ?>
        <button class="header-notification-btn" data-bs-toggle="dropdown">
            <i class="bi bi-bell"></i>
            <?php if($pendingCount > 0): ?><span class="badge-dot"></span><?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:280px;border-radius:12px;">
            <li><h6 class="dropdown-header">Notifikasi</h6></li>
            <?php if($pendingCount > 0): ?>
            <li>
                <a class="dropdown-item d-flex gap-2 align-items-center" href="<?php echo e(route('admin.approvals.index')); ?>">
                    <span class="badge bg-warning p-2"><i class="bi bi-clock"></i></span>
                    <div>
                        <div class="fw-semibold" style="font-size:13px;"><?php echo e($pendingCount); ?> Transaksi Menunggu Approval</div>
                        <div class="text-muted" style="font-size:11px;">Klik untuk review</div>
                    </div>
                </a>
            </li>
            <?php else: ?>
            <li><p class="text-center text-muted py-3 mb-0" style="font-size:13px;">Tidak ada notifikasi</p></li>
            <?php endif; ?>
        </ul>

        
        <div class="dropdown">
            <button class="btn btn-link p-0 d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                <img src="<?php echo e(auth()->user()->photo ? asset('storage/'.auth()->user()->photo) : asset('assets/images/avatar.png')); ?>"
                     class="header-user-avatar" alt="Avatar">
                <div class="d-none d-md-block text-start">
                    <div style="font-size:13px;font-weight:600;color:#343a40;"><?php echo e(auth()->user()->name); ?></div>
                    <div style="font-size:11px;color:#6c757d;"><?php echo e(auth()->user()->getRoleLabel()); ?></div>
                </div>
                <i class="bi bi-chevron-down text-muted" style="font-size:11px;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="border-radius:12px;min-width:200px;">
                <li>
                    <div class="px-4 py-2">
                        <div class="fw-semibold"><?php echo e(auth()->user()->name); ?></div>
                        <div class="text-muted" style="font-size:12px;">NPK: <?php echo e(auth()->user()->npk); ?></div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="<?php echo e(route('logout')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>


<div class="pc-container">
    <div class="pc-content">
        
        <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        
        <div class="page-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>" class="text-decoration-none">Home</a></li>
                            <?php echo $__env->yieldContent('breadcrumb'); ?>
                        </ol>
                    </nav>
                </div>
                <div><?php echo $__env->yieldContent('page-actions'); ?></div>
            </div>
        </div>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
    // Sidebar toggle
    function toggleSidebar() {
        if (window.innerWidth <= 992) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        }
    }

    // Restore sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 992) {
        document.body.classList.add('sidebar-collapsed');
    }

    // CSRF token for AJAX
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ===== GLOBAL LOADING STATE =====
    // Tampil otomatis saat form native di-submit (kecuali diberi class "no-loading")
    // dan saat ada request AJAX jQuery ($.ajax/$.post/$.get) berjalan.
    const globalLoadingOverlay = document.getElementById('globalLoadingOverlay');
    function showGlobalLoading() { globalLoadingOverlay.classList.add('active'); }
    function hideGlobalLoading() { globalLoadingOverlay.classList.remove('active'); }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.tagName === 'FORM' && !form.classList.contains('no-loading') && !e.defaultPrevented) {
            showGlobalLoading();
        }
    });

    $(document).ajaxStart(showGlobalLoading);
    $(document).ajaxStop(hideGlobalLoading);

    // ===== ATTACHMENT PREVIEW MODAL =====
    function previewAttachment(url, name, mimeType) {
        const body = document.getElementById('attachmentPreviewBody');
        document.getElementById('attachmentPreviewTitle').textContent = name;
        document.getElementById('attachmentPreviewDownload').href = url;

        mimeType = mimeType || '';
        if (mimeType.startsWith('image/')) {
            body.innerHTML = `<img src="${url}" alt="${name}" style="max-width:100%;max-height:70vh;border-radius:8px;">`;
        } else if (mimeType === 'application/pdf') {
            body.innerHTML = `<iframe src="${url}" style="width:100%;height:70vh;border:0;border-radius:8px;"></iframe>`;
        } else {
            body.innerHTML = `
                <div class="py-5">
                    <i class="bi bi-file-earmark-arrow-down" style="font-size:48px;color:#adb5bd;"></i>
                    <p class="mt-3 mb-0 text-muted">Preview tidak didukung untuk tipe file ini.<br>Gunakan tombol "Buka di Tab Baru" untuk melihat/mengunduh.</p>
                </div>`;
        }
        new bootstrap.Modal(document.getElementById('attachmentPreviewModal')).show();
    }

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            let bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 5000);
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/warehouse/resources/views/layouts/app.blade.php ENDPATH**/ ?>