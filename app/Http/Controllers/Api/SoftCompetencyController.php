<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SoftCompetencyResource;
use App\Models\SoftCompetency;
use Illuminate\Http\Request;

class SoftCompetencyController extends Controller
{
    /**
     * (KARYAWAN ONLY)
     *
     * GET /api/karyawan/soft-competencies?tahun=2025
     */
    public function indexSelf(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->nik) {
            return response()->json([
                'message' => 'User tidak memiliki NIK, tidak bisa mengambil data soft competency.',
            ], 422);
        }

        $tahun = $request->integer('tahun'); // bisa null

        $userRows = SoftCompetency::forNik($user->nik)
            ->forYear($tahun)
            ->orderBy('kode')
            ->get();

        if ($userRows->isEmpty()) {
            return response()->json([
                'data' => [
                    'nik'   => $user->nik,
                    'tahun' => $tahun,
                    'chart' => [],
                    'items' => [],
                ],
            ]);
        }

        $avgByKom = SoftCompetency::forYear($tahun)
            ->whereIn('id_kompetensi', $userRows->pluck('id_kompetensi'))
            ->selectRaw('id_kompetensi, AVG(nilai) as avg_nilai')
            ->groupBy('id_kompetensi')
            ->pluck('avg_nilai', 'id_kompetensi');

        $chart = $userRows->map(function ($row) use ($avgByKom) {
            $avg = $avgByKom[$row->id_kompetensi] ?? null;

            if ($avg !== null) {
                $avgRounded = round($avg, 1);
                $avgLevel   = $this->scoreLevel((int) round($avgRounded));
            } else {
                $avgRounded = null;
                $avgLevel   = null;
            }

            return [
                'id_kompetensi'        => $row->id_kompetensi,
                'kode'                 => $row->kode,
                'nama_kompetensi'      => $row->nama_kompetensi,
                'your_score'           => (int) $row->nilai,
                'your_level'           => $this->scoreLevel($row->nilai),
                'avg_employee_score'   => $avgRounded,
                'avg_level'            => $avgLevel,
            ];
        })->values();

        $items = $userRows->map(function ($row) {
            return [
                'id_kompetensi'   => $row->id_kompetensi,
                'kode'            => $row->kode,
                'nama_kompetensi' => $row->nama_kompetensi,
                'status'          => $row->status,
                'nilai'           => $row->nilai,
                'level'           => $this->scoreLevel($row->nilai),
                'deskripsi'       => $row->deskripsi,
            ];
        })->values();

        return response()->json([
            'data' => [
                'nik'   => $user->nik,
                'tahun' => $tahun,
                'chart' => $chart,
                'items' => $items,
            ],
        ]);
    }

    /**
     * (ADMIN ONLY)
     *
     * GET /api/admin/karyawan/{nik}/soft-competencies
     *
     * Admin lihat soft competency 1 karyawan (by NIK) dengan pagination.
     * Query:
     *  - q        : keyword (kode/nama/status/deskripsi)
     *  - tahun    : tahun penilaian
     *  - per_page : default 10
     */
    public function adminByNik(Request $request, string $nik)
    {
        return $this->buildListForNik($request, $nik);
    }

    /**
     * (ADMIN ONLY)
     *
     * GET /api/admin/soft-competencies
     *
     * Admin lihat list global soft competency dengan filter & pagination.
     * Query:
     *  - nik      : filter per NIK tertentu (opsional)
     *  - q        : keyword (kode/nama/status/deskripsi)
     *  - tahun    : tahun penilaian
     *  - per_page : default 10
     */
    public function adminIndex(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $nik     = trim((string) $request->get('nik', ''));
        $perPage = (int) $request->get('per_page', 10);
        $tahun   = $request->integer('tahun'); // bisa null

        $query = SoftCompetency::query()
            ->when($nik !== '', fn ($q) => $q->forNik($nik))
            ->forYear($tahun)
            ->search($search)
            ->orderBy('nik')
            ->orderBy('kode');

        $paginator = $query->paginate($perPage);

        $items = SoftCompetencyResource::collection($paginator->items())->resolve();

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
     * Helper untuk build list soft competency berdasarkan NIK (admin / karyawan).
     */
    protected function buildListForNik(Request $request, string $nik)
    {
        $search  = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);
        $tahun   = $request->integer('tahun'); // bisa null

        $query = SoftCompetency::forNik($nik)
            ->forYear($tahun)
            ->search($search)
            ->orderBy('kode');

        $paginator = $query->paginate($perPage);

        $items = SoftCompetencyResource::collection($paginator->items())->resolve();

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

    protected function scoreLevel(?int $score): ?string
    {
        if ($score === null) {
            return null;
        }

        if ($score <= 69) {
            return 'Low';
        }

        if ($score <= 85) {
            return 'Middle';
        }

        return 'High';
    }
}
