<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\SupplyRequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ModificationController;
use App\Http\Controllers\RisController;
use App\Http\Controllers\AccountableItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PropertyReturnController;
use App\Http\Controllers\PhysicalInventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\AuditLogController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/stock-availability', \App\Http\Controllers\StockAvailabilityController::class)->name('stock-availability.index');
    Route::get('/account/session', fn () => redirect()->route('dashboard'))->name('account.session');
    Route::get('/account/password', [LoginController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/account/password', [LoginController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'permission:view_reports'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::get('export', [ReportsController::class, 'export'])->name('export');
});

Route::middleware(['auth', 'permission:view_audit_logs'])->prefix('audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
    Route::get('{auditLog}', [AuditLogController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:supply_administrator'])
    ->get('/authorization/role-check', fn () => response('role authorized'))
    ->name('authorization.role-check');

Route::middleware(['auth', 'permission:manage_inventory'])
    ->get('/authorization/permission-check', fn () => response('permission authorized'))
    ->name('authorization.permission-check');

Route::middleware(['auth', 'permission:manage_users'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
});

Route::middleware(['auth', 'permission:manage_catalog'])->prefix('admin/catalog')->name('admin.catalog.')->group(function () {
    Route::get('/', [CatalogController::class, 'index'])->name('index');
    Route::get('items/lookup', [CatalogController::class, 'lookupItem'])->name('items.lookup');
    Route::get('{type}/create', [CatalogController::class, 'create'])->name('create');
    Route::get('{type}/{id}/edit', [CatalogController::class, 'edit'])->name('edit');
    Route::post('departments', [CatalogController::class, 'storeDepartment'])->name('departments.store');
    Route::post('offices', [CatalogController::class, 'storeOffice'])->name('offices.store');
    Route::post('categories', [CatalogController::class, 'storeCategory'])->name('categories.store');
    Route::post('items', [CatalogController::class, 'storeItem'])->name('items.store');
    Route::put('departments/{department}', [CatalogController::class, 'updateDepartment'])->name('departments.update');
    Route::put('offices/{office}', [CatalogController::class, 'updateOffice'])->name('offices.update');
    Route::put('categories/{category}', [CatalogController::class, 'updateCategory'])->name('categories.update');
    Route::put('items/{item}', [CatalogController::class, 'updateItem'])->name('items.update');
    Route::post('{type}/{id}/activate', [CatalogController::class, 'activate'])->name('activate');
    Route::post('{type}/{id}/deactivate', [CatalogController::class, 'deactivate'])->name('deactivate');
});

Route::middleware(['auth', 'permission:manage_inventory'])->prefix('admin/inventory')->name('admin.inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('receive', [InventoryController::class, 'create'])->name('receive');
    Route::post('receive', [InventoryController::class, 'store'])->name('receive.store');
    Route::get('items/{item}/stock-card', [InventoryController::class, 'stockCard'])->name('stock-card');
    Route::get('expiration', [InventoryController::class, 'expirationReport'])->name('expiration');
    Route::get('expiration/{status}', [InventoryController::class, 'expirationReport'])->name('expiration.status');
});

Route::middleware(['auth', 'permission:manage_inventory'])->prefix('physical-inventories')->name('physical-inventories.')->group(function () {
    Route::get('/', [PhysicalInventoryController::class, 'index'])->name('index');
    Route::get('create', [PhysicalInventoryController::class, 'create'])->name('create');
    Route::post('/', [PhysicalInventoryController::class, 'store'])->name('store');
    Route::get('{physicalInventory}', [PhysicalInventoryController::class, 'show'])->name('show');
    Route::post('{physicalInventory}/complete', [PhysicalInventoryController::class, 'complete'])->name('complete');
    Route::post('{physicalInventory}/cancel', [PhysicalInventoryController::class, 'cancel'])->name('cancel');
});

Route::middleware(['auth', 'permission:submit_supply_requests'])->group(function () {
    Route::resource('requests', SupplyRequestController::class)
        ->parameters(['requests' => 'supplyRequest'])
        ->only(['index', 'create', 'store', 'show']);
});

Route::middleware(['auth', 'permission:approve_supply_requests'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/', [ApprovalController::class, 'index'])->name('index');
    Route::get('{supplyRequest}', [ApprovalController::class, 'show'])->name('show');
    Route::post('{supplyRequest}/decision', [ApprovalController::class, 'decide'])->name('decide');
});

Route::middleware('auth')->prefix('modifications')->name('modifications.')->group(function () {
    Route::get('/', [ModificationController::class, 'index'])->name('index');
    Route::get('{supplyRequest}', [ModificationController::class, 'edit'])->name('edit');
    Route::put('{supplyRequest}', [ModificationController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'permission:issue_supplies'])->prefix('ris')->name('ris.')->group(function () {
    Route::get('/', [RisController::class, 'index'])->name('index');
    Route::get('requests/{supplyRequest}/create', [RisController::class, 'create'])->name('create');
    Route::post('requests/{supplyRequest}', [RisController::class, 'store'])->name('store');
    Route::post('{ris}/release', [RisController::class, 'release'])->name('release');
    Route::get('{ris}', [RisController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'permission:manage_accountable_items'])->prefix('accountable-items')->name('accountable-items.')->group(function () {
    Route::get('/', [AccountableItemController::class, 'index'])->name('index');
    Route::get('create', [AccountableItemController::class, 'create'])->name('create');
    Route::post('/', [AccountableItemController::class, 'store'])->name('store');
    Route::post('{accountableItem}/assign', [AccountableItemController::class, 'assign'])->name('assign');
    Route::post('{accountableItem}/transfer', [AccountableItemController::class, 'transfer'])->name('transfer');
    Route::get('{accountableItem}', [AccountableItemController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'permission:manage_accountable_items'])->prefix('property-returns')->name('property-returns.')->group(function () {
    Route::get('/', [PropertyReturnController::class, 'index'])->name('index');
    Route::get('create', [PropertyReturnController::class, 'create'])->name('create');
    Route::post('/', [PropertyReturnController::class, 'store'])->name('store');
    Route::get('{propertyReturn}', [PropertyReturnController::class, 'show'])->name('show');
    Route::post('{propertyReturn}/complete', [PropertyReturnController::class, 'complete'])->name('complete');
    Route::post('{propertyReturn}/cancel', [PropertyReturnController::class, 'cancel'])->name('cancel');
});
