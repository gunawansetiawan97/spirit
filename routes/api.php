<?php

use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AccountTypeController;
use App\Http\Controllers\Api\AccountGroupController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\CoaMappingController;
use App\Http\Controllers\Api\NumberSequenceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductBrandController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\AdjustmentTypeController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\StockLedgerController;
use App\Http\Controllers\Api\SupplierController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/switch-branch', [AuthController::class, 'switchBranch']);
    Route::get('/branches/all', [AuthController::class, 'branches']);

    // Branches
    Route::get('/branches/list', [BranchController::class, 'all']);
    Route::apiResource('/branches', BranchController::class);

    // Roles
    Route::get('/roles/list', [RoleController::class, 'all']);
    Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions']);
    Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);
    Route::apiResource('/roles', RoleController::class);

    // Users
    Route::apiResource('/users', UserController::class);

    // Permissions
    Route::get('/permissions/tree', [PermissionController::class, 'tree']);
    Route::get('/permissions/parents', [PermissionController::class, 'parents']);
    Route::apiResource('/permissions', PermissionController::class);

    
    // Account Types
    Route::get('/account-types/list', [AccountTypeController::class, 'all']);
    Route::apiResource('/account-types', AccountTypeController::class);

    // Account Groups
    Route::get('/account-groups/list', [AccountGroupController::class, 'all']);
    Route::apiResource('/account-groups', AccountGroupController::class);

    // Chart of Accounts
    Route::get('/chart-of-accounts/list', [ChartOfAccountController::class, 'all']);
    Route::apiResource('/chart-of-accounts', ChartOfAccountController::class);

    // COA Mappings
    Route::apiResource('/coa-mappings', CoaMappingController::class);

    
    
    // Units
    Route::get('/units/list', [UnitController::class, 'all']);
    Route::apiResource('/units', UnitController::class);

    // Number Sequences
    Route::get('/number-sequences/list', [NumberSequenceController::class, 'all']);
    Route::get('/number-sequences/peek-by-code', [NumberSequenceController::class, 'peekByCode']);
    Route::post('/number-sequences/preview-format', [NumberSequenceController::class, 'previewFormat']);
    Route::post('/number-sequences/{number_sequence}/peek', [NumberSequenceController::class, 'peek']);
    Route::post('/number-sequences/{number_sequence}/generate', [NumberSequenceController::class, 'generate']);
    Route::apiResource('/number-sequences', NumberSequenceController::class);

    // Product Categories
    Route::get('/product-categories/list', [ProductCategoryController::class, 'all']);
    Route::apiResource('/product-categories', ProductCategoryController::class);

    // Product Brands
    Route::get('/product-brands/list', [ProductBrandController::class, 'all']);
    Route::apiResource('/product-brands', ProductBrandController::class);

    // Products
    Route::get('/products/list', [ProductController::class, 'all']);
    Route::apiResource('/products', ProductController::class);

    // Warehouses
    Route::get('/warehouses/list', [WarehouseController::class, 'all']);
    Route::apiResource('/warehouses', WarehouseController::class);

    // Adjustment Types
    Route::get('/adjustment-types/list', [AdjustmentTypeController::class, 'all']);
    Route::apiResource('/adjustment-types', AdjustmentTypeController::class);

    // Stock Adjustments
    Route::post('/stock-adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve']);
    Route::post('/stock-adjustments/{stock_adjustment}/disapprove', [StockAdjustmentController::class, 'disapprove']);
    Route::apiResource('/stock-adjustments', StockAdjustmentController::class);

    // Stock Transfers
    Route::post('/stock-transfers/{stock_transfer}/approve', [StockTransferController::class, 'approve']);
    Route::post('/stock-transfers/{stock_transfer}/disapprove', [StockTransferController::class, 'disapprove']);
    Route::apiResource('/stock-transfers', StockTransferController::class);

    // Stock Ledger
    Route::get('/stock-ledgers/batches', [StockLedgerController::class, 'batches']);

    // Suppliers
    Route::get('/suppliers/list', [SupplierController::class, 'all']);
    Route::apiResource('/suppliers', SupplierController::class);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});
