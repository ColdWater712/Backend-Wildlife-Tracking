<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    public function index()
    {
        // Ambil semua perangkat beserta data satwanya
        $devices = TrackingDevice::with('animal.species')->get();
        
        return response()->json([
            'success' => true,
            'data' => $devices
        ], 200);
    }

    public function show($id)
    {
        // Cari berdasarkan device_id
        $device = TrackingDevice::with('animal.species', 'gpsReadings')->find($id);
        
        if (!$device) {
            return response()->json(['message' => 'Perangkat tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $device
        ], 200);
    }
}