<?php

namespace App\Imports;

use App\Models\HardCompetency;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class HardCompetencyImport implements
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
        // Kalau dari Excel masih ada kolom "id" / "no", biarin aja, TAPI JANGAN dipakai.
        // Kita ambil hanya kolom yang kita butuh.

        $nik       = trim((string)($row['nik'] ?? ''));
        $idKom     = trim((string)($row['id_kompetensi'] ?? ''));
        $kode      = trim((string)($row['kode'] ?? ''));
        $nama      = trim((string)($row['nama_kompetensi'] ?? ''));
        $jobFam    = trim((string)($row['job_family_kompetensi'] ?? ''));
        $subJob    = trim((string)($row['sub_job_family_kompetensi'] ?? ''));
        $statusRaw = trim((string)($row['status'] ?? ''));
        $nilaiRaw  = $row['nilai'] ?? null;
        $deskripsi = trim((string)($row['deskripsi'] ?? ''));

        // Normalisasi status ke ENUM di DB (migration: 'tercapai', 'tidak tercapai')
        $statusLower = mb_strtolower($statusRaw);
        if ($statusLower === 'tercapai') {
            $status = 'tercapai';
        } elseif ($statusLower === 'tidak tercapai') {
            $status = 'tidak tercapai';
        } else {
            $status = $statusLower; // biar kelihatan error di validation
        }

        $nilai = is_null($nilaiRaw) ? null : (int) $nilaiRaw;

        // Upsert manual: ini langsung nembak DB
        HardCompetency::updateOrCreate(
            [
                'nik'   => $nik,
                'kode'  => $kode,
                'tahun' => $this->tahun,
            ],
            [
                'id_kompetensi'             => $idKom,
                'nama_kompetensi'           => $nama,
                'job_family_kompetensi'     => $jobFam,
                'sub_job_family_kompetensi' => $subJob ?: null,
                'status'                    => $status,
                'nilai'                     => $nilai,
                'deskripsi'                 => $deskripsi ?: null,
            ]
        );

        $this->imported++;

        // ⛔ PENTING: return null supaya Excel TIDAK mencoba insert lagi
        return null;
    }

    public function rules(): array
    {
        return [
            '*.nik'                   => ['required', 'string'],
            '*.kode'                  => ['required', 'string'],
            '*.id_kompetensi'         => ['required'],
            '*.nama_kompetensi'       => ['required', 'string'],
            '*.job_family_kompetensi' => ['required', 'string'],
            '*.status'                => [
                'required',
                Rule::in([
                    'Tercapai',
                    'Tidak Tercapai',
                    'tercapai',
                    'tidak tercapai',
                ]),
            ],
            '*.nilai'                 => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nik.required'                   => 'Kolom NIK wajib diisi.',
            '*.kode.required'                  => 'Kolom kode wajib diisi.',
            '*.id_kompetensi.required'         => 'Kolom id_kompetensi wajib diisi.',
            '*.nama_kompetensi.required'       => 'Kolom nama_kompetensi wajib diisi.',
            '*.job_family_kompetensi.required' => 'Kolom job_family_kompetensi wajib diisi.',
            '*.status.required'                => 'Kolom status wajib diisi.',
            '*.status.in'                      => 'Status harus "Tercapai" atau "Tidak Tercapai".',
            '*.nilai.required'                 => 'Kolom nilai wajib diisi.',
            '*.nilai.integer'                  => 'Nilai harus berupa angka.',
            '*.nilai.min'                      => 'Nilai minimal 0.',
            '*.nilai.max'                      => 'Nilai maksimal 100.',
        ];
    }

    public function getImportedCount(): int { return $this->imported; }
    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }
}
