<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HardCompetencyController;
use App\Http\Controllers\Api\DashboardKaryawanController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
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
    Route::post('/import-hard-competencies', [AdminController::class, 'importHardCompetencies']);

    // ✅ Admin lihat semua hard competency
    Route::get('/hard-competencies', [HardCompetencyController::class, 'adminIndex']);
});

/*
|--------------------------------------------------------------------------
| USER-ONLY DATA (Per NIK)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // ✅ Karyawan (dan admin) lihat hard competency by NIK
    Route::get('/karyawan/{nik}/hard-competencies', [HardCompetencyController::class, 'indexSelf']);
    Route::get('/dashboard/karyawan/summary', [DashboardKaryawanController::class, 'summary']);
});
