<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HardCompetencyResource;
use App\Models\HardCompetency;
use Illuminate\Http\Request;

class HardCompetencyController extends Controller
{
    /**
     * (KARYAWAN + ADMIN)
     *
     * GET /api/karyawan/{nik}/hard-competencies
     * Query (optional):
     *  - q        : keyword (kode/nama/job_family/sub_job/deskripsi)
     *  - tahun    : tahun penilaian
     *  - per_page : default 10
     */
    public function indexSelf(Request $request, string $nik)
    {
        $user = $request->user();

        // Karyawan hanya boleh akses NIK miliknya sendiri
        if ($user->role !== 'admin' && $user->nik !== $nik) {
            return response()->json([
                'message' => 'Forbidden: Anda tidak bisa mengakses data karyawan lain.',
            ], 403);
        }

        $search  = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $tahun   = $request->integer('tahun'); // bisa null

        $query = HardCompetency::forNik($nik)
            ->forYear($tahun)
            ->search($search)
            ->orderBy('nilai', 'desc');

        $paginator = $query->paginate($perPage);

        // Ubah koleksi model jadi array pakai Resource, TANPA nambah meta/links bawaan lagi
        $items = HardCompetencyResource::collection($paginator->items())->resolve();

        return response()->json([
            'data'  => $items,
            'meta'  => [
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
     * (ADMIN ONLY)
     *
     * GET /api/admin/hard-competencies
     *
     * Query (optional):
     *  - nik      : filter per NIK tertentu
     *  - q        : keyword (kode/nama/job_family/sub_job/deskripsi)
     *  - tahun    : tahun penilaian
     *  - per_page : default 10
     */
    public function adminIndex(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $nik     = trim((string) $request->get('nik', ''));
        $perPage = (int) $request->get('per_page', 10);
        $tahun   = $request->integer('tahun'); // bisa null

        $query = HardCompetency::query()
            ->when($nik !== '', fn ($q) => $q->forNik($nik))
            ->forYear($tahun)
            ->search($search)
            ->orderBy('nik')
            ->orderByDesc('nilai');

        $paginator = $query->paginate($perPage);

        $items = HardCompetencyResource::collection($paginator->items())->resolve();

        return response()->json([
            'data'  => $items,
            'meta'  => [
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
}