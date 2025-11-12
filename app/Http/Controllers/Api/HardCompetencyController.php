<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HardCompetencyResource;
use App\Models\HardCompetency;
use Illuminate\Http\Request;

class HardCompetencyController extends Controller
{
    /**
     * Tampilkan daftar hard competency berdasarkan NIK user yang login.
     * GET /api/karyawan/{nik}/hard-competencies
     */
    public function index(Request $request, string $nik)
    {
        $user = $request->user();

        // ✅ Pastikan user hanya bisa akses datanya sendiri
        if ($user->nik !== $nik) {
            return response()->json([
                'message' => 'Forbidden: Anda tidak bisa mengakses data karyawan lain.'
            ], 403);
        }

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
    }
}
