<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Dashboard\CompetencySummaryService;

class DashboardController extends Controller
{
    public function competencySummary(Request $request)
    {
        // unit_kerja:
        // - tidak dikirim => null (artinya semua unit)
        // - "All" => null (UI only, jangan jadi filter DB)
        // - selain itu => nama unit kerja
        $unitKerja = $request->query('unit_kerja');
        if ($unitKerja === 'All' || $unitKerja === '' || $unitKerja === null) {
            $unitKerja = null;
        }

        // years[]: optional, boleh dikirim kapan saja
        $years = $request->query('years', []);
        if (!is_array($years)) {
            $years = [];
        }

        return response()->json(
            CompetencySummaryService::execute($unitKerja, $years)
        );
    }
}
