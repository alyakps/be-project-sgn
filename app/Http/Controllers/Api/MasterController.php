<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class MasterController extends Controller
{
    /**
     * GET /api/master/unit-kerja
     * Mengembalikan daftar unit kerja dari DATABASE (distinct).
     * Kalau DB kosong, fallback ke CSV.
     *
     * Response:
     * {
     *   "data": ["Divisi Sekretariat Perusahaan", ...]
     * }
     */
    public function unitKerja()
    {
        // 1) Ambil dari DB dulu (dibatasi: karyawan aktif)
        $dbList = User::query()
            ->where('role', 'karyawan')      // ✅ biar relevan dengan listKaryawan
            ->where('is_active', true)       // ✅ biar dropdown match default list (active only)
            ->whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->distinct()
            ->orderBy('unit_kerja')
            ->pluck('unit_kerja')
            ->values()
            ->all();

        if (count($dbList) > 0) {
            return response()->json([
                'data' => $dbList,
            ]);
        }

        // 2) Fallback: baca dari CSV (logic lama tetap dipakai)
        $relativePath = 'master/unit_kerja.csv';
        $fullPath = storage_path('app/' . $relativePath);

        if (!file_exists($fullPath)) {
            return response()->json([
                'data'    => [],
                'message' => 'DB unit_kerja kosong dan file unit_kerja.csv tidak ditemukan di path: ' . $fullPath,
            ], 404);
        }

        $content = trim(file_get_contents($fullPath));
        if ($content === '') {
            return response()->json([
                'data' => [],
                'message' => 'DB unit_kerja kosong dan file unit_kerja.csv kosong.',
            ]);
        }

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $rows = array_map('str_getcsv', $lines);

        $firstRow = $rows[0] ?? null;
        if ($firstRow && stripos($firstRow[0] ?? '', 'unit') !== false) {
            array_shift($rows);
        }

        $list = [];
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) === 0) continue;

            $nama = $row[0] ?? null;
            $nama = $nama !== null ? trim($nama) : null;

            if ($nama !== null && $nama !== '') {
                $list[] = $nama;
            }
        }

        return response()->json([
            'data' => array_values($list),
        ]);
    }
}
