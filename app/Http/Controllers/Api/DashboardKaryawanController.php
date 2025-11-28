<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HardCompetency;
use App\Models\SoftCompetency;
use App\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardKaryawanController extends Controller
{
    /**
     * Summary dashboard karyawan.
     *
     * GET /api/dashboard/karyawan/summary
     *
     * Query (opsional):
     *  - tahun : 4 digit, contoh ?tahun=2025
     *
     * Response:
     * {
     *   "data": {
     *     "nik": "5025211174",
     *     "tahun": 2025,
     *     "profile": {
     *       "name": "Budi Santoso",
     *       "nik": "5025211174",
     *       "jabatan": "Staff IT",
     *       "unit_kerja": "IT Department",
     *       "photo_url": "http://localhost:8000/storage/employee_photos/xxx.jpg"
     *     },
     *     "hard_competency": { ... },
     *     "soft_competency": { ... }
     *   }
     * }
     */
    public function summary(Request $request)
    {
        // load user + profile
        $user = $request->user()->load('profile');

        if (!$user || !$user->nik) {
            return response()->json([
                'message' => 'User tidak memiliki NIK, tidak bisa mengambil summary.'
            ], 422);
        }

        // kalau belum punya profile → buat default dulu (biar nggak null)
        $profile = $user->profile ?? EmployeeProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap'  => $user->name,
                'nik'           => $user->nik,
                'email_pribadi' => $user->email,
            ]
        );

        // tahun opsional, kalau null → semua tahun
        $tahun = $request->integer('tahun');

        // =========================
        // HARD COMPETENCY SUMMARY
        // =========================
        $hardQuery   = HardCompetency::forNik($user->nik)->forYear($tahun);
        $hardSummary = $this->buildSummary($hardQuery);

        // =========================
        // SOFT COMPETENCY SUMMARY
        // =========================
        $softQuery   = SoftCompetency::forNik($user->nik)->forYear($tahun);
        $softSummary = $this->buildSummary($softQuery);

        // =========================
        // PROFILE SUMMARY (UNTUK CARD DI DASHBOARD)
        // =========================
        $profileSummary = [
            'name'       => $profile->nama_lengkap ?? $user->name,
            'nik'        => $profile->nik ?? $user->nik,
            'jabatan'    => $profile->jabatan_terakhir,
            'unit_kerja' => $profile->unit_kerja,
            'photo_url'  => $profile->photo_path
                ? Storage::disk('public')->url($profile->photo_path)
                : null,
        ];

        return response()->json([
            'data' => [
                'nik'             => $user->nik,
                'tahun'           => $tahun, // bisa null kalau tidak difilter
                'profile'         => $profileSummary,
                'hard_competency' => $hardSummary,
                'soft_competency' => $softSummary,
            ],
        ]);
    }

    /**
     * Helper untuk hitung:
     * - total competency
     * - jumlah status (tercapai / tidak tercapai)
     * - rata-rata nilai
     *
     * Dipakai untuk Hard & Soft Competency.
     */
    protected function buildSummary(Builder $baseQuery): array
    {
        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'status_counts' => [
                    'tercapai'       => 0,
                    'tidak_tercapai' => 0,
                ],
                'avg_nilai' => null,
            ];
        }

        // group by status
        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $avg = (clone $baseQuery)->avg('nilai');

        return [
            'total' => $total,
            'status_counts' => [
                'tercapai'       => (int) ($statusCounts['tercapai'] ?? 0),
                // enum di DB: "tidak tercapai" (pakai spasi)
                'tidak_tercapai' => (int) ($statusCounts['tidak tercapai'] ?? 0),
            ],
            'avg_nilai' => $avg ? round($avg, 2) : null,
        ];
    }
}
