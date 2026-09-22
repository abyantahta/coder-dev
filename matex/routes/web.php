<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\QadItemController;
use App\Http\Controllers\Admin\QadSupplierController;
use App\Http\Controllers\Admin\SupplierDisciplineController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReceivingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:purchasing,admin,supplier_rm')->group(function () {
        Route::get('forecasts', [ForecastController::class, 'index'])->name('forecasts.index');
        Route::get('forecasts/create', [ForecastController::class, 'create'])
            ->middleware('role:purchasing,admin')
            ->name('forecasts.create');
        Route::post('forecasts', [ForecastController::class, 'store'])
            ->middleware('role:purchasing,admin')
            ->name('forecasts.store');
        Route::get('forecasts/{forecast}', [ForecastController::class, 'show'])->name('forecasts.show');
        Route::get('forecasts/{forecast}/edit', [ForecastController::class, 'edit'])
            ->middleware('role:purchasing,admin')
            ->name('forecasts.edit');
        Route::post('forecasts/{forecast}', [ForecastController::class, 'update'])
            ->middleware('role:purchasing,admin')
            ->name('forecasts.update');
        Route::delete('forecasts/{forecast}', [ForecastController::class, 'destroy'])
            ->middleware('role:purchasing,admin')
            ->name('forecasts.destroy');
        Route::get('forecasts/{forecast}/download', [ForecastController::class, 'download'])
            ->name('forecasts.download');
    });

    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['destroy']);
    Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])
        ->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchase_order}/confirm-rm', [PurchaseOrderController::class, 'confirmRm'])
        ->name('purchase-orders.confirm-rm');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])
        ->name('purchase-orders.reject');

    Route::get('delivery-notes', [DeliveryNoteController::class, 'index'])->name('delivery-notes.index');
    Route::get('delivery-notes/{delivery_note}', [DeliveryNoteController::class, 'show'])->name('delivery-notes.show');
    Route::get('delivery-notes/{delivery_note}/print', [DeliveryNoteController::class, 'print'])->name('delivery-notes.print');
    Route::post('delivery-notes/{delivery_note}/confirm-shipment', [DeliveryNoteController::class, 'confirmShipment'])
        ->name('delivery-notes.confirm-shipment');
    Route::post('delivery-notes/{delivery_note}/confirm-ohp', [DeliveryNoteController::class, 'confirmOhp'])
        ->name('delivery-notes.confirm-ohp');
    Route::post('delivery-notes/{delivery_note}/delivery-date', [DeliveryNoteController::class, 'updateDeliveryDate'])
        ->name('delivery-notes.update-delivery-date');
    Route::post('delivery-schedules/{delivery_schedule}/generate-dn', [DeliveryNoteController::class, 'generateDn'])
        ->name('delivery-schedules.generate-dn');

    Route::get('receivings', [ReceivingController::class, 'index'])
        ->middleware('role:ppic,admin')
        ->name('receivings.index');
    Route::post('receivings/{delivery_note}', [ReceivingController::class, 'store'])
        ->middleware('role:ppic,admin')
        ->name('receivings.store');

    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');

    // Master data: Purchasing (Mbak Dita) + Admin. Users kini juga bisa
    // dikelola Purchasing (kecuali akun Admin — lihat UserController).
    Route::middleware('role:admin,purchasing')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('companies', CompanyController::class)->except(['show']);
        Route::resource('items', ItemController::class)->except(['show']);
        Route::get('discipline', [SupplierDisciplineController::class, 'index'])
            ->name('discipline.index');

        Route::prefix('qad-items')->name('qad-items.')->group(function () {
            Route::get('/', [QadItemController::class, 'index'])->name('index');
            Route::post('/sync', [QadItemController::class, 'sync'])
                ->middleware('throttle:3,1')
                ->name('sync');
        });

        Route::prefix('qad-suppliers')->name('qad-suppliers.')->group(function () {
            Route::get('/', [QadSupplierController::class, 'index'])->name('index');
            Route::post('/sync', [QadSupplierController::class, 'sync'])
                ->middleware('throttle:3,1')
                ->name('sync');
            Route::patch('/{qad_supplier}/category', [QadSupplierController::class, 'updateCategory'])
                ->name('update-category');
        });

        // Users: Admin full akses; Purchasing bisa kelola PPIC/Purchasing/
        // Supplier RM/Supplier OHP tapi tidak bisa menyentuh akun Admin
        // (ditegakkan di StoreUserRequest/UpdateUserRequest/UserController).
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
