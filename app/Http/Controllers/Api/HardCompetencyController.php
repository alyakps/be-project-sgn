<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HardCompetencyResource;
use App\Models\HardCompetency;
use Illuminate\Http\Request;

class HardCompetencyController extends Controller
{
    /**
<<<<<<< HEAD
     * Tampilkan daftar hard competency berdasarkan NIK user yang login.
     * GET /api/karyawan/{nik}/hard-competencies
     */
    public function index(Request $request, string $nik)
    {
        $user = $request->user();

        // ✅ Pastikan user hanya bisa akses datanya sendiri
        if ($user->nik !== $nik) {
=======
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
>>>>>>> 8be18af (update api hard competency)
            return response()->json([
                'message' => 'Forbidden: Anda tidak bisa mengakses data karyawan lain.'
            ], 403);
        }

<<<<<<< HEAD
        $data = HardCompetency::where('nik', $nik)
            ->select([
                'nik',
                'id_kompetensi',
                'kode',
                'nama_kompetensi',
                'job_family_kompetensi',
                'sub_job_family_kompetensi',
                'status',
                'nilai',
                'deskripsi'
            ])
            ->orderBy('nilai', 'desc')
            ->get();

        return HardCompetencyResource::collection($data);
=======
        $search  = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 10);

        $query = HardCompetency::forNik($nik)
            ->search($search)
            ->orderBy('nilai', 'desc');

        $paginator = $query->paginate($perPage);

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
     *
     * Admin bisa:
     *  - lihat semua data
     *  - filter per NIK
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

        return HardCompetencyResource::collection($paginator)
            ->additional([
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
>>>>>>> 8be18af (update api hard competency)
    }
}
