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
            'data' => $zones
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone_name' => 'required|string|max:150',
            'zone_type' => 'required|string|max:50',
            'center_lat' => 'required|numeric',
            'center_lon' => 'required|numeric',
            'radius_km' => 'nullable|numeric',
        ]);

        // Set created_by dari user yang sedang login
        $data = $request->all();
        $data['created_by'] = $request->user()->user_id;

        $zone = GeoZone::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Zona berhasil dibuat',
            'data' => $zone
        ], 201);
    }
}