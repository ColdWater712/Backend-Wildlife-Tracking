<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    public function index()
    {
        $devices = TrackingDevice::with(['animal.species'])->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'nullable|integer|exists:animals,animal_id|unique:tracking_devices,animal_id',
            'model' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
            'installation_date' => 'nullable|date|before_or_equal:today',
            'battery_life_months' => 'nullable|integer|min:1|max:60',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer|min:0|max:5',
            'device_condition' => 'required|in:normal,damaged,lost',
        ], [
            'model.regex' => 'Model hanya boleh berisi huruf, angka, spasi, dan strip.',
            'battery_level.max' => 'Baterai maksimal 100%.',
            'signal_strength.max' => 'Sinyal maksimal 5.',
        ]);

        $validated = $request->validated();
        $validated['is_active'] = true;

        $device = TrackingDevice::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Perangkat pelacak berhasil ditambahkan',
            'data' => $device,
        ], 201);
    }

    public function show($id)
    {
        $device = TrackingDevice::with(['animal.species', 'gpsReadings'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $device,
        ], 200);
    }
}