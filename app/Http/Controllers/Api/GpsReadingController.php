<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsReading;
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    public function index()
    {
        // Ambil 100 data terakhir, urutkan dari yang terbaru
        $readings = GpsReading::with('device.animal')
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();
            
        return response()->json([
            'success' => true, 
            'data' => $readings
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:tracking_devices,device_id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'recorded_at' => 'required|date_format:Y-m-d H:i:s',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|numeric',
        ]);

        // Insert data GPS
        // Trigger database akan otomatis berjalan di belakang layar
        $reading = GpsReading::create($request->all());

        return response()->json([
            'success' => true, 
            'message' => 'Data GPS tersimpan & Trigger database dijalankan.', 
            'data' => $reading
        ], 201);
    }
}