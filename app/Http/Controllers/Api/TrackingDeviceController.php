<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = TrackingDevice::with(['animal.species']);

        // FIX BUG-DEVICE-001: Logika filter is_active yang robust
        if ($request->has('is_active') && $request->input('is_active') !== 'all' && $request->input('is_active') !== '') {
            $val = $request->input('is_active');
            
            // Deteksi berbagai format input dari frontend
            $isActive = false;
            if (is_bool($val)) {
                $isActive = $val;
            } elseif (is_numeric($val)) {
                $isActive = (bool) $val;
            } elseif (is_string($val)) {
                $lowerVal = strtolower(trim($val));
                // Terima '1', 'true', 'aktif', 'active', 'ya'
                $isActive = in_array($lowerVal, ['1', 'true', 'aktif', 'active', 'ya', 'yes']);
            }

            $query->where('is_active', $isActive);
        }

        // Filter Kondisi (Normal, Rusak, Hilang)
        if ($request->has('device_condition') && $request->input('device_condition') !== 'all') {
            $query->where('device_condition', $request->input('device_condition'));
        }

        // Pencarian (Model atau Nama Satwa)
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('model', 'like', '%' . $search . '%')
                  ->orWhereHas('animal', function ($aq) use ($search) {
                      $aq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $devices = $query->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'nullable|integer|exists:animals,animal_id|unique:tracking_devices,animal_id',
            'model' => 'required|string|max:100',
            'installation_date' => 'nullable|date|before_or_equal:today',
            'battery_life_months' => 'nullable|integer|min:1|max:60',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer|min:0|max:5',
            'device_condition' => 'required|in:normal,damaged,lost',
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