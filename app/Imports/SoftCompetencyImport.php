<?php

namespace App\Imports;

use App\Models\SoftCompetency;
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

    /**
     * NOTE:
     * - Jangan trim nol di belakang (bisa ubah NIK)
     * - Handle ".0" dari Excel
     */
    private function normalizeNik(string $nik): string
    {
        $nik = trim($nik);
        $nik = preg_replace('/\s+/', '', $nik) ?? $nik;
        $nik = preg_replace('/\.0+$/', '', $nik) ?? $nik;
        return $nik;
    }

    public function model(array $row)
    {
        $kNik    = $this->resolveKey($row, ['nik', 'NIK']);
        $kKode   = $this->resolveKey($row, ['kode', 'kode_kompetensi', 'competency_code', 'kode kompetensi']);
        $kNama   = $this->resolveKey($row, ['nama_kompetensi', 'nama kompetensi', 'kompetensi', 'nama']);
        $kIdKom  = $this->resolveKey($row, ['id_kompetensi', 'id kompetensi', 'competency_id']);
        $kStatus = $this->resolveKey($row, ['status']);
        $kNilai  = $this->resolveKey($row, ['nilai', 'score', 'skor']);
        $kDesk   = $this->resolveKey($row, ['deskripsi', 'description', 'keterangan']);

        $nik   = $this->normalizeNik($kNik ? $this->asString($row[$kNik] ?? '') : '');
        $kode  = $kKode ? $this->asString($row[$kKode] ?? '') : '';
        $nama  = $kNama ? $this->asString($row[$kNama] ?? '') : '';
        $idKom = $kIdKom ? $this->asString($row[$kIdKom] ?? '') : '';
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

        // ✅ PENTING: match unique DB soft_competencies (nik + id_kompetensi + tahun)
        // Migration kamu id_kompetensi TIDAK nullable → kalau kosong harus skip
        if ($idKom === '') {
            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => 'id_kompetensi kosong (baris di-skip agar tidak overwrite & sesuai unique).',
                'nik' => $nik,
                'kode' => $kode,
                'nama' => $nama,
            ];
            return null;
        }

        $nilai = null;
        if ($nilaiRaw !== '') {
            if (is_numeric($nilaiRaw)) $nilai = (int) round((float) $nilaiRaw);
        }

        try {
            $uniqueKey = [
                'nik'           => $nik,
                'tahun'         => $this->tahun,
                'id_kompetensi' => $idKom,
            ];

            SoftCompetency::updateOrCreate(
                $uniqueKey,
                [
                    'nik'             => $nik,
                    'tahun'           => $this->tahun,
                    'id_kompetensi'   => $idKom,
                    'kode'            => $kode,
                    'nama_kompetensi' => $nama,
                    'status'          => $status !== '' ? $status : null,
                    'nilai'           => $nilai,
                    'deskripsi'       => $desk !== '' ? $desk : null,
                    'is_active'       => true,
                    'import_log_id'   => $this->importLogId,
                ]
            );

            $this->imported++;
            return null;
        } catch (\Throwable $e) {
            Log::warning('SOFT COMPETENCY ROW ERROR', [
                'row' => $this->getRowNumber(),
                'nik' => $nik,
                'id_kompetensi' => $idKom,
                'err' => $e->getMessage(),
            ]);

            $this->rowErrors[] = [
                'row' => $this->getRowNumber(),
                'message' => $e->getMessage(),
                'nik' => $nik,
                'id_kompetensi' => $idKom,
                'kode' => $kode,
            ];
            return null;
        }
    }

    public function rules(): array
    {
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
