<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\KaryawanImport;
use App\Imports\HardCompetencyImport;
use App\Imports\SoftCompetencyImport;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Import karyawan dari Excel/CSV (hanya admin).
     */
    public function importKaryawan(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv']
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $import = new KaryawanImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi error saat import karyawan.',
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }

        $failures = collect($import->failures())->map(function ($f) {
            return [
                'row'    => $f->row(),
                'errors' => $f->errors(),
                'values' => $f->values(),
            ];
        })->values();

        return response()->json([
            'message' => 'Proses import selesai.',
            'sukses'  => $import->getImportedCount(),
            'gagal'   => $failures,
            'catatan' => 'Header yang diterima: nik, nama/name, email, password.',
        ]);
    }

    /**
     * Import hard competency dari Excel/CSV (hanya admin).
     */
    public function importHardCompetencies(Request $request)
    {
        $data = $request->validate([
            'file'  => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Tambahan untuk log
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $uploadedAt   = now();

        // Proses import
        $import = new HardCompetencyImport($data['tahun']);
        Excel::import($import, $file);

        // Kumpulkan failures dalam dua bentuk:
        $failuresCollection = collect($import->failures());

        $failures = $failuresCollection->map(function ($f) {
            return [
                'row'    => $f->row(),
                'errors' => $f->errors(),
                'values' => $f->values(),
            ];
        })->values();

        $sukses = $import->getImportedCount();
        $gagalCount = $failuresCollection->count();

        // Simpan log ke DB
        ImportLog::create([
            'filename'    => $originalName,
            'type'        => 'hard',
            'tahun'       => $data['tahun'],
            'sukses'      => $sukses,
            'gagal'       => $gagalCount,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message'     => 'Proses import hard competency selesai.',
            'tahun'       => $data['tahun'],
            'sukses'      => $sukses,
            'gagal'       => $failures, // tetap kirim detail ke FE
            'filename'    => $originalName,
            'uploaded_at' => $uploadedAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Import soft competency dari Excel/CSV (hanya admin).
     */
    public function importSoftCompetencies(Request $request)
    {
        $data = $request->validate([
            'file'  => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Tambahan untuk log
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $uploadedAt   = now();

        // Proses import
        $import = new SoftCompetencyImport($data['tahun']);
        Excel::import($import, $file);

        $failuresCollection = collect($import->failures());

        $failures = $failuresCollection->map(function ($f) {
            return [
                'row'    => $f->row(),
                'errors' => $f->errors(),
                'values' => $f->values(),
            ];
        })->values();

        $sukses = $import->getImportedCount();
        $gagalCount = $failuresCollection->count();

        // Simpan log ke DB
        ImportLog::create([
            'filename'    => $originalName,
            'type'        => 'soft',
            'tahun'       => $data['tahun'],
            'sukses'      => $sukses,
            'gagal'       => $gagalCount,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message'     => 'Proses import soft competency selesai.',
            'tahun'       => $data['tahun'],
            'sukses'      => $sukses,
            'gagal'       => $failures, // tetap detail
            'filename'    => $originalName,
            'uploaded_at' => $uploadedAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * List karyawan.
     */
    public function listKaryawan(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('q', ''));

        $paginator = User::query()
            ->where('role', 'karyawan')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select('id', 'name', 'email', 'role', 'nik', 'created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
                'last'  => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }

    /**
     * Hapus karyawan.
     */
    public function deleteKaryawan(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role !== 'karyawan') {
            return response()->json(['message' => 'Hanya karyawan yang boleh dihapus.'], 422);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak boleh menghapus akun sendiri.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Karyawan berhasil dihapus.']);
    }

    /**
     * ✅ NEW: Reset password karyawan ke default (123).
     */
    public function resetKaryawanPassword(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role !== 'karyawan') {
            return response()->json(['message' => 'Hanya karyawan yang boleh direset passwordnya.'], 422);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak boleh reset password akun sendiri dari sini.'], 422);
        }

        // default password
        $defaultPassword = '123';

        // Karena 'password' => 'hashed' di User model, ini otomatis di-hash
        $user->password = $defaultPassword;
        $user->save();

        return response()->json([
            'message'          => 'Password karyawan berhasil direset ke default.',
            'default_password' => $defaultPassword, // optional
        ]);
    }

    /**
     * Bulk delete.
     */
    public function bulkDelete(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ]);

        $deleted = User::whereIn('id', $data['ids'])
            ->where('role', 'karyawan')
            ->where('id', '!=', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => 'Bulk delete selesai.',
            'deleted' => $deleted,
        ]);
    }

    /**
     * Log import (hard & soft) untuk admin.
     */
    public function importLogs(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = (int) $request->get('per_page', 20);
        $type    = $request->get('type');   // optional: 'hard' / 'soft'
        $tahun   = $request->get('tahun');  // optional: 2023, 2024, ...

        $query = ImportLog::with('uploader:id,name,email')->orderByDesc('id');

        if ($type) {
            $query->where('type', $type);
        }

        if ($tahun) {
            $query->where('tahun', (int) $tahun);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(function (ImportLog $log) {
                return [
                    'id'          => $log->id,
                    'filename'    => $log->filename,
                    'type'        => $log->type,
                    'tahun'       => $log->tahun,
                    'sukses'      => $log->sukses,
                    'gagal'       => $log->gagal,
                    'uploaded_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                    'uploaded_by' => [
                        'id'    => $log->uploader?->id,
                        'name'  => $log->uploader?->name,
                        'email' => $log->uploader?->email,
                    ],
                ];
            }),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
