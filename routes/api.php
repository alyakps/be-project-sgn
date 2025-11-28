<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HardCompetencyController;
use App\Http\Controllers\Api\DashboardKaryawanController;
use App\Http\Controllers\Api\SoftCompetencyController;
use App\Http\Controllers\Api\EmployeeProfileController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

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
    Route::get('/hard-competencies', [HardCompetencyController::class, 'adminIndex']);
    Route::get('/karyawan/{nik}/hard-competencies', [HardCompetencyController::class, 'adminByNik']);
    Route::get('/soft-competencies', [SoftCompetencyController::class, 'adminIndex']);
    Route::get('/karyawan/{nik}/soft-competencies', [SoftCompetencyController::class, 'adminByNik']);
    Route::post('/import-soft-competencies', [AdminController::class, 'importSoftCompetencies']);
    Route::get('/employee-profiles', [EmployeeProfileController::class, 'adminIndex']);
    Route::get('/karyawan/{nik}/profile', [EmployeeProfileController::class, 'adminShowByNik']);
});

/*
|--------------------------------------------------------------------------
| KARYAWAN (role:karyawan)
|--------------------------------------------------------------------------
*/
// 🟢 Karyawan cuma bisa lihat data miliknya sendiri (pakai NIK dari token)
Route::middleware(['auth:sanctum', 'role:karyawan'])->group(function () {
    Route::get('/karyawan/hard-competencies', [HardCompetencyController::class, 'indexSelf']);
    Route::get('/dashboard/karyawan/summary', [DashboardKaryawanController::class, 'summary']);
    Route::get('/karyawan/soft-competencies', [SoftCompetencyController::class, 'indexSelf']);
    Route::get('/karyawan/profile', [EmployeeProfileController::class, 'showSelf']);
    Route::post('/karyawan/profile', [EmployeeProfileController::class, 'updateSelf']);
});
