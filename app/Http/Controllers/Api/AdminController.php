<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\KaryawanImport;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Import karyawan dari file Excel/CSV.
     * Hanya boleh oleh user dengan role=admin.
     *
     * Body (multipart/form-data):
     * - file: .xlsx/.xls/.csv dengan header: nama|name, email, password
     */
    public function importKaryawan(Request $request)
    {
        // validasi file
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv']
        ]);

        // pastikan hanya admin
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // jalankan import
        $import = new KaryawanImport();
        Excel::import($import, $request->file('file'));

        // rangkum hasil
        $failures = collect($import->failures())->map(function ($f) {
            return [
                'row'    => $f->row(),     // nomor baris (termasuk heading row)
                'errors' => $f->errors(),  // pesan error validasi
                'values' => $f->values(),  // data baris terkait
            ];
        })->values();

        return response()->json([
            'message' => 'Proses import selesai.',
            'sukses'  => $import->getImportedCount(),
            'gagal'   => $failures,
            'catatan' => 'Header yang diterima: nama/name, email, password. Case-insensitive & auto-trim.',
        ]);
    }
}
