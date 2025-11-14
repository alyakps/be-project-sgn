<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\KaryawanImport;
use App\Imports\HardCompetencyImport;
use App\Models\User;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Import karyawan dari Excel/CSV (hanya admin).
     * Body: multipart/form-data, field: file (.xlsx/.xls/.csv)
     */
    public function importKaryawan(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv']
        ]);

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $import = new KaryawanImport();
        Excel::import($import, $request->file('file'));

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
            'catatan' => 'Header yang diterima: nama/name, email, password. Case-insensitive & auto-trim.',
        ]);
    }

    /**
     * Import hard competency dari Excel/CSV (hanya admin).
     * Body: multipart/form-data
     *  - file  : .xlsx/.xls/.csv
     *  - tahun : 4 digit (misal 2025) -> dipilih di FE, tidak ada di Excel
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

        // Tahun diambil dari FE, bukan dari file Excel
        $import = new HardCompetencyImport($data['tahun']);
        Excel::import($import, $request->file('file'));

        $failures = collect($import->failures())->map(function ($f) {
            return [
                'row'    => $f->row(),
                'errors' => $f->errors(),
                'values' => $f->values(),
            ];
        })->values();

        return response()->json([
            'message' => 'Proses import hard competency selesai.',
            'tahun'   => $data['tahun'],
            'sukses'  => $import->getImportedCount(),
            'gagal'   => $failures,
        ]);
    }

    /**
     * List karyawan (hanya admin) dengan pencarian & pagination.
     * Query:
     *  - q        : keyword nama/email (opsional)
     *  - per_page : default 10 (opsional)
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

        // format respons API yang lebih bersih
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
     * Hapus satu karyawan (hanya admin).
     * Endpoint: DELETE /api/admin/karyawan/{user}
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
     * Hapus beberapa karyawan sekaligus (hanya admin).
     * Body JSON:
     * {
     *   "ids": [1,2,3]
     * }
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

        // hanya hapus role=karyawan & jangan hapus diri sendiri
        $deleted = User::whereIn('id', $data['ids'])
            ->where('role', 'karyawan')
            ->where('id', '!=', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => 'Bulk delete selesai.',
            'deleted' => $deleted,
        ]);
    }
}
