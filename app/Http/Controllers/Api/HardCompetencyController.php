<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HardCompetencyResource;
use App\Models\HardCompetency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->where('is_active', true) // ✅ ADDED (agar data canceled tidak tampil)
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
     * ✅ FIX: endpoint ini sekarang ngasih list PER NIK (grouped)
     * supaya hard competency yang di-import duluan tetap muncul walau user belum ada.
     *
     * Query Params:
     * - q=... (search nik / user_name / email)
     * - nik=... (filter nik like)
     * - tahun=... (optional)
     * - per_page=...
     */
    public function adminIndex(Request $request)
    {
        $search  = trim((string) $request->get('q', ''));
        $nik     = trim((string) $request->get('nik', ''));
        $perPage = (int) $request->get('per_page', 10);
        if ($perPage < 1) $perPage = 10;
        if ($perPage > 200) $perPage = 200;

        $tahun   = $request->integer('tahun'); // bisa null

        $query = HardCompetency::query()
            ->from('hard_competencies as hc')
            ->where('hc.is_active', true)
            ->when($tahun, fn ($q) => $q->where('hc.tahun', $tahun))
            ->when($nik !== '', fn ($q) => $q->where('hc.nik', 'like', "%{$nik}%"))
            ->leftJoin('users as u', function ($join) {
                $join->on('u.nik', '=', 'hc.nik')
                     ->where('u.role', '=', 'karyawan');
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('hc.nik', 'like', "%{$search}%")
                      ->orWhere('u.name', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%");
                });
            })
            ->select([
                'hc.nik',
                DB::raw('MAX(hc.tahun) as latest_year'),
                DB::raw('COUNT(*) as total_items'),
                DB::raw('MAX(u.id) as user_id'),
                DB::raw('MAX(u.name) as user_name'),
                DB::raw('MAX(u.email) as user_email'),
                DB::raw('CASE WHEN MAX(u.id) IS NULL THEN 0 ELSE 1 END as has_user'),
            ])
            ->groupBy('hc.nik')
            ->orderByDesc(DB::raw('MAX(hc.tahun)'))
            ->orderBy('hc.nik');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function ($r) {
                return [
                    'nik'         => $r->nik,
                    'has_user'    => (bool) $r->has_user,
                    'user_name'   => $r->user_name,
                    'user_email'  => $r->user_email,
                    'latest_year' => (int) $r->latest_year,
                    'total_items' => (int) $r->total_items,
                ];
            })->values(),
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
