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
     * Contoh:
     *  - " NIK "     -> " NIK "
     *  - "Nama"      -> "Nama"
     *  - "e-mail"    -> "e-mail"
     */
    private function resolveKey(array $row, array $aliases): ?string
    {
        $normalized = [];

        foreach ($row as $k => $_) {
            // contoh: " NIK " -> "nik"
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
        $kNik   = $this->resolveKey($row, ['nik', 'NIK']);
        $kNama  = $this->resolveKey($row, ['nama', 'name']);
        $kEmail = $this->resolveKey($row, ['email', 'e-mail', 'e_mail']);
        $kPass  = $this->resolveKey($row, ['password', 'kata sandi', 'sandi', 'pwd']);

        // ambil nilai (string) + trim
        $nik   = $kNik   ? (string)($row[$kNik]   ?? '') : '';
        $name  = $kNama  ? (string)($row[$kNama]  ?? '') : '';
        $email = $kEmail ? (string)($row[$kEmail] ?? '') : '';
        $pass  = $kPass  ? (string)($row[$kPass]  ?? '') : '';

        $nik   = trim($nik);
        $name  = trim($name);
        $email = trim($email);
        $pass  = trim($pass);

        // Normalisasi NIK agar tidak jadi 123.0 dan tetap berupa string
        if ($nik !== '' && is_numeric($nik)) {
            $nik = rtrim(rtrim((string) $nik, '0'), '.');
        }

        // Default password jika kosong (opsional)
        if ($pass === '') {
            $pass = 'password123';
        }

        // ============================
        // SIMPAN / UPDATE USER
        // ============================
        // Kalau mau selalu buat baru dan fail kalau duplikat email/nik,
        // bisa pakai create() saja. Di sini pakai updateOrCreate supaya
        // kalau re-import NIK yang sama, datanya di-update.
        $user = User::updateOrCreate(
            ['nik' => $nik],
            [
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($pass),
                'role'     => 'karyawan',
            ]
        );

        // ============================
        // AUTO-BUAT EMPLOYEE PROFILE
        // ============================
        EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
                // field lain boleh dibiarkan null / default
            ]
        );

        $this->imported++;

        // PENTING:
        // Karena kita sudah manual insert/update di atas,
        // JANGAN kembalikan model ke Excel.
        // Kalau return $user -> Excel akan mass-insert lagi dan ID bentrok.
        return null;
    }

    /** Validasi per baris */
    public function rules(): array
    {
        return [
            // NIK wajib, hanya digit
            '*.nik'      => ['required', 'regex:/^\d+$/'],
            // Salah satu wajib: nama atau name
            '*.nama'     => ['required_without:*.name'],
            '*.name'     => ['required_without:*.nama'],
            // Email wajib & format benar
            '*.email'    => ['required', 'email'],
            // Password boleh kosong (akan diisi default). Ubah ke 'required' jika wajib.
            '*.password' => ['nullable', 'string', 'min:6'],
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
