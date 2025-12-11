<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class MasterController extends Controller
{
    /**
     * GET /api/master/unit-kerja
     * Mengembalikan daftar unit kerja dari CSV.
     *
     * Response:
     * {
     *   "data": ["Divisi Sekretariat Perusahaan", ...]
     * }
     */
    public function unitKerja()
    {
        // relative dari folder storage/app
        $relativePath = 'master/unit_kerja.csv';

        // full path di filesystem (otomatis pakai separator yang benar untuk Windows/Linux)
        $fullPath = storage_path('app/' . $relativePath);

        // 🔍 Debug 1: cek file beneran ada di sini
        if (!file_exists($fullPath)) {
            return response()->json([
                'data'    => [],
                'message' => 'File unit_kerja.csv tidak ditemukan di path: ' . $fullPath,
            ], 404);
        }

        // 🔍 Debug 2: baca isinya
        $content = trim(file_get_contents($fullPath));
        if ($content === '') {
            return response()->json([
                'data' => [],
                'message' => 'File unit_kerja.csv kosong.',
            ]);
        }

        // Pecah baris aman untuk Windows/Linux (CRLF / LF / CR)
        $lines = preg_split("/\r\n|\n|\r/", $content);

        // Konversi tiap baris ke array CSV
        $rows = array_map('str_getcsv', $lines);

        // Buang header kalau memang baris pertama header (opsional)
        // Kalau file kamu TIDAK pakai header, hapus 3 baris berikut:
        $firstRow = $rows[0] ?? null;
        if ($firstRow && stripos($firstRow[0] ?? '', 'unit') !== false) {
            array_shift($rows);
        }

        $list = [];
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) === 0) {
                continue;
            }

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
