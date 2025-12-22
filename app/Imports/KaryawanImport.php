<?php

namespace App\Imports;

use App\Models\User;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsFailures;

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

    protected int $imported = 0;

    /**
     * Temukan key asli di $row dari daftar alias (case-insensitive, trim)
     */
    private function resolveKey(array $row, array $aliases): ?string
    {
        $normalized = [];

        foreach ($row as $k => $_) {
            $normalized[trim(mb_strtolower($k))] = $k; // lower -> original
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
        // --- dukung variasi header ---
        $kNik       = $this->resolveKey($row, ['nik', 'NIK']);
        $kNama      = $this->resolveKey($row, ['nama', 'name']);
        $kEmail     = $this->resolveKey($row, ['email', 'e-mail', 'e_mail']);
        $kPass      = $this->resolveKey($row, ['password', 'kata sandi', 'sandi', 'pwd']);
        $kUnitKerja = $this->resolveKey($row, ['unit_kerja', 'unit kerja', 'unit']);

        // ambil nilai (string) + trim
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

        // Normalisasi NIK agar tidak jadi 123.0 dan tetap string
        if ($nik !== '' && is_numeric($nik)) {
            $nik = rtrim(rtrim((string) $nik, '0'), '.');
        }

        // Default password jika kosong
        if ($pass === '') {
            $pass = 'password123';
        }

        // SIMPAN / UPDATE USER
        $user = User::updateOrCreate(
            ['nik' => $nik],
            [
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($pass),
                'role'       => 'karyawan',
                'unit_kerja' => $unitKerja,
            ]
        );

        // ✅ FIX MINIMAL: sinkronkan profile juga (biar unit_kerja ke-update)
        EmployeeProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
                'unit_kerja'    => $user->unit_kerja,
            ]
        );

        $this->imported++;

        return null; // jangan return model lagi
    }

    /** Validasi per baris */
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

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}