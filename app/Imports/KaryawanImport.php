<?php

namespace App\Imports;

use App\Models\User;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;

class KaryawanImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsEmptyRows,
    WithBatchInserts,
    WithChunkReading
{
    use SkipsFailures;
    use RemembersRowNumber;

    protected int $imported = 0;
    protected int $importLogId;

    protected array $rowErrors = [];

    public function __construct(int $importLogId)
    {
        $this->importLogId = $importLogId;
    }

    /**
     * Cari key asli di $row dengan daftar alias (case-insensitive)
     */
    private function resolveKey(array $row, array $aliases): ?string
    {
        $normalized = [];
        foreach ($row as $k => $_) {
            $normalized[trim(mb_strtolower((string) $k))] = (string) $k;
        }

        foreach ($aliases as $alias) {
            $key = trim(mb_strtolower((string) $alias));
            if (isset($normalized[$key])) return $normalized[$key];
        }

        return null;
    }

    private function asString($v): string
    {
        return trim((string)($v ?? ''));
    }

    /**
     * Normalisasi NIK:
     * - hilangkan spasi
     * - handle angka excel yang jadi "12345.0"
     * - TIDAK ngilangin 0 belakang yang valid
     */
    private function normalizeNik(string $nik): string
    {
        $nik = preg_replace('/\s+/', '', trim($nik)) ?? trim($nik);

        // kasus umum excel: "12345.0"
        if (preg_match('/^\d+\.0$/', $nik)) {
            $nik = preg_replace('/\.0$/', '', $nik) ?? $nik;
        }

        // kasus scientific (mis. 1.2345E+15) -> susah 100% akurat tanpa formatter,
        // tapi minimal kita biarkan apa adanya, dan error-kan kalau jadi tidak digit.
        return $nik;
    }

    public function model(array $row)
    {
        // alias header fleksibel
        $kNik       = $this->resolveKey($row, ['nik', 'NIK', 'no_induk', 'no_induk_karyawan']);
        $kNama      = $this->resolveKey($row, ['nama', 'name', 'nama_karyawan', 'nama lengkap', 'nama_lengkap']);
        $kEmail     = $this->resolveKey($row, ['email', 'e-mail', 'e_mail', 'email_kantor']);
        $kPass      = $this->resolveKey($row, ['password', 'kata sandi', 'sandi', 'pwd']);
        $kUnitKerja = $this->resolveKey($row, ['unit_kerja', 'unit kerja', 'unit', 'unitkerja']);

        $nik       = $this->normalizeNik($kNik ? $this->asString($row[$kNik] ?? '') : '');
        $name      = $kNama ? $this->asString($row[$kNama] ?? '') : '';
        $email     = $kEmail ? $this->asString($row[$kEmail] ?? '') : '';
        $pass      = $kPass ? $this->asString($row[$kPass] ?? '') : '';
        $unitKerja = $kUnitKerja ? $this->asString($row[$kUnitKerja] ?? '') : '';

        if ($pass === '') $pass = 'password123';

        // ✅ validasi minimal manual
        if ($nik === '' || !preg_match('/^\d+$/', $nik)) {
            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => 'NIK kosong / tidak valid (harus angka). Baris di-skip.',
                'nik' => $nik,
                'email' => $email,
            ];
            return null;
        }

        if ($name === '') {
            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => 'Nama kosong. Baris di-skip.',
                'nik' => $nik,
                'email' => $email,
            ];
            return null;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => 'Email kosong / format tidak valid. Baris di-skip.',
                'nik' => $nik,
                'email' => $email,
            ];
            return null;
        }

        try {
            // jangan update user existing (production-safe)
            $existsByNik = User::query()->where('nik', $nik)->exists();
            $existsByEmail = User::query()->where('email', $email)->exists();

            if ($existsByNik || $existsByEmail) {
                $this->rowErrors[] = [
                    'row' => $this->getRowNumber(),
                    'message' => 'User sudah ada (nik/email). Baris di-skip.',
                    'nik' => $nik,
                    'email' => $email,
                ];
                return null;
            }

            $user = User::create([
                'nik'           => $nik,
                'name'          => $name,
                'email'         => $email,
                'password'      => Hash::make($pass),
                'role'          => 'karyawan',
                'unit_kerja'    => $unitKerja !== '' ? $unitKerja : null,
                'is_active'     => true,
                'import_log_id' => $this->importLogId,
            ]);

            EmployeeProfile::create([
                'user_id'       => $user->id,
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
                'unit_kerja'    => $user->unit_kerja,
                'import_log_id' => $this->importLogId,
            ]);

            $this->imported++;
            return null;
        } catch (\Throwable $e) {
            Log::warning('KARYAWAN ROW ERROR', [
                'row' => $this->getRowNumber(),
                'nik' => $nik,
                'email' => $email,
                'err' => $e->getMessage(),
            ]);

            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => $e->getMessage(),
                'nik' => $nik,
                'email' => $email,
            ];
            return null;
        }
    }

    public function rules(): array
    {
        // ✅ Longgarkan agar header bervariasi tidak memblok semua baris
        return [
            '*.nik' => ['nullable'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [];
    }

    public function getImportedCount(): int { return $this->imported; }
    public function getRowErrors(): array { return $this->rowErrors; }
    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }
}
