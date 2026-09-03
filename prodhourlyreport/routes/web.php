<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionLogController;
use App\Http\Controllers\ProductModelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    Route::get('/entry', [ProductionLogController::class, 'create'])->name('entry.create');
    Route::post('/entry', [ProductionLogController::class, 'store'])->name('entry.store');

    Route::get('/catalog/lines', [CatalogController::class, 'lines'])->name('catalog.lines');
    Route::get('/catalog/models', [CatalogController::class, 'models'])->name('catalog.models');
    Route::get('/catalog/products', [CatalogController::class, 'products'])->name('catalog.products');

    Route::post('/lines/sync-qad', [LineController::class, 'sync'])
        ->middleware('throttle:5,1')
        ->name('lines.sync');
    Route::resource('lines', LineController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('product-models', ProductModelController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('/products/sync-qad', [ProductController::class, 'sync'])
        ->middleware('throttle:3,1')
        ->name('products.sync');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
