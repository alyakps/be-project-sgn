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
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    private function resolveJenisKompetensi(Request $request, string $default): string
    {
        $candidates = [
            'jenis_kompetensi',
            'type',
            'competency_type',
            'kompetensi_type',
            'kompetensi',
            'jenis',
        ];

        $raw = '';
        foreach ($candidates as $key) {
            $val = $request->input($key);
            if (!is_null($val) && trim((string) $val) !== '') {
                $raw = (string) $val;
                break;
            }
        }

        $jenis = strtolower(trim($raw));
        $jenis = str_replace(['-', ' '], '_', $jenis);

        if (in_array($jenis, ['soft', 'soft_competency'], true)) return 'soft';
        if (in_array($jenis, ['hard', 'hard_competency'], true)) return 'hard';

        return $default;
    }

    private function createImportLogAndStoreFile(Request $request, string $typeLog, ?int $tahun): ImportLog
    {
        $storedPath = null;

        try {
            $storedPath = $request->file('file')->store('imports', 'public');
        } catch (\Throwable $e) {
            Log::warning('STORE IMPORT FILE FAILED', ['err' => $e->getMessage()]);
        }

        return ImportLog::create([
            'filename'    => $request->file('file')->getClientOriginalName(),
            'stored_path' => $storedPath,
            'type'        => $typeLog,
            'tahun'       => $tahun,
            'sukses'      => 0,
            'gagal'       => 0,
            'status'      => 'done',
            'uploaded_by' => (int) $request->user()->id,
        ]);
    }

    public function importKaryawan(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $log = $this->createImportLogAndStoreFile($request, 'karyawan', (int) date('Y'));

        try {
            $import = new KaryawanImport((int) $log->id);
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('IMPORT KARYAWAN ERROR', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $log->update([
                'sukses' => 0,
                'gagal'  => 1,
            ]);

            if (config('app.debug')) {
                return response()->json([
                    'message'       => 'Terjadi error saat import karyawan.',
                    'error'         => $e->getMessage(),
                    'at'            => $e->getFile() . ':' . $e->getLine(),
                    'import_log_id' => $log->id,
                ], 500);
            }

            return response()->json([
                'message'       => 'Terjadi error saat import karyawan.',
                'import_log_id' => $log->id,
            ], 500);
        }

        $failures = collect($import->failures())->map(fn ($f) => [
            'row'    => $f->row(),
            'errors' => $f->errors(),
            'values' => $f->values(),
        ])->values();

        $rowErrors = collect($import->getRowErrors())->values();

        $gagalCount = (int) $failures->count() + (int) $rowErrors->count();

        $log->update([
            'sukses' => (int) $import->getImportedCount(),
            'gagal'  => $gagalCount,
        ]);

        $statusCode = ($gagalCount > 0) ? 422 : 200;

        return response()->json([
            'message'       => 'Proses import selesai.',
            'sukses'        => $import->getImportedCount(),
            'gagal'         => $failures,
            'row_errors'    => $rowErrors,
            'import_log_id' => $log->id,
        ], $statusCode);
    }

    public function importHardCompetencies(Request $request)
    {
        $request->validate([
            'file'  => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tahun = (int) $request->tahun;

        $jenis = $this->resolveJenisKompetensi($request, 'hard');
        $typeLog = ($jenis === 'soft') ? 'soft_competency' : 'hard_competency';

        $log = $this->createImportLogAndStoreFile($request, $typeLog, $tahun);

        try {
            $import = ($jenis === 'soft')
                ? new SoftCompetencyImport($tahun, (int) $log->id)
                : new HardCompetencyImport($tahun, (int) $log->id);

            Excel::import($import, $request->file('file'));

            $failures = collect($import->failures())->map(fn ($f) => [
                'row'    => $f->row(),
                'errors' => $f->errors(),
                'values' => $f->values(),
            ])->values();

            $log->update([
                'sukses' => (int) $import->getImportedCount(),
                'gagal'  => (int) $failures->count(),
            ]);

            return response()->json([
                'message'       => 'Proses import selesai.',
                'jenis_final'   => $jenis,
                'type_log'      => $typeLog,
                'sukses'        => (int) $import->getImportedCount(),
                'gagal'         => $failures,
                'import_log_id' => $log->id,
            ], $failures->count() > 0 ? 422 : 200);
        } catch (\Throwable $e) {
            Log::error('IMPORT COMPETENCY ERROR (HARD ENDPOINT)', [
                'jenis_final' => $jenis,
                'err'         => $e->getMessage(),
            ]);

            $log->update(['sukses' => 0, 'gagal' => 1]);

            return response()->json([
                'message'       => 'Terjadi error saat import competency.',
                'import_log_id' => $log->id,
            ], 500);
        }
    }

    public function importSoftCompetencies(Request $request)
    {
        $request->validate([
            'file'  => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tahun = (int) $request->tahun;

        $jenis = $this->resolveJenisKompetensi($request, 'soft');
        $typeLog = ($jenis === 'hard') ? 'hard_competency' : 'soft_competency';

        $log = $this->createImportLogAndStoreFile($request, $typeLog, $tahun);

        try {
            $import = ($jenis === 'hard')
                ? new HardCompetencyImport($tahun, (int) $log->id)
                : new SoftCompetencyImport($tahun, (int) $log->id);

            Excel::import($import, $request->file('file'));

            $failures = collect($import->failures())->map(fn ($f) => [
                'row'    => $f->row(),
                'errors' => $f->errors(),
                'values' => $f->values(),
            ])->values();

            $log->update([
                'sukses' => (int) $import->getImportedCount(),
                'gagal'  => (int) $failures->count(),
            ]);

            return response()->json([
                'message'       => 'Proses import selesai.',
                'jenis_final'   => $jenis,
                'type_log'      => $typeLog,
                'sukses'        => (int) $import->getImportedCount(),
                'gagal'         => $failures,
                'import_log_id' => $log->id,
            ], $failures->count() > 0 ? 422 : 200);
        } catch (\Throwable $e) {
            Log::error('IMPORT COMPETENCY ERROR (SOFT ENDPOINT)', [
                'jenis_final' => $jenis,
                'err'         => $e->getMessage(),
            ]);

            $log->update(['sukses' => 0, 'gagal' => 1]);

            return response()->json([
                'message'       => 'Terjadi error saat import competency.',
                'import_log_id' => $log->id,
            ], 500);
        }
    }

    // ✅ UBAH MINIMAL DI SINI: route param tetap {nik} tapi dipakai sebagai import_log_id (angka)
    public function cancelImportLog(Request $request, string $nik)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!ctype_digit($nik)) {
            return response()->json([
                'message' => 'Parameter nik harus angka (import_log_id).',
            ], 422);
        }

        $id = (int) $nik;

        $log = ImportLog::query()->find($id);
        if (!$log) {
            return response()->json(['message' => 'Import log tidak ditemukan.'], 404);
        }

        if ($log->status === 'canceled') {
            return response()->json([
                'message' => 'Import sudah dibatalkan.',
                'data' => ['id' => $log->id, 'status' => 'canceled'],
            ], 200);
        }

        DB::transaction(function () use ($request, $log) {
            if ($log->type === 'karyawan') {
                User::query()
                    ->where('import_log_id', $log->id)
                    ->update(['is_active' => false]);
            }

            if ($log->type === 'hard_competency') {
                DB::table('hard_competencies')
                    ->where('import_log_id', $log->id)
                    ->update(['is_active' => false]);
            }

            if ($log->type === 'soft_competency') {
                DB::table('soft_competencies')
                    ->where('import_log_id', $log->id)
                    ->update(['is_active' => false]);
            }

            $log->update([
                'status'      => 'canceled',
                'canceled_at' => now(),
                'canceled_by' => (int) $request->user()->id,
            ]);
        });

        if ($log->stored_path) {
            try {
                Storage::disk('public')->delete($log->stored_path);
            } catch (\Throwable $e) {
                Log::warning('DELETE IMPORT FILE FAILED', ['id' => $log->id, 'err' => $e->getMessage()]);
            }
        }

        return response()->json([
            'message' => 'Import berhasil dibatalkan. Data terkait sudah dinonaktifkan.',
            'data' => [
                'id'     => $log->id,
                'status' => 'canceled',
            ],
        ]);
    }

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
                    'id'          => $log->id,
                    'filename'    => $log->filename,
                    'type'        => $log->type,
                    'tahun'       => $log->tahun,
                    'sukses'      => $log->sukses,
                    'gagal'       => $log->gagal,
                    'status'      => $log->status,
                    'canceled_at' => optional($log->canceled_at)?->format('Y-m-d H:i:s'),
                    'created_at'  => optional($log->created_at)->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

public function listKaryawan(Request $request)
{
    if (!$request->user()->isAdmin()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $perPage = (int) $request->get('per_page', 10);
    $search  = trim((string) $request->get('q', ''));

    // ✅ filter unit kerja (baru)
    $unitKerja = trim((string) $request->get('unit_kerja', ''));

    // ✅ PATCH MINIMAL:
    // default hanya tampilkan active
    // untuk lihat nonaktif, pakai ?include_inactive=1
    $includeInactive = $request->boolean('include_inactive', false);

    $paginator = User::query()
        ->where('role', 'karyawan')
        ->when(!$includeInactive, fn($q) => $q->where('is_active', true))
        ->when($search !== '', function ($q) use ($search) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        })
        // ✅ filter unit kerja (baru)
        ->when($unitKerja !== '', fn($q) => $q->where('unit_kerja', $unitKerja))
        ->select('id', 'name', 'email', 'nik', 'unit_kerja', 'is_active', 'created_at')
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
                    'is_active'  => true,
                ]);

                EmployeeProfile::create([
                    'user_id'          => $user->id,
                    'nama_lengkap'     => $data['name'],
                    'nik'              => $data['nik'],
                    'email_pribadi'    => $data['email'],
                    'jabatan_terakhir' => $data['jabatan_terakhir'] ?? null,
                    'unit_kerja'       => $user->unit_kerja,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('STORE KARYAWAN ERROR', ['err' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menyimpan karyawan.'], 500);
        }

        return response()->json(['message' => 'Karyawan berhasil dibuat.'], 201);
    }

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
            $newUnitKerja = $request->has('unit_kerja') ? $request->unit_kerja : $user->unit_kerja;

            $user->update([
                'name'       => $request->name,
                'email'      => $request->email,
                'unit_kerja' => $newUnitKerja,
            ]);

            $profile = $user->profile ?? EmployeeProfile::create([
                'user_id'       => $user->id,
                'nik'           => $user->nik,
                'nama_lengkap'  => $user->name,
                'email_pribadi' => $user->email,
            ]);

            $profile->fill($request->except(['photo', 'unit_kerja']));
            $profile->unit_kerja = $user->unit_kerja;
            $profile->nama_lengkap = $user->name;
            $profile->email_pribadi = $user->email;
            $profile->nik = $user->nik;
            $profile->save();
        });

        $user->load('profile');

        return response()->json([
            'message' => 'Profil karyawan berhasil diperbarui.',
            'data' => [
                'user' => $user,
                'profile' => $user->profile,
            ],
        ]);
    }

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

    public function resetKaryawanPassword(Request $request, string $nik)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('nik', $nik)->where('role', 'karyawan')->first();

        if (!$user) {
            return response()->json(['message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $defaultPassword = '123';

        $user->password = Hash::make($defaultPassword);
        $user->must_change_password = true;
        $user->save();

        return response()->json([
            'message' => 'Password karyawan berhasil direset.',
            'default_password' => $defaultPassword,
        ]);
    }
}
