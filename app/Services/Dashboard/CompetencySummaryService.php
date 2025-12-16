<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompetencySummaryService
{
    public static function execute(?string $unitKerja, array $years): array
    {
        $unitKerja = is_string($unitKerja) ? trim($unitKerja) : '';
        $isAll = $unitKerja === '' || strtolower($unitKerja) === 'all';

        $years = array_values(array_filter(array_map(
            fn ($y) => is_numeric($y) ? (int) $y : null,
            $years
        )));

        $hardTable = 'hard_competencies';
        $softTable = 'soft_competencies';

        // key user
        $userCol = 'nik';

        if (!Schema::hasColumn($hardTable, $userCol)) {
            throw new \RuntimeException("Kolom '{$userCol}' tidak ditemukan di tabel {$hardTable}.");
        }
        if (!Schema::hasColumn($softTable, $userCol)) {
            throw new \RuntimeException("Kolom '{$userCol}' tidak ditemukan di tabel {$softTable}.");
        }

        // kolom tahun & skor
        $hardYearCol = self::detectFirstExistingColumn($hardTable, ['tahun', 'tahun_penilaian', 'year']);
        $softYearCol = self::detectFirstExistingColumn($softTable, ['tahun', 'tahun_penilaian', 'year']);
        if (!$hardYearCol) throw new \RuntimeException("Kolom tahun tidak ditemukan di {$hardTable} (coba: tahun/tahun_penilaian/year).");
        if (!$softYearCol) throw new \RuntimeException("Kolom tahun tidak ditemukan di {$softTable} (coba: tahun/tahun_penilaian/year).");

        $hardScoreCol = self::detectFirstExistingColumn($hardTable, ['nilai', 'score', 'value']);
        $softScoreCol = self::detectFirstExistingColumn($softTable, ['nilai', 'score', 'value']);
        if (!$hardScoreCol) throw new \RuntimeException("Kolom nilai/score/value tidak ditemukan di {$hardTable}.");
        if (!$softScoreCol) throw new \RuntimeException("Kolom nilai/score/value tidak ditemukan di {$softTable}.");

        // ====== unit kerja ada di tabel profile/user (bukan di competencies)
        $profileTable = null;
        $profileNikCol = null;
        $profileUnitCol = null;

        if (
            Schema::hasTable('employee_profiles') &&
            Schema::hasColumn('employee_profiles', 'nik') &&
            Schema::hasColumn('employee_profiles', 'unit_kerja')
        ) {
            $profileTable = 'employee_profiles';
            $profileNikCol = 'nik';
            $profileUnitCol = 'unit_kerja';
        } elseif (
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'nik') &&
            Schema::hasColumn('users', 'unit_kerja')
        ) {
            $profileTable = 'users';
            $profileNikCol = 'nik';
            $profileUnitCol = 'unit_kerja';
        }

        if (!$isAll && !$profileTable) {
            throw new \RuntimeException(
                "Filter unit kerja butuh tabel relasi (employee_profiles atau users) yang punya kolom nik + unit_kerja."
            );
        }

        /**
         * ✅ NORMALISASI NIK
         * Kasus umum: dari Excel NIK bisa jadi "5025211174.0" atau ada spasi.
         */
        $normalizeNikPhp = function ($v): string {
            $s = trim((string) $v);
            // hilangkan spasi
            $s = str_replace(' ', '', $s);
            // hilangkan kasus excel ".0" di akhir
            if (str_ends_with($s, '.0')) {
                $s = substr($s, 0, -2);
            }
            return $s;
        };

        // expression SQL untuk membandingkan nik secara "aman"
        // (trim, hapus spasi, hapus ".0" di akhir)
        $normNikSql = function (string $col): string {
            // REPLACE(..., '.0', '') akan menghapus ".0" jika ada (umum dari excel)
            return "REPLACE(REPLACE(TRIM({$col}), ' ', ''), '.0', '')";
        };

        // ✅ ambil daftar NIK untuk unit kerja (lebih aman daripada JOIN langsung)
        $niksForUnit = [];
        if (!$isAll) {
            $niksForUnit = DB::table($profileTable)
                ->whereNotNull($profileNikCol)
                ->whereRaw('LOWER(TRIM(' . $profileUnitCol . ')) = LOWER(?)', [$unitKerja])
                ->pluck($profileNikCol)
                ->map(fn ($v) => $normalizeNikPhp($v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            // kalau unit kerja ada, tapi nik list kosong → pasti mapping profile-nya belum keisi
            if (empty($niksForUnit)) {
                return [
                    'unit_kerja' => $unitKerja,
                    'years_available' => [],
                    'data' => [],
                ];
            }
        }

        /**
         * STEP 1 — HARD: AVG per NIK per tahun (adil)
         */
        $hardPerUser = DB::table($hardTable . ' as hc')
            ->selectRaw("
                hc.{$hardYearCol} AS tahun,
                " . $normNikSql('hc.' . $userCol) . " AS nik,
                AVG(hc.{$hardScoreCol}) AS avg_hard_user
            ");

        if (!$isAll) {
            // ✅ bandingkan nik yang sudah dinormalisasi
            $hardPerUser->whereIn(DB::raw($normNikSql('hc.' . $userCol)), $niksForUnit);
        }

        if ($isAll && !empty($years)) {
            $hardPerUser->whereIn('hc.' . $hardYearCol, $years);
        }

        $hardPerUser->groupBy('tahun', 'nik');

        $hardByYear = DB::query()
            ->fromSub($hardPerUser, 'h')
            ->selectRaw('
                tahun,
                ROUND(AVG(avg_hard_user), 1) AS avg_hard,
                NULL AS avg_soft
            ')
            ->groupBy('tahun');

        /**
         * STEP 2 — SOFT: AVG per NIK per tahun (adil)
         */
        $softPerUser = DB::table($softTable . ' as sc')
            ->selectRaw("
                sc.{$softYearCol} AS tahun,
                " . $normNikSql('sc.' . $userCol) . " AS nik,
                AVG(sc.{$softScoreCol}) AS avg_soft_user
            ");

        if (!$isAll) {
            // ✅ bandingkan nik yang sudah dinormalisasi
            $softPerUser->whereIn(DB::raw($normNikSql('sc.' . $userCol)), $niksForUnit);
        }

        if ($isAll && !empty($years)) {
            $softPerUser->whereIn('sc.' . $softYearCol, $years);
        }

        $softPerUser->groupBy('tahun', 'nik');

        $softByYear = DB::query()
            ->fromSub($softPerUser, 's')
            ->selectRaw('
                tahun,
                NULL AS avg_hard,
                ROUND(AVG(avg_soft_user), 1) AS avg_soft
            ')
            ->groupBy('tahun');

        /**
         * STEP 3 — merge hard+soft per tahun
         * ✅ pakai MAX biar ambil nilai non-null hasil UNION
         */
        $union = $hardByYear->unionAll($softByYear);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->selectRaw('
                tahun,
                ROUND(MAX(avg_hard), 1) AS avg_hard,
                ROUND(MAX(avg_soft), 1) AS avg_soft
            ')
            ->groupBy('tahun')
            ->orderBy('tahun', 'asc')
            ->get();

        return [
            'unit_kerja' => $isAll ? 'All' : $unitKerja,
            'years_available' => $rows->pluck('tahun')->values(),
            'data' => $rows,
        ];
    }

    private static function detectFirstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) return $col;
        }
        return null;
    }
}
