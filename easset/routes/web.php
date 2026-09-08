<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return redirect('/dashboard');
});
Route::post('/sync_qad_asset', [AssetController::class, 'store'])->name('syncqad');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/items/{encryptedId}', [ItemController::class, 'show'])->name('items.show');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware(['admin'])->group(function () {
        Route::get('/transactions/fullsto', [TransactionController::class, 'exportSTO'])->name('transactions.fullsto');
        Route::get('/transactions/dailyreport', [TransactionController::class, 'dailyreport'])->name('transactions.dailyreport');
        Route::get('/transactions/dailyreportpage', [TransactionController::class, 'dailyreportpage'])->name('transactions.dailyreportpage');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
    });

    Route::middleware(['permission:perform-sto'])->group(function () {
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    });

    Route::middleware(['permission:manage-locations'])->group(function () {
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    });

    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware(['permission:manage-departments'])->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/items/departments', [ItemController::class, 'departmentsPage'])->name('items.departments');
        Route::get('/items/export/departments', [ItemController::class, 'exportDepartments'])->name('items.export.departments');
        Route::post('/items/import/departments', [ItemController::class, 'importDepartments'])->name('items.import.departments');
    });

    Route::middleware(['permission:manage-jabatan'])->group(function () {
        Route::get('/jabatans', [JabatanController::class, 'index'])->name('jabatans.index');
        Route::post('/jabatans', [JabatanController::class, 'store'])->name('jabatans.store');
        Route::put('/jabatans/{jabatan}', [JabatanController::class, 'update'])->name('jabatans.update');
        Route::delete('/jabatans/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatans.destroy');
    });

    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
    
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/export/url', [ItemController::class, 'exportUrl'])->name('items.export.url');
});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
