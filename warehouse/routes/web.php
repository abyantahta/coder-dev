<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentQadConfigController;
use App\Http\Controllers\DirectorPurchaseRequestController;
use App\Http\Controllers\GaBudgetController;
use App\Http\Controllers\GaCheckoutController;
use App\Http\Controllers\GaReceivingController;
use App\Http\Controllers\GaReviewController;
use App\Http\Controllers\GoodsInController;
use App\Http\Controllers\GoodsOutController;
use App\Http\Controllers\GoodsReturnController;
use App\Http\Controllers\ItemCodePrefixController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MemoTransactionController;
use App\Http\Controllers\MyTransactionController;
use App\Http\Controllers\MinimumStockController;
use App\Http\Controllers\ProcurementApprovalController;
use App\Http\Controllers\ProcurementCategoryController;
use App\Http\Controllers\QadSettingController;
use App\Http\Controllers\ProcurementItemController;
use App\Http\Controllers\ProcurementRequestController;
use App\Http\Controllers\ProcurementPurposeController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\UomController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ===== AUTH ROUTES =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/scan-qr', [AuthController::class, 'scanQrForm'])->name('auth.scan-qr');
    Route::post('/scan-qr', [AuthController::class, 'scanQr'])->name('auth.scan-qr.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ===== MASTER DATA =====
    Route::prefix('master')->name('master.')->group(function () {

        // Master Data (admin + superadmin only)
        Route::middleware('role:admin')->group(function () {
            Route::resource('departments', DepartmentController::class)->except(['show']);

            Route::resource('users', UserController::class)->except(['show']);
            Route::post('users/{user}/regenerate-qr', [UserController::class, 'regenerateQr'])->name('users.regenerate-qr');
            Route::get('users/{user}/qr', [UserController::class, 'qrImage'])->name('users.qr');
            Route::get('users/{user}/qr/download', [UserController::class, 'qrDownload'])->name('users.qr.download');

            Route::get('items', [ItemController::class, 'index'])->name('items.index');
            Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
            Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
            Route::post('items/sync', [ItemController::class, 'syncFromQAD'])->name('items.sync');
        });
    });

    // ===== TRANSACTIONS =====
    Route::prefix('transactions')->name('transactions.')->group(function () {

        // Goods Out (all roles)
        Route::prefix('goods-out')->name('goods-out.')->group(function () {
            Route::get('/', [GoodsOutController::class, 'index'])->name('index');
            Route::get('/scan', [GoodsOutController::class, 'scan'])->name('scan');
            Route::get('/item-info', [GoodsOutController::class, 'getItemInfo'])->name('item-info');
            Route::post('/', [GoodsOutController::class, 'store'])->name('store');
            Route::get('/{transaction}', [GoodsOutController::class, 'show'])->name('show');
        });

        // Goods In — semua role bisa lihat daftar
        Route::prefix('goods-in')->name('goods-in.')->group(function () {
            Route::get('/', [GoodsInController::class, 'index'])->name('index');

            // Admin only: input penerimaan dari QAD
            Route::middleware('role:admin')->group(function () {
                Route::get('/create', [GoodsInController::class, 'create'])->name('create');
                Route::get('/qad-receipts', [GoodsInController::class, 'getQADReceipts'])->name('qad-receipts');
                Route::get('/receipt-detail', [GoodsInController::class, 'getReceiptDetail'])->name('receipt-detail');
                Route::post('/', [GoodsInController::class, 'store'])->name('store');
            });
        });

        // Memo Transactions (admin only)
        Route::prefix('memo')->name('memo.')->middleware('role:admin')->group(function () {
            Route::get('/', [MemoTransactionController::class, 'index'])->name('index');
            Route::post('/', [MemoTransactionController::class, 'store'])->name('store');
        });

        // Goods Return (all roles)
        Route::prefix('goods-return')->name('goods-return.')->group(function () {
            Route::get('/scan', [GoodsReturnController::class, 'scan'])->name('scan');
            Route::get('/item-info', [GoodsReturnController::class, 'getItemInfo'])->name('item-info');
            Route::post('/', [GoodsReturnController::class, 'store'])->name('store');
        });

        // My Transactions (all roles)
        Route::prefix('my-transactions')->name('my-transactions.')->group(function () {
            Route::get('/', [MyTransactionController::class, 'index'])->name('index');
            Route::get('/{transaction}', [MyTransactionController::class, 'show'])->name('show');
        });
    });

    // ===== ADMIN ROUTES =====
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {

        // Approvals
        Route::prefix('approvals')->name('approvals.')->group(function () {
            Route::get('/', [ApprovalController::class, 'index'])->name('index');
            Route::get('/{transaction}', [ApprovalController::class, 'show'])->name('show');
            Route::post('/{transaction}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('/{transaction}/reject', [ApprovalController::class, 'reject'])->name('reject');
        });

        // Minimum Stock
        Route::prefix('min-stock')->name('min-stock.')->group(function () {
            Route::get('/', [MinimumStockController::class, 'index'])->name('index');
            Route::put('/{item}', [MinimumStockController::class, 'update'])->name('update');
            Route::post('/generate-pr', [MinimumStockController::class, 'generatePR'])->name('generate-pr');
        });

        // QAD Sync page
        Route::get('/qad-sync', function () {
            return view('admin.qad-sync');
        })->name('qad-sync');

        // Konfigurasi routing QAD per departemen (site/buyer/approver/dst)
        Route::get('/qad-config', [DepartmentQadConfigController::class, 'index'])->name('qad-config.index');
        Route::put('/qad-config/{department}', [DepartmentQadConfigController::class, 'update'])->name('qad-config.update');

        // URL koneksi QAD (QXI/WSA) — bisa diubah tanpa edit .env
        Route::get('/qad-settings', [QadSettingController::class, 'edit'])->name('qad-settings.edit');
        Route::put('/qad-settings', [QadSettingController::class, 'update'])->name('qad-settings.update');
    });

    // Purchase Requests (admin + purchasing — purchasing memproses PR yang sudah sent_to_qad jadi PO di QAD)
    Route::prefix('admin/purchase-requests')->name('admin.purchase-requests.')->middleware('role:admin,purchasing')->group(function () {
        Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
        Route::post('/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('submit');
        Route::patch('/{purchaseRequest}/status', [PurchaseRequestController::class, 'updateStatus'])->name('update-status');
        Route::get('/{purchaseRequest}/pdf', [PurchaseRequestController::class, 'printPdf'])->name('pdf');
    });

    // ===== GENERAL AFFAIR (review, terima & distribusi, serah-terima) =====
    Route::prefix('ga')->name('ga.')->middleware('role:ga,superadmin')->group(function () {
        Route::prefix('review')->name('review.')->group(function () {
            Route::get('/', [GaReviewController::class, 'index'])->name('index');
            Route::post('/{procurementRequest}/approve', [GaReviewController::class, 'approve'])->name('approve');
            Route::post('/bulk-approve', [GaReviewController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/{procurementRequest}/reject', [GaReviewController::class, 'reject'])->name('reject');
        });

        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [GaReviewController::class, 'requestsIndex'])->name('index');
            Route::get('/{purchaseRequest}', [GaReviewController::class, 'requestsShow'])->name('show');
            Route::post('/{purchaseRequest}/retry-send', [GaReviewController::class, 'retrySend'])->name('retry-send');
            Route::get('/{purchaseRequest}/revise', [GaReviewController::class, 'reviseForm'])->name('revise');
            Route::post('/{purchaseRequest}/revise', [GaReviewController::class, 'reviseSubmit'])->name('revise.submit');
        });

        Route::prefix('budget')->name('budget.')->group(function () {
            Route::get('/', [GaBudgetController::class, 'index'])->name('index');
            Route::post('/', [GaBudgetController::class, 'store'])->name('store');
            Route::put('/{budget}', [GaBudgetController::class, 'update'])->name('update');
            Route::post('/{budget}/topup', [GaBudgetController::class, 'topup'])->name('topup');
        });

        Route::prefix('receiving')->name('receiving.')->group(function () {
            Route::get('/', [GaReceivingController::class, 'index'])->name('index');
            Route::get('/{purchaseRequest}', [GaReceivingController::class, 'create'])->name('create');
            Route::post('/{purchaseRequest}', [GaReceivingController::class, 'store'])->name('store');
        });

        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::get('/', [GaCheckoutController::class, 'selectDepartment'])->name('index');
            Route::get('/scan', [GaCheckoutController::class, 'scan'])->name('scan');
            Route::post('/lookup', [GaCheckoutController::class, 'lookup'])->name('lookup');
            Route::get('/{employee}', [GaCheckoutController::class, 'show'])->name('show');
            Route::post('/{employee}', [GaCheckoutController::class, 'store'])->name('store');
        });
    });

    // ===== DIRECTOR: approval PR hasil agregasi GA =====
    Route::prefix('director/purchase-requests')->name('director.purchase-requests.')->middleware('role:director,superadmin')->group(function () {
        Route::get('/', [DirectorPurchaseRequestController::class, 'index'])->name('index');
        Route::get('/{purchaseRequest}', [DirectorPurchaseRequestController::class, 'show'])->name('show');
        Route::post('/{purchaseRequest}/approve', [DirectorPurchaseRequestController::class, 'approve'])->name('approve');
        Route::post('/{purchaseRequest}/reject', [DirectorPurchaseRequestController::class, 'reject'])->name('reject');
    });

    // ===== PROCUREMENT (semua role login) =====
    Route::prefix('procurement')->name('procurement.')->group(function () {

        // Master UOM & Item (admin & superadmin)
        Route::middleware('role:admin')->group(function () {
            Route::get('uom', [UomController::class, 'index'])->name('uom.index');
            Route::post('uom', [UomController::class, 'store'])->name('uom.store');
            Route::put('uom/{uom}', [UomController::class, 'update'])->name('uom.update');
            Route::delete('uom/{uom}', [UomController::class, 'destroy'])->name('uom.destroy');

            Route::get('purposes', [ProcurementPurposeController::class, 'index'])->name('purposes.index');
            Route::post('purposes', [ProcurementPurposeController::class, 'store'])->name('purposes.store');
            Route::put('purposes/{procurementPurpose}', [ProcurementPurposeController::class, 'update'])->name('purposes.update');
            Route::delete('purposes/{procurementPurpose}', [ProcurementPurposeController::class, 'destroy'])->name('purposes.destroy');

            Route::get('categories', [ProcurementCategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [ProcurementCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{procurementCategory}', [ProcurementCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{procurementCategory}', [ProcurementCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('item-prefixes/next-code', [ItemCodePrefixController::class, 'nextCode'])->name('item-prefixes.next-code');
            Route::get('item-prefixes', [ItemCodePrefixController::class, 'index'])->name('item-prefixes.index');
            Route::post('item-prefixes', [ItemCodePrefixController::class, 'store'])->name('item-prefixes.store');
            Route::put('item-prefixes/{itemCodePrefix}', [ItemCodePrefixController::class, 'update'])->name('item-prefixes.update');
            Route::delete('item-prefixes/{itemCodePrefix}', [ItemCodePrefixController::class, 'destroy'])->name('item-prefixes.destroy');

            Route::get('items', [ProcurementItemController::class, 'index'])->name('items.index');
            Route::get('items/create', [ProcurementItemController::class, 'create'])->name('items.create');
            Route::post('items', [ProcurementItemController::class, 'store'])->name('items.store');
            Route::get('items/template', [ProcurementItemController::class, 'downloadTemplate'])->name('items.template');
            Route::post('items/import', [ProcurementItemController::class, 'import'])->name('items.import');
            Route::get('items/{procurementItem}/edit', [ProcurementItemController::class, 'edit'])->name('items.edit');
            Route::put('items/{procurementItem}', [ProcurementItemController::class, 'update'])->name('items.update');

            // Semua request (view only admin)
            Route::get('requests/all', [ProcurementRequestController::class, 'allRequests'])->name('requests.all');
        });

        // Search & browse item (semua role)
        Route::get('items/search', [ProcurementItemController::class, 'search'])->name('items.search');
        Route::get('items/categories', [ProcurementItemController::class, 'categories'])->name('items.categories');

        // Permintaan pengadaan (semua role)
        Route::get('requests', [ProcurementRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [ProcurementRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [ProcurementRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{procurementRequest}', [ProcurementRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{procurementRequest}/submit', [ProcurementRequestController::class, 'submit'])->name('requests.submit');
        Route::delete('requests/attachments/{attachment}', [ProcurementRequestController::class, 'destroyAttachment'])->name('requests.attachments.destroy');

        // Approval Section/Dept Head (dept-scoped, dicek juga di canBeApprovedBy())
        Route::middleware('role:section,superadmin')->group(function () {
            Route::get('approvals', [ProcurementApprovalController::class, 'index'])->name('approvals.index');
            Route::post('approvals/{procurementRequest}/approve', [ProcurementApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('approvals/{procurementRequest}/reject', [ProcurementApprovalController::class, 'reject'])->name('approvals.reject');
        });
    });
});
