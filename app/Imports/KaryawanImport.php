<?php

namespace App\Imports;

use App\Models\User;
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

    /** @var int */
    protected int $imported = 0;

    /**
     * Cari key sebenarnya di array row berdasarkan alias-ali­as yang diizinkan.
     * Normalisasi: lower-case + trim.
     */
    private function resolveKey(array $row, array $aliases): ?string
    {
        $normalized = [];
        foreach ($row as $k => $_) {
            $normalized[trim(mb_strtolower($k))] = $k;
        }
        foreach ($aliases as $alias) {
            $key = trim(mb_strtolower($alias));
            if (isset($normalized[$key])) {
                return $normalized[$key]; // k asli
            }
        }
        return null;
    }

    public function model(array $row)
    {
        // dukung variasi header
        $kNama  = $this->resolveKey($row, ['nama', 'name']);
        $kEmail = $this->resolveKey($row, ['email', 'e-mail']);
        $kPass  = $this->resolveKey($row, ['password', 'kata sandi', 'sandi']);

        $name  = $kNama  ? trim((string)($row[$kNama]  ?? '')) : '';
        $email = $kEmail ? trim((string)($row[$kEmail] ?? '')) : '';
        $pass  = $kPass  ? (string)($row[$kPass] ?? '') : '';

        // Jika ada kolom krusial yang kosong, biarkan rules() yang menandai failure
        // tetapi return null agar tidak membuat model invalid.
        if ($name === '' || $email === '' || $pass === '') {
            return null;
        }

        $this->imported++;

        return new User([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($pass),
            'role'     => 'karyawan',
        ]);
    }

    /**
     * Validasi per baris (berbasis heading row yang sudah dinormalisasi oleh package).
     * Catatan: kita cover dua kemungkinan header untuk nama: 'nama' dan 'name'.
     */
    public function rules(): array
    {
        return [
            // salah satu wajib ada: 'nama' ATAU 'name'
            '*.nama'     => ['required_without:*.name'],
            '*.name'     => ['required_without:*.nama'],

            'email' => ['required','email', Rule::unique('users','email')],
            '*.password' => ['required','string','min:6'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nama.required_without' => 'Kolom nama atau name wajib diisi.',
            '*.name.required_without' => 'Kolom name atau nama wajib diisi.',
            '*.email.required'        => 'Kolom email wajib diisi.',
            '*.email.email'           => 'Format email tidak valid.',
            '*.email.unique'          => 'Email sudah terdaftar.',
            '*.password.required'     => 'Kolom password wajib diisi.',
            '*.password.min'          => 'Password minimal 6 karakter.',
        ];
    }

    /** Counter sukses */
    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }
}
