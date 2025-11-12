<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HardCompetencyController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Public
    Route::post('/login', [AuthController::class, 'login']);

    // Private
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN (role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('/import-karyawan', [AdminController::class, 'importKaryawan']);
    Route::get('/karyawan', [AdminController::class, 'listKaryawan']);
    Route::delete('/karyawan/{user}', [AdminController::class, 'deleteKaryawan']);
    Route::delete('/karyawan', [AdminController::class, 'bulkDelete']);
});

/*
|--------------------------------------------------------------------------
| USER-ONLY DATA (Per NIK)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Hard competency (per user/NIK)
    Route::get('/karyawan/{nik}/hard-competencies', [HardCompetencyController::class, 'index']);
});
