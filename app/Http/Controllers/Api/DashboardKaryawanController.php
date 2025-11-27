<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HardCompetency;
use App\Models\SoftCompetency; // ⬅️ TAMBAHKAN INI
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
     * Response contoh:
     * {
     *   "data": {
     *     "nik": "5025211174",
     *     "tahun": 2025,
     *     "hard_competency": {...},
     *     "soft_competency": {...}
     *   }
     * }
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->nik) {
            return response()->json([
                'message' => 'User tidak memiliki NIK, tidak bisa mengambil summary.'
            ], 422);
        }

        // tahun opsional, kalau null -> semua tahun
        $tahun = $request->integer('tahun');

        // HARD COMPETENCY
        $hardQuery   = HardCompetency::forNik($user->nik)->forYear($tahun);
        $hardSummary = $this->buildSummary($hardQuery);

        // SOFT COMPETENCY (❗baru)
        $softQuery   = SoftCompetency::forNik($user->nik)->forYear($tahun);
        $softSummary = $this->buildSummary($softQuery);

        return response()->json([
            'data' => [
                'nik'   => $user->nik,
                'tahun' => $tahun, // bisa null kalau tidak difilter
                'hard_competency'  => $hardSummary,
                'soft_competency'  => $softSummary, // ⬅️ baru
            ],
        ]);
    }

    /**
     * Helper untuk hitung:
     * - total competency
     * - jumlah status (tercapai / tidak tercapai)
     * - rata-rata nilai
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
                // di DB enum: "tidak tercapai" (pakai spasi)
                'tidak_tercapai' => (int) ($statusCounts['tidak tercapai'] ?? 0),
            ],
            'avg_nilai' => $avg ? round($avg, 2) : null,
        ];
    }
}
