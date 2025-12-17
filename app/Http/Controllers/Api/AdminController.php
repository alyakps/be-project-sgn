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
    /**
     * Ambil jenis kompetensi dari request dengan toleransi:
     * - field: jenis_kompetensi | type | competency_type | kompetensi_type | kompetensi | jenis
     * - value: soft | hard | soft_competency | hard_competency | "soft competency" | "hard competency"
     * Return: 'soft' | 'hard'
     */
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
        $jenis = str_replace(['-', ' '], '_', $jenis); // "soft competency" -> soft_competency

        if (in_array($jenis, ['soft', 'soft_competency'], true)) return 'soft';
        if (in_array($jenis, ['hard', 'hard_competency'], true)) return 'hard';

        return $default;
    }

    /**
     * Jalankan import kompetensi sesuai jenis, lalu catat log.
     */
    private function handleCompetencyImport(Request $request, int $tahun, string $jenis): array
    {
        if ($jenis === 'soft') {
            $import  = new SoftCompetencyImport($tahun);
            $typeLog = 'soft_competency';
        } else {
            $import  = new HardCompetencyImport($tahun);
            $typeLog = 'hard_competency';
        }

        Excel::import($import, $request->file('file'));

        $failures = collect($import->failures())->map(fn ($f) => [
            'row'    => $f->row(),
            'errors' => $f->errors(),
            'values' => $f->values(),
        ])->values();

        $log = ImportLog::create([
            'filename'    => $request->file('file')->getClientOriginalName(),
            'type'        => $typeLog,
            'tahun'       => $tahun,
            'sukses'      => (int) $import->getImportedCount(),
            'gagal'       => (int) $failures->count(),
            'uploaded_by' => (int) $request->user()->id,
        ]);

        return [(int) $import->getImportedCount(), $failures, (int) $log->id, $typeLog];
    }

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
            return response()->json(['message' => 'Terjadi error saat import karyawan.'], 500);
        }

        $failures = collect($import->failures())->map(fn ($f) => [
            'row'    => $f->row(),
            'errors' => $f->errors(),
            'values' => $f->values(),
        ])->values();

        $log = null;
        try {
            $log = ImportLog::create([
                'filename'    => $request->file('file')->getClientOriginalName(),
                'type'        => 'karyawan',
                'tahun'       => (int) date('Y'),
                'sukses'      => (int) $import->getImportedCount(),
                'gagal'       => (int) $failures->count(),
                'uploaded_by' => (int) $request->user()->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('IMPORT LOG CREATE FAILED (KARYAWAN)', ['err' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Proses import selesai.',
            'sukses'  => $import->getImportedCount(),
            'gagal'   => $failures,
            'import_log_id' => $log?->id,
        ]);
    }

    /* ======================================================
     * IMPORT HARD COMPETENCY
     * ====================================================== */

    public function importHardCompetencies(Request $request)
    {
        $request->validate([
            'file'  => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            // jangan maksa jenis_kompetensi di sini, karena FE kamu kemungkinan pakai nama field lain
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tahun = (int) $request->tahun;

        // ✅ ambil dari request kalau ada, kalau tidak fallback hard (karena endpoint hard)
        $jenis = $this->resolveJenisKompetensi($request, 'hard');

        // DEBUG ringan biar ketauan endpoint mana yang kepanggil dan jenis apa yang kebaca
        Log::info('IMPORT COMPETENCY HIT', [
            'endpoint' => 'importHardCompetencies',
            'path' => $request->path(),
            'jenis_final' => $jenis,
            'jenis_raw_candidates' => $request->only([
                'jenis_kompetensi','type','competency_type','kompetensi_type','kompetensi','jenis'
            ]),
        ]);

        try {
            [$sukses, $failures, $logId, $typeLog] = $this->handleCompetencyImport($request, $tahun, $jenis);
        } catch (\Throwable $e) {
            Log::error('IMPORT COMPETENCY ERROR (HARD ENDPOINT)', [
                'jenis_final' => $jenis,
                'err' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Terjadi error saat import competency.'], 500);
        }

        return response()->json([
            'message' => 'Proses import selesai.',
            'jenis_final' => $jenis,
            'type_log' => $typeLog,
            'sukses'  => $sukses,
            'gagal'   => $failures,
            'import_log_id' => $logId,
        ]);
    }

    /* ======================================================
     * IMPORT SOFT COMPETENCY
     * ====================================================== */

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

        // ✅ ambil dari request kalau ada, kalau tidak fallback soft (karena endpoint soft)
        $jenis = $this->resolveJenisKompetensi($request, 'soft');

        Log::info('IMPORT COMPETENCY HIT', [
            'endpoint' => 'importSoftCompetencies',
            'path' => $request->path(),
            'jenis_final' => $jenis,
            'jenis_raw_candidates' => $request->only([
                'jenis_kompetensi','type','competency_type','kompetensi_type','kompetensi','jenis'
            ]),
        ]);

        try {
            [$sukses, $failures, $logId, $typeLog] = $this->handleCompetencyImport($request, $tahun, $jenis);
        } catch (\Throwable $e) {
            Log::error('IMPORT COMPETENCY ERROR (SOFT ENDPOINT)', [
                'jenis_final' => $jenis,
                'err' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Terjadi error saat import competency.'], 500);
        }

        return response()->json([
            'message' => 'Proses import selesai.',
            'jenis_final' => $jenis,
            'type_log' => $typeLog,
            'sukses'  => $sukses,
            'gagal'   => $failures,
            'import_log_id' => $logId,
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

                    // ✅ FIX: langsung sinkron dari awal
                    'unit_kerja'       => $user->unit_kerja,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('STORE KARYAWAN ERROR', ['err' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menyimpan karyawan.'], 500);
        }

        return response()->json(['message' => 'Karyawan berhasil dibuat.'], 201);
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
            // ✅ FIX: jangan set unit_kerja jadi null kalau FE tidak mengirim fieldnya
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

            // ✅ FIX: jangan biarkan request menimpa unit_kerja pada profile
            $profile->fill($request->except(['photo', 'unit_kerja']));

            // ✅ single source of truth
            $profile->unit_kerja = $user->unit_kerja;

            // jaga konsistensi field dasar
            $profile->nama_lengkap  = $user->name;
            $profile->email_pribadi = $user->email;
            $profile->nik           = $user->nik;

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

        $defaultPassword = '123';

        // ✅ FIX: harus hash biar bisa login
        $user->password = Hash::make($defaultPassword);

        // ✅ Trigger: wajib ganti password setelah reset admin
        $user->must_change_password = true;

        $user->save();

        return response()->json([
            'message' => 'Password karyawan berhasil direset.',
            'default_password' => $defaultPassword,
        ]);
    }

    /* ======================================================
     * IMPORT LOGS
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
