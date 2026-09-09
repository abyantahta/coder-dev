<?php

use App\Http\Controllers\Admin\DeptAdminController;
use App\Http\Controllers\Admin\ItemMasterController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Admin\UnitManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\QaController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Work Orders
    Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::get('/work-orders/create', [WorkOrderController::class, 'create'])->name('work-orders.create');
    Route::post('/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');

    // Approval engine (new dynamic system)
    Route::post('/work-orders/{workOrder}/approval/advance', [ApprovalController::class, 'advance'])->name('approval.advance');
    Route::post('/work-orders/{workOrder}/approval/reject',  [ApprovalController::class, 'reject'])->name('approval.reject');
    Route::post('/work-orders/{workOrder}/approval/forward', [ApprovalController::class, 'forward'])->name('approval.forward');
    Route::post('/work-orders/{workOrder}/approval/cancel',  [ApprovalController::class, 'cancel'])->name('approval.cancel');

    // WO Actions (legacy — kept for backward compat, no longer linked from UI)
    Route::post('/work-orders/{workOrder}/accept', [WorkOrderController::class, 'accept'])->name('work-orders.accept');
    Route::post('/work-orders/{workOrder}/reject', [WorkOrderController::class, 'reject'])->name('work-orders.reject');
    Route::post('/work-orders/{workOrder}/assign-group', [WorkOrderController::class, 'assignGroup'])->name('work-orders.assign-group');
    Route::post('/work-orders/{workOrder}/assign-member', [WorkOrderController::class, 'assignMember'])->name('work-orders.assign-member');
    Route::post('/work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
    Route::post('/work-orders/{workOrder}/review', [WorkOrderController::class, 'review'])->name('work-orders.review');
    Route::post('/work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');
    Route::post('/work-orders/{workOrder}/forward', [WorkOrderController::class, 'forward'])->name('work-orders.forward');
    Route::post('/work-orders/{workOrder}/check-parts', [WorkOrderController::class, 'checkParts'])->name('work-orders.check-parts');

    // Warehouse MTC — dedicated warehouse dashboard, role-gated
    Route::middleware('role:warehouse_mtc,section_head')->prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
    });

    // Part-order management — dedicated warehouse staff manage any order;
    // a WO's own assigned staffer can manage their own (self-service PR,
    // e.g. GA's material_check step). Authorized per-request in the
    // controller (WarehouseController::canManageOrder), not by role here.
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::post('/work-orders/{workOrder}/create-pr', [WarehouseController::class, 'createPr'])->name('create-pr');
        Route::post('/orders/{partOrder}/receive', [WarehouseController::class, 'receive'])->name('receive');
        Route::get('/orders/{partOrder}', [WarehouseController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{partOrder}/lines', [WarehouseController::class, 'addLine'])->name('orders.add-line');
        Route::delete('/orders/{partOrder}/lines/{line}', [WarehouseController::class, 'removeLine'])->name('orders.remove-line');
    });

    // Item Master Data (synced item catalog that Warehouse PR lines are built from)
    Route::middleware('role:warehouse_mtc,section_head')->prefix('items')->name('items.')->group(function () {
        Route::get('/', [ItemMasterController::class, 'index'])->name('index');
        Route::post('/sync', [ItemMasterController::class, 'sync'])->middleware('throttle:3,1')->name('sync');
    });

    // QA Actions
    Route::middleware('role:qa_group_head,qa_section_head')->prefix('qa')->name('qa.')->group(function () {
        Route::post('/work-orders/{workOrder}/accept',        [QaController::class, 'accept'])->name('accept');
        Route::post('/work-orders/{workOrder}/reject',        [QaController::class, 'reject'])->name('reject');
        Route::post('/work-orders/{workOrder}/forward',       [QaController::class, 'forward'])->name('forward');
        Route::post('/work-orders/{workOrder}/assign-member', [QaController::class, 'assignMember'])->name('assign-member');
        Route::post('/work-orders/{workOrder}/cancel',        [QaController::class, 'cancel'])->name('cancel');
    });

    // Performance (maintenance staff only)
    Route::get('/performance', [PerformanceController::class, 'index'])
        ->name('performance.index')
        ->middleware('role:section_head,unit_head,group_head');

    // Performance QA
    Route::get('/performance/qa', [QaController::class, 'performance'])
        ->name('performance.qa')
        ->middleware('role:qa_group_head,qa_section_head');

    // Admin — User Management (Section Head MTC/QA/GA, each scoped to own department)
    Route::middleware('role:section_head,qa_section_head,ga_section_head')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    // Dept Admin — Superuser management of their own department
    Route::middleware('dept.superuser')->prefix('dept-admin')->name('dept-admin.')->group(function () {
        Route::get('/', [DeptAdminController::class, 'index'])->name('index');

        // Roles
        Route::get('/roles', [DeptAdminController::class, 'roles'])->name('roles.index');
        Route::post('/roles', [DeptAdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [DeptAdminController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [DeptAdminController::class, 'destroyRole'])->name('roles.destroy');

        // Categories
        Route::get('/categories', [DeptAdminController::class, 'categories'])->name('categories.index');
        Route::post('/categories', [DeptAdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [DeptAdminController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [DeptAdminController::class, 'destroyCategory'])->name('categories.destroy');

        // Approval Steps
        Route::get('/approval-steps', [DeptAdminController::class, 'approvalSteps'])->name('steps.index');
        Route::post('/approval-steps', [DeptAdminController::class, 'storeStep'])->name('steps.store');
        Route::put('/approval-steps/reorder', [DeptAdminController::class, 'reorderSteps'])->name('steps.reorder');
        Route::put('/approval-steps/{step}', [DeptAdminController::class, 'updateStep'])->name('steps.update');
        Route::delete('/approval-steps/{step}', [DeptAdminController::class, 'destroyStep'])->name('steps.destroy');

        // QAD Config (site/buyer/approver codes for SDI_CreatePR)
        Route::get('/qad-config', [DeptAdminController::class, 'qadConfig'])->name('qad-config.index');
        Route::put('/qad-config', [DeptAdminController::class, 'updateQadConfig'])->name('qad-config.update');
    });

    // Super Admin — IT global admin
    Route::middleware(['superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::post('/departments', [SuperAdminController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}', [SuperAdminController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [SuperAdminController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::get('/users', [SuperAdminController::class, 'users'])->name('users.index');
        Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [SuperAdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser'])->name('users.destroy');
    });

    // Admin — Unit & Group management (Section Head MTC only)
    Route::middleware('role:section_head')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/units', [UnitManagementController::class, 'index'])->name('units.index');
        Route::post('/units', [UnitManagementController::class, 'store'])->name('units.store');
        Route::put('/units/{unit}', [UnitManagementController::class, 'update'])->name('units.update');
        Route::delete('/units/{unit}', [UnitManagementController::class, 'destroy'])->name('units.destroy');
        Route::post('/units/{unit}/groups', [UnitManagementController::class, 'storeGroup'])->name('units.groups.store');
        Route::delete('/groups/{group}', [UnitManagementController::class, 'destroyGroup'])->name('groups.destroy');
    });
});
