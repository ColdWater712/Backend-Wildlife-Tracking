<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    public function index()
    {
        $devices = TrackingDevice::with('animal')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data perangkat pelacak berhasil diambil.',
            'data' => $devices,
            'errors' => null
        ], 200);
    }

    public function show($id)
    {
        // Gunakan find() karena primary key adalah device_id, bukan id
        $device = TrackingDevice::with(['animal.species'])->find($id);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat tidak ditemukan.',
                'data' => null,
                'errors' => ['device' => ['ID perangkat tidak valid.']]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail perangkat berhasil diambil.',
            'data' => $device,
            'errors' => null
        ], 200);
    }
}