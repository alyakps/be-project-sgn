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
     * GET /api/karyawan/soft-competencies?tahun=2025&per_page=10&page=1
     */
    public function indexSelf(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->nik) {
            return response()->json([
                'message' => 'User tidak memiliki NIK, tidak bisa mengambil data soft competency.',
            ], 422);
        }

        $tahun   = $request->integer('tahun'); // bisa null
        $perPage = (int) $request->get('per_page', 10);

        // =========================
        // 0) AMBIL DAFTAR TAHUN YANG ADA UNTUK USER INI
        // =========================
        $availableYears = SoftCompetency::forNik($user->nik)
            ->selectRaw('DISTINCT tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->values();

        // =========================
        // 1) AMBIL SEMUA ROW (UNTUK CHART)
        // =========================
        $allRows = SoftCompetency::forNik($user->nik)
            ->forYear($tahun)
            ->orderBy('kode')
            ->get();

        if ($allRows->isEmpty()) {
            return response()->json([
                'data' => [
                    'nik'             => $user->nik,
                    'tahun'           => $tahun,
                    'available_years' => $availableYears, // ⬅️ KIRIM JUGA LIST TAHUN
                    'chart'           => [],
                    'items'           => [],
                ],
                'meta' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
                'links' => [
                    'first' => null,
                    'prev'  => null,
                    'next'  => null,
                    'last'  => null,
                ],
            ]);
        }

        // Hitung rata-rata per id_kompetensi (semua karyawan)
        $avgByKom = SoftCompetency::forYear($tahun)
            ->whereIn('id_kompetensi', $allRows->pluck('id_kompetensi'))
            ->selectRaw('id_kompetensi, AVG(nilai) as avg_nilai')
            ->groupBy('id_kompetensi')
            ->pluck('avg_nilai', 'id_kompetensi');

        // =========================
        // 2) BUILD DATA CHART (TANPA PAGINATION)
        // =========================
        $chart = $allRows->map(function ($row) use ($avgByKom) {
            $avg = $avgByKom[$row->id_kompetensi] ?? null;

            if ($avg !== null) {
                $avgRounded = round($avg, 1);
                $avgLevel   = $this->scoreLevel((int) round($avgRounded));
            } else {
                $avgRounded = null;
                $avgLevel   = null;
            }

            return [
                'id_kompetensi'      => $row->id_kompetensi,
                'kode'               => $row->kode,
                'nama_kompetensi'    => $row->nama_kompetensi,
                'your_score'         => (int) $row->nilai,
                'your_level'         => $this->scoreLevel($row->nilai),
                'avg_employee_score' => $avgRounded,
                'avg_level'          => $avgLevel,
            ];
        })->values();

        // =========================
        // 3) QUERY PAGINATED ITEMS
        // =========================
        $itemsQuery = SoftCompetency::forNik($user->nik)
            ->forYear($tahun)
            ->orderBy('kode');

        $paginator = $itemsQuery->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
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
                'nik'             => $user->nik,
                'tahun'           => $tahun,
                'available_years' => $availableYears, // ⬅️ DIKIRIM KE FRONTEND
                'chart'           => $chart,
                'items'           => $items,
            ],
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
     * (ADMIN ONLY)
     *
     * GET /api/admin/karyawan/{nik}/soft-competencies?tahun=2025&per_page=10
     *
     * Admin lihat soft competency 1 karyawan (by NIK) dengan
     * struktur mirip indexSelf: nik + tahun + available_years + chart + items.
     */
    public function adminByNik(Request $request, string $nik)
    {
        $tahun   = $request->integer('tahun'); // bisa null
        $perPage = (int) $request->get('per_page', 10);

        // =========================
        // 0) AMBIL DAFTAR TAHUN YANG ADA UNTUK NIK INI
        // =========================
        $availableYears = SoftCompetency::forNik($nik)
            ->selectRaw('DISTINCT tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->values();

        // =========================
        // 1) AMBIL SEMUA ROW (UNTUK CHART)
        // =========================
        $allRows = SoftCompetency::forNik($nik)
            ->forYear($tahun)
            ->orderBy('kode')
            ->get();

        if ($allRows->isEmpty()) {
            return response()->json([
                'data' => [
                    'nik'             => $nik,
                    'tahun'           => $tahun,
                    'available_years' => $availableYears,
                    'chart'           => [],
                    'items'           => [],
                ],
                'meta' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
                'links' => [
                    'first' => null,
                    'prev'  => null,
                    'next'  => null,
                    'last'  => null,
                ],
            ]);
        }

        // =========================
        // 2) Hitung rata-rata per id_kompetensi (semua karyawan di tahun tsb)
        // =========================
        $avgByKom = SoftCompetency::forYear($tahun)
            ->whereIn('id_kompetensi', $allRows->pluck('id_kompetensi'))
            ->selectRaw('id_kompetensi, AVG(nilai) as avg_nilai')
            ->groupBy('id_kompetensi')
            ->pluck('avg_nilai', 'id_kompetensi');

        // =========================
        // 3) BUILD DATA CHART (TANPA PAGINATION)
        // =========================
        $chart = $allRows->map(function ($row) use ($avgByKom) {
            $avg = $avgByKom[$row->id_kompetensi] ?? null;

            if ($avg !== null) {
                $avgRounded = round($avg, 1);
                $avgLevel   = $this->scoreLevel((int) round($avgRounded));
            } else {
                $avgRounded = null;
                $avgLevel   = null;
            }

            return [
                'id_kompetensi'      => $row->id_kompetensi,
                'kode'               => $row->kode,
                'nama_kompetensi'    => $row->nama_kompetensi,
                'your_score'         => (int) $row->nilai,
                'your_level'         => $this->scoreLevel($row->nilai),
                'avg_employee_score' => $avgRounded,
                'avg_level'          => $avgLevel,
            ];
        })->values();

        // =========================
        // 4) QUERY PAGINATED ITEMS BUAT TABEL
        // =========================
        $itemsQuery = SoftCompetency::forNik($nik)
            ->forYear($tahun)
            ->orderBy('kode');

        $paginator = $itemsQuery->paginate($perPage);

        $items = collect($paginator->items())->map(function ($row) {
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
                'nik'             => $nik,
                'tahun'           => $tahun,
                'available_years' => $availableYears,
                'chart'           => $chart,
                'items'           => $items,
            ],
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
     * (Sekarang tidak dipakai di adminByNik, tapi boleh disimpan kalau mau reuse untuk endpoint lain.)
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
