<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;

// ========== AUTH ==========
Route::prefix('auth')->group(function () {
    // Public
    Route::post('/login', [AuthController::class, 'login']);

    // Butuh token, TIDAK perlu role admin
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ========== ADMIN ==========
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/import-karyawan', [AdminController::class, 'importKaryawan']);
    Route::get('/admin/karyawan', [AdminController::class, 'listKaryawan']);
    Route::delete('/admin/karyawan/{user}', [AdminController::class, 'deleteKaryawan']);
    Route::delete('/admin/karyawan', [AdminController::class, 'bulkDelete']);
});
