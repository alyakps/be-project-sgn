<?php

namespace App\Imports;

use App\Models\User;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
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

    private function resolveKey(array $row, array $aliases): ?string
    {
        $normalized = [];
        foreach ($row as $k => $_) {
            $normalized[trim(mb_strtolower($k))] = $k;
        }

        foreach ($aliases as $alias) {
            $key = trim(mb_strtolower($alias));
            if (isset($normalized[$key])) {
                return $normalized[$key];
            }
        }
        return null;
    }

    public function model(array $row)
    {
        $kNik       = $this->resolveKey($row, ['nik', 'NIK']);
        $kNama      = $this->resolveKey($row, ['nama', 'name']);
        $kEmail     = $this->resolveKey($row, ['email', 'e-mail', 'e_mail']);
        $kPass      = $this->resolveKey($row, ['password', 'kata sandi', 'sandi', 'pwd']);
        $kUnitKerja = $this->resolveKey($row, ['unit_kerja', 'unit kerja', 'unit']);

        $nik       = $kNik       ? (string)($row[$kNik]       ?? '') : '';
        $name      = $kNama      ? (string)($row[$kNama]      ?? '') : '';
        $email     = $kEmail     ? (string)($row[$kEmail]     ?? '') : '';
        $pass      = $kPass      ? (string)($row[$kPass]      ?? '') : '';
        $unitKerja = $kUnitKerja ? (string)($row[$kUnitKerja] ?? '') : '';

        $nik       = trim($nik);
        $name      = trim($name);
        $email     = trim($email);
        $pass      = trim($pass);
        $unitKerja = trim($unitKerja);

        if ($nik !== '' && is_numeric($nik)) {
            $nik = rtrim(rtrim((string) $nik, '0'), '.');
        }

        if ($pass === '') $pass = 'password123';

        if ($nik === '') {
            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => 'NIK kosong.',
                'nik' => $nik,
                'email' => $email,
            ];
            return null;
        }

        try {
            // ✅ PRODUCTION SAFE: jangan update user yang sudah ada
            $existsByNik = User::query()->where('nik', $nik)->exists();
            $existsByEmail = ($email !== '') ? User::query()->where('email', $email)->exists() : false;

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
                'nik'          => $nik,
                'name'         => $name,
                'email'        => $email,
                'password'     => Hash::make($pass),
                'role'         => 'karyawan',
                'unit_kerja'   => $unitKerja,
                'is_active'    => true,
                'import_log_id'=> $this->importLogId,
            ]);

            EmployeeProfile::create([
                'user_id'        => $user->id,
                'nama_lengkap'   => $user->name,
                'nik'            => $user->nik,
                'email_pribadi'  => $user->email,
                'unit_kerja'     => $user->unit_kerja,
                'import_log_id'  => $this->importLogId,
            ]);

            $this->imported++;
            return null;
        } catch (\Throwable $e) {
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
        return [
            '*.nik'        => ['required', 'regex:/^\d+$/'],
            '*.nama'       => ['required_without:*.name'],
            '*.name'       => ['required_without:*.nama'],
            '*.email'      => ['required', 'email'],
            '*.password'   => ['nullable', 'string', 'min:6'],
            '*.unit_kerja' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nik.required' => 'Kolom NIK wajib diisi.',
            '*.nik.regex'    => 'NIK hanya boleh berisi angka.',
            '*.nama.required_without' => 'Kolom nama atau name wajib diisi.',
            '*.name.required_without' => 'Kolom name atau nama wajib diisi.',
            '*.email.required' => 'Kolom email wajib diisi.',
            '*.email.email'    => 'Format email tidak valid.',
            '*.password.min'   => 'Password minimal 6 karakter.',
        ];
    }

    public function getImportedCount(): int { return $this->imported; }
    public function getRowErrors(): array { return $this->rowErrors; }
    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }
}
