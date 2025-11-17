<?php

namespace App\Imports;

use App\Models\SoftCompetency;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class SoftCompetencyImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsEmptyRows,
    WithBatchInserts,
    WithChunkReading
{
    use SkipsFailures;

    protected int $tahun;
    protected int $imported = 0;

    public function __construct(int $tahun)
    {
        $this->tahun = $tahun;
    }

    public function model(array $row)
    {
        // CAST SEMUA KE STRING (fix error nik must be string)
        $nik       = trim((string)($row['nik'] ?? ''));
        $idKom     = trim((string)($row['id_kompetensi'] ?? ''));
        $kode      = trim((string)($row['kode'] ?? ''));
        $nama      = trim((string)($row['nama_kompetensi'] ?? ''));
        $statusRaw = trim((string)($row['status'] ?? ''));
        $nilaiRaw  = $row['nilai'] ?? null;
        $deskripsi = trim((string)($row['deskripsi'] ?? ''));

        // Normalisasi status
        $statusLower = mb_strtolower($statusRaw);
        if ($statusLower === 'tercapai') {
            $status = 'tercapai';
        } elseif ($statusLower === 'tidak tercapai') {
            $status = 'tidak tercapai';
        } else {
            $status = $statusLower;
        }

        $nilai = is_null($nilaiRaw) ? null : (int) $nilaiRaw;

        // UPSERT: unik berdasarkan (nik + id_kompetensi + tahun)
        SoftCompetency::updateOrCreate(
            [
                'nik'           => $nik,
                'id_kompetensi' => $idKom,
                'tahun'         => $this->tahun,
            ],
            [
                'kode'            => $kode,
                'nama_kompetensi' => $nama,
                'status'          => $status,
                'nilai'           => $nilai,
                'deskripsi'       => $deskripsi ?: null,
            ]
        );

        $this->imported++;

        return null; // jangan return model karena pakai updateOrCreate
    }

    public function rules(): array
    {
        return [
            '*.nik'             => ['required', 'regex:/^\d+$/'], // FIXED → fleksibel & aman
            '*.id_kompetensi'   => ['required'],
            '*.kode'            => ['required', 'string'],
            '*.nama_kompetensi' => ['required', 'string'],
            '*.status'          => [
                'required',
                Rule::in([
                    'Tercapai',
                    'Tidak Tercapai',
                    'tercapai',
                    'tidak tercapai',
                ]),
            ],
            '*.nilai'           => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nik.required'           => 'Kolom NIK wajib diisi.',
            '*.nik.regex'              => 'NIK harus berupa angka.',

            '*.id_kompetensi.required' => 'Kolom id_kompetensi wajib diisi.',
            '*.kode.required'          => 'Kolom kode wajib diisi.',
            '*.nama_kompetensi.required' => 'Kolom nama_kompetensi wajib diisi.',

            '*.status.required'        => 'Kolom status wajib diisi.',
            '*.status.in'              => 'Status harus "Tercapai" atau "Tidak Tercapai".',

            '*.nilai.required'         => 'Kolom nilai wajib diisi.',
            '*.nilai.integer'          => 'Nilai harus berupa angka.',
            '*.nilai.min'              => 'Nilai minimal 0.',
            '*.nilai.max'              => 'Nilai maksimal 100.',
        ];
    }

    public function getImportedCount(): int { return $this->imported; }
    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }
}
