<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGpsReadingRequest;
use App\Models\GPSReading;
use Illuminate\Http\Request;

class GPSReadingController extends Controller
{
    // ... (method index dan byAnimal yang sudah ada biarkan saja) ...

    public function store(StoreGpsReadingRequest $request)
    {
        try {
            // Create data GPS
            // PENTING: Insert ini akan memicu Trigger 'trg_gps_after_insert' 
            // yang otomatis mengupdate last_recorded_at di tabel tracking_devices!
            $reading = GPSReading::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Data GPS berhasil disimpan.',
                'data' => $reading,
                'errors' => null
            ], 201);

        } catch (\Exception $e) {
            // Handle error jika ada duplikasi (device_id + recorded_at harus unik)
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data GPS.',
                'data' => null,
                'errors' => ['database' => [$e->getMessage()]]
            ], 500);
        }
    }
}