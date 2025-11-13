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
     *  - per_page : default 10
     *
     * Karyawan: hanya boleh akses NIK miliknya sendiri.
     * Admin   : boleh akses NIK siapa pun.
     */
    public function indexSelf(Request $request, string $nik)
    {
        $user = $request->user();

        // ❌ Kalau bukan admin & NIK bukan miliknya sendiri → tolak
        if ($user->role !== 'admin' && $user->nik !== $nik) {
            return response()->json([
                'message' => 'Forbidden: Anda tidak bisa mengakses data karyawan lain.'
            ], 403);
        }

        $search  = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);

        $query = HardCompetency::forNik($nik)
            ->search($search)
            ->orderBy('nilai', 'desc');

        $paginator = $query->paginate($perPage);

        // boleh pakai resource langsung, biar simple
        return HardCompetencyResource::collection($paginator)
            ->additional([
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
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
     *  - per_page : default 10
     */
    public function adminIndex(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $nik     = trim((string) $request->get('nik', ''));
        $perPage = (int) $request->get('per_page', 10);

        $query = HardCompetency::query()
            ->when($nik !== '', fn($q) => $q->forNik($nik))
            ->search($search)
            ->orderBy('nik')
            ->orderByDesc('nilai');

        $paginator = $query->paginate($perPage);

        // ✅ format pagination mirip listKaryawan
        return response()->json([
            'data' => HardCompetencyResource::collection($paginator->items()),
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
}
