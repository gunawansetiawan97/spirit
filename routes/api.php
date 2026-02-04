<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PermissionController;

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
});
