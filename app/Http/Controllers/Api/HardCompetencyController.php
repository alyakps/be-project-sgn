<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HardCompetencyResource;
use App\Models\HardCompetency;
use Illuminate\Http\Request;

class HardCompetencyController extends Controller
{
    /**
     * (KARYAWAN ONLY)
     *
     * GET /api/karyawan/hard-competencies
     *
     * Query (optional):
     *  - q        : keyword (kode/nama/job_family/sub_job/deskripsi)
     *  - tahun    : tahun penilaian
     *  - per_page : default 10
     *
     * NIK diambil dari user yang login, jadi karyawan
     * hanya bisa melihat kompetensi miliknya sendiri.
     */
    public function indexSelf(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->nik) {
            return response()->json([
                'message' => 'User tidak memiliki NIK, tidak bisa mengambil data hard competency.',
            ], 422);
        }

        return $this->buildListForNik($request, $user->nik);
    }

    /**
     * (ADMIN ONLY)
     *
     * GET /api/admin/karyawan/{nik}/hard-competencies
     *
     * Admin bisa melihat hard competency karyawan tertentu berdasarkan NIK.
     */
    public function adminByNik(Request $request, string $nik)
    {
        // Middleware sudah menjamin user adalah admin,
        // jadi di sini tinggal ambil data berdasarkan NIK.
        return $this->buildListForNik($request, $nik);
    }

    /**
     * Helper untuk build list hard competency berdasarkan NIK.
     */
    protected function buildListForNik(Request $request, string $nik)
    {
        $search  = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $tahun   = $request->integer('tahun'); // bisa null

        $query = HardCompetency::forNik($nik)
            ->forYear($tahun)
            ->search($search)
            ->orderBy('nilai', 'desc');

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

    /**
     * (ADMIN ONLY)
     *
     * GET /api/admin/hard-competencies
     *
     * List global dengan filter (sudah ada, tetap).
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
