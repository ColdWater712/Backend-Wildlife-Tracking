<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGpsReadingRequest;
use App\Models\GpsReading;
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    public function index(Request $request)
    {
        $query = GpsReading::with(['device.animal.species']);

        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        $readings = $query->orderBy('recorded_at', 'desc')->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $readings,
        ], 200);
    }

    public function store(StoreGpsReadingRequest $request)
    {
        $validated = $request->validated();

        $payload = json_encode([
            'device_id' => $validated['device_id'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'recorded_at' => $validated['recorded_at'],
        ]);
        $validated['checksum'] = hash('sha256', $payload);

        $reading = GpsReading::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data GPS tersimpan & Trigger database dijalankan.',
            'data' => $reading,
        ], 201);
    }
}