<?php

namespace App\Imports;

use App\Models\HardCompetency;
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
    use RemembersRowNumber;

    protected int $tahun;
    protected int $importLogId;
    protected int $imported = 0;

    protected array $rowErrors = [];

    public function __construct(int $tahun, int $importLogId)
    {
        $this->tahun = $tahun;
        $this->importLogId = $importLogId;
    }

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
        return trim((string) ($v ?? ''));
    }

    private function normalizeNik(string $nik): string
    {
        $nik = trim($nik);

        // kalau excel bikin jadi 1.3221E+10 atau ada .0
        if ($nik !== '' && is_numeric($nik)) {
            $nik = rtrim(rtrim((string) $nik, '0'), '.');
        }

        // remove spasi dll
        $nik = preg_replace('/\s+/', '', $nik) ?? $nik;

        return $nik;
    }

    public function model(array $row)
    {
        // --- alias header (biar fleksibel) ---
        $kNik     = $this->resolveKey($row, ['nik', 'NIK']);
        $kKode    = $this->resolveKey($row, ['kode', 'kode_kompetensi', 'competency_code', 'kode kompetensi']);
        $kNama    = $this->resolveKey($row, ['nama_kompetensi', 'nama kompetensi', 'kompetensi', 'nama']);
        $kIdKom   = $this->resolveKey($row, ['id_kompetensi', 'id kompetensi', 'competency_id']);
        $kJobFam  = $this->resolveKey($row, ['job_family_kompetensi', 'job family', 'job_family']);
        $kSubJob  = $this->resolveKey($row, ['sub_job_family_kompetensi', 'sub job family', 'sub_job_family']);
        $kStatus  = $this->resolveKey($row, ['status']);
        $kNilai   = $this->resolveKey($row, ['nilai', 'score', 'skor']);
        $kDesk    = $this->resolveKey($row, ['deskripsi', 'description', 'keterangan']);

        $nik   = $this->normalizeNik($kNik ? $this->asString($row[$kNik] ?? '') : '');
        $kode  = $kKode ? $this->asString($row[$kKode] ?? '') : '';
        $nama  = $kNama ? $this->asString($row[$kNama] ?? '') : '';
        $idKom = $kIdKom ? $this->asString($row[$kIdKom] ?? '') : '';
        $job   = $kJobFam ? $this->asString($row[$kJobFam] ?? '') : '';
        $sub   = $kSubJob ? $this->asString($row[$kSubJob] ?? '') : '';
        $status= $kStatus ? $this->asString($row[$kStatus] ?? '') : '';
        $nilaiRaw = $kNilai ? $this->asString($row[$kNilai] ?? '') : '';
        $desk  = $kDesk ? $this->asString($row[$kDesk] ?? '') : '';

        if ($nik === '') {
            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => 'NIK kosong (baris di-skip).',
            ];
            return null;
        }

        // nilai boleh kosong → null
        $nilai = null;
        if ($nilaiRaw !== '') {
            // handle "80.0"
            if (is_numeric($nilaiRaw)) $nilai = (int) round((float) $nilaiRaw);
        }

        try {
            // ✅ benar-benar simpan ke DB
            // key unik: nik + tahun + kode (kalau kode kosong, fallback pakai nama)
            $uniqueKey = [
                'nik'   => $nik,
                'tahun' => $this->tahun,
                'kode'  => $kode !== '' ? $kode : ($nama !== '' ? $nama : 'UNKNOWN'),
            ];

            HardCompetency::updateOrCreate(
                $uniqueKey,
                [
                    'nik'                     => $nik,
                    'tahun'                   => $this->tahun,
                    'id_kompetensi'           => $idKom !== '' ? $idKom : null,
                    'kode'                    => $kode,
                    'nama_kompetensi'         => $nama,
                    'job_family_kompetensi'   => $job !== '' ? $job : null,
                    'sub_job_family_kompetensi'=> $sub !== '' ? $sub : null,
                    'status'                  => $status !== '' ? $status : null,
                    'nilai'                   => $nilai,
                    'deskripsi'               => $desk !== '' ? $desk : null,
                    'is_active'               => true,
                    'import_log_id'           => $this->importLogId,
                ]
            );

            $this->imported++;
            return null; // karena kita sudah persist manual
        } catch (\Throwable $e) {
            Log::warning('HARD COMPETENCY ROW ERROR', [
                'row' => $this->getRowNumber(),
                'nik' => $nik,
                'err' => $e->getMessage(),
            ]);

            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => $e->getMessage(),
                'nik' => $nik,
                'kode' => $kode,
            ];
            return null;
        }
    }

    public function rules(): array
    {
        // Dengan WithHeadingRow, key di rules harus sesuai header file.
        // Tapi karena header kamu bisa beda-beda, kita bikin minimal strict:
        // validasi utama kita lakukan manual (nik kosong, dsb).
        return [
            '*.nik' => ['nullable'], // biar tidak memblok semua baris kalau header beda
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
