<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HardCompetencyController;
use App\Http\Controllers\Api\DashboardKaryawanController;
use App\Http\Controllers\Api\SoftCompetencyController;
use App\Http\Controllers\Api\EmployeeProfileController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\DashboardController;

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
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

/*
|--------------------------------------------------------------------------
| MASTER DATA (Butuh Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/master/cities', [CityController::class, 'index']);
    Route::get('/master/unit-kerja', [MasterController::class, 'unitKerja']);
});

/*
|--------------------------------------------------------------------------
| ADMIN (role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('/import-karyawan', [AdminController::class, 'importKaryawan']);

    // CRUD user/karyawan
    Route::get('/karyawan', [AdminController::class, 'listKaryawan']);
    Route::post('/karyawan', [AdminController::class, 'storeKaryawan']);
    Route::delete('/karyawan/{nik}', [AdminController::class, 'deleteKaryawan']);
    Route::delete('/karyawan', [AdminController::class, 'bulkDelete']);
    Route::post('/karyawan/{nik}/reset-password', [AdminController::class, 'resetKaryawanPassword']);

    // Hard & soft competency admin
    Route::post('/import-hard-competencies', [AdminController::class, 'importHardCompetencies']);
    Route::get('/hard-competencies', [HardCompetencyController::class, 'adminIndex']);
    Route::get('/karyawan/{nik}/hard-competencies', [HardCompetencyController::class, 'adminByNik']);

    Route::post('/import-soft-competencies', [AdminController::class, 'importSoftCompetencies']);
    Route::get('/soft-competencies', [SoftCompetencyController::class, 'adminIndex']);
    Route::get('/karyawan/{nik}/soft-competencies', [SoftCompetencyController::class, 'adminByNik']);

    // Profil & logs
    Route::get('/employee-profiles', [EmployeeProfileController::class, 'adminIndex']);
    Route::get('/karyawan/{nik}/profile', [EmployeeProfileController::class, 'adminShowByNik']);
    Route::get('/import-logs', [AdminController::class, 'importLogs']);

    // ✅ TAMBAHAN MINIMAL: Cancel import log (batalkan import)
    Route::post('/import-cancel/{nik}', [AdminController::class, 'cancelImportLog']);

    Route::put('/karyawan/{nik}', [AdminController::class, 'updateKaryawan']);

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD ADMIN (TAMBAHAN)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard/competency-summary', [DashboardController::class, 'competencySummary']);
});

/*
|--------------------------------------------------------------------------
| KARYAWAN (role:karyawan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:karyawan'])->group(function () {
    Route::get('/karyawan/hard-competencies', [HardCompetencyController::class, 'indexSelf']);
    Route::get('/dashboard/karyawan/summary', [DashboardKaryawanController::class, 'summary']);
    Route::get('/karyawan/soft-competencies', [SoftCompetencyController::class, 'indexSelf']);
    Route::get('/karyawan/profile', [EmployeeProfileController::class, 'showSelf']);
    Route::post('/karyawan/profile', [EmployeeProfileController::class, 'updateSelf']);
});
