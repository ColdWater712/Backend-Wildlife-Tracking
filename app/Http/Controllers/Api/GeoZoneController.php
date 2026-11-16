<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGeoZoneRequest;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    public function index()
    {
        $zones = GeoZone::with('creator')->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ], 200);
    }

    public function store(StoreGeoZoneRequest $request)
    {
        $validated = $request->validated();

        $validated['created_by'] = $request->user()->user_id;

        $zone = GeoZone::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Zona berhasil dibuat',
            'data' => $zone,
        ], 201);
    }
}