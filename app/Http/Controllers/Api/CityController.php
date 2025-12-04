<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = City::query();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        return response()->json([
            'data' => $query->orderBy('name')->limit(30)->get(['id', 'name']),
        ]);
    }
}