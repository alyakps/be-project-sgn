<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\KaryawanImport;
use App\Imports\HardCompetencyImport;
use App\Imports\SoftCompetencyImport;
use App\Models\ImportLog;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Http\Requests\CreateEmployeeRequest;
use App\Http\Requests\AdminUpdateEmployeeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /* ======================================================
     * IMPORT KARYAWAN
     * ====================================================== */

    public function importKaryawan(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv']
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $import = new KaryawanImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('IMPORT KARYAWAN ERROR', ['err' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi error saat import karyawan.',
            ], 500);
        }

        $failures = collect($import->failures())->map(fn ($f) => [
            'row'    => $f->row(),
            'errors' => $f->errors(),
            'values' => $f->values(),
        ])->values();

        return response()->json([
            'message' => 'Proses import selesai.',
            'sukses'  => $import->getImportedCount(),
            'gagal'   => $failures,
        ]);
    }

    /* ======================================================
     * LIST KARYAWAN
     * ====================================================== */

    public function listKaryawan(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('q', ''));

        $paginator = User::query()
            ->where('role', 'karyawan')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($w) =>
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%")
                );
            })
            ->select('id', 'name', 'email', 'nik', 'unit_kerja', 'created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /* ======================================================
     * CREATE KARYAWAN
     * ====================================================== */

    public function storeKaryawan(CreateEmployeeRequest $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $user = User::create([
                    'nik'        => $data['nik'],
                    'name'       => $data['name'],
                    'email'      => $data['email'],
                    'role'       => $data['role'],
                    'unit_kerja' => $data['unit_kerja'] ?? null,
                    'password'   => Hash::make($data['password']),
                ]);

                EmployeeProfile::create([
                    'user_id'          => $user->id,
                    'nama_lengkap'     => $data['name'],
                    'nik'              => $data['nik'],
                    'email_pribadi'    => $data['email'],
                    'jabatan_terakhir' => $data['jabatan_terakhir'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('STORE KARYAWAN ERROR', ['err' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menyimpan karyawan.'], 500);
        }

        return response()->json([
            'message' => 'Karyawan berhasil dibuat.',
        ], 201);
    }

    /* ======================================================
     * UPDATE PROFIL KARYAWAN (ADMIN)
     * ====================================================== */

    public function updateKaryawan(AdminUpdateEmployeeRequest $request, string $nik)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::with('profile')
            ->where('nik', $nik)
            ->where('role', 'karyawan')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Karyawan tidak ditemukan.'], 404);
        }

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'name'       => $request->name,
                'email'      => $request->email,
                'unit_kerja' => $request->unit_kerja,
            ]);

            $profile = $user->profile ?? EmployeeProfile::create([
                'user_id'       => $user->id,
                'nik'           => $user->nik,
                'nama_lengkap'  => $user->name,
                'email_pribadi' => $user->email,
            ]);

            $profile->fill($request->except(['photo']));
            $profile->save();
        });

        $user->load('profile');
        $user->profile->unit_kerja = $user->unit_kerja;

        return response()->json([
            'message' => 'Profil karyawan berhasil diperbarui.',
            'data' => [
                'user' => $user,
                'profile' => $user->profile,
            ],
        ]);
    }

    /* ======================================================
     * DELETE KARYAWAN
     * ====================================================== */

    public function deleteKaryawan(Request $request, string $nik)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('nik', $nik)->where('role', 'karyawan')->first();

        if (!$user) {
            return response()->json(['message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Karyawan berhasil dihapus.']);
    }

    /* ======================================================
     * RESET PASSWORD
     * ====================================================== */

    public function resetKaryawanPassword(Request $request, string $nik)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('nik', $nik)->where('role', 'karyawan')->first();

        if (!$user) {
            return response()->json(['message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $user->password = '123';
        $user->save();

        return response()->json([
            'message' => 'Password karyawan berhasil direset.',
            'default_password' => '123',
        ]);
    }

    /* ======================================================
     * IMPORT LOGS (✅ FIX – METHOD YANG KURANG)
     * ====================================================== */

    public function importLogs(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $logs = ImportLog::query()
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $logs->map(function (ImportLog $log) {
                return [
                    'id'         => $log->id,
                    'filename'   => $log->filename,
                    'type'       => $log->type,
                    'tahun'      => $log->tahun,
                    'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }
}
