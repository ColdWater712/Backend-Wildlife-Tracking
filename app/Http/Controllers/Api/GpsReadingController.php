<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\GpsReading;
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GpsReading::with(['animal', 'device', 'weather']);

        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('device_id')) $query->where('device_id', $request->device_id);
        if ($request->filled('start_date')) $query->where('recorded_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('recorded_at', '<=', $request->end_date);
        if ($request->filled('min_altitude')) $query->where('altitude', '>=', $request->min_altitude);
        if ($request->filled('max_altitude')) $query->where('altitude', '<=', $request->max_altitude);

        $query->orderBy('recorded_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            GpsReading::with(['animal', 'device', 'weather'])->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id'      => 'required|exists:animals,animal_id',
            'device_id'      => 'required|exists:tracking_devices,device_id',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'altitude'       => 'nullable|numeric',
            'recorded_at'    => 'required|date',
            'battery_level'  => 'nullable|numeric|between:0,100',
            'signal_strength' => 'nullable|numeric',
        ]);

        $reading = GpsReading::create($request->only([
            'animal_id', 'device_id', 'latitude', 'longitude', 'altitude',
            'recorded_at', 'battery_level', 'signal_strength'
        ]));

        return $this->success($reading->load(['animal', 'device']), 'GPS reading berhasil dibuat', 201);
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'readings' => 'required|array',
            'readings.*.animal_id' => 'required|exists:animals,animal_id',
            'readings.*.device_id' => 'required|exists:tracking_devices,device_id',
            'readings.*.latitude' => 'required|numeric',
            'readings.*.longitude' => 'required|numeric',
            'readings.*.recorded_at' => 'required|date',
        ]);

        $created = [];
        foreach ($request->readings as $data) {
            $created[] = GpsReading::create($data);
        }

        return $this->success($created, count($created) . ' GPS readings berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $reading = GpsReading::findOrFail($id);
        $request->validate([
            'latitude'  => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'altitude'  => 'nullable|numeric',
        ]);

        $reading->update($request->only(['latitude', 'longitude', 'altitude']));
        return $this->success($reading->fresh(['animal', 'device']), 'GPS reading diperbarui');
    }

    public function destroy($id)
    {
        GpsReading::findOrFail($id)->delete();
        return $this->success(null, 'GPS reading dihapus');
    }

    public function byAnimal($animalId)
    {
        $readings = GpsReading::where('animal_id', $animalId)
            ->with(['device'])
            ->orderBy('recorded_at', 'desc')
            ->limit(1000)
            ->get();
        return $this->list($readings);
    }

    public function byDevice($deviceId)
    {
        $readings = GpsReading::where('device_id', $deviceId)
            ->with(['animal'])
            ->orderBy('recorded_at', 'desc')
            ->limit(1000)
            ->get();
        return $this->list($readings);
    }
}