<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    public function index()
    {
        $zones = GeoZone::with('creator')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data zona geografis berhasil diambil.',
            'data' => $zones,
            'errors' => null
        ], 200);
    }

    public function show($id)
    {
        $zone = GeoZone::with('creator')->find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Zona tidak ditemukan.',
                'data' => null,
                'errors' => ['zone' => ['ID zona tidak valid.']]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail zona berhasil diambil.',
            'data' => $zone,
            'errors' => null
        ], 200);
    }
}