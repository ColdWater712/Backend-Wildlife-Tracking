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
        $query = GpsReading::with(['device.animal', 'weatherData']);

        if ($request->filled('animal_id')) {
            $query->whereHas('device', fn($q) => $q->where('animal_id', $request->animal_id));
        }
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }
        if ($request->filled('start_date')) {
            $query->where('recorded_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('recorded_at', '<=', $request->end_date);
        }
        if ($request->filled('min_altitude')) {
            $query->where('altitude_meters', '>=', $request->min_altitude);
        }
        if ($request->filled('max_altitude')) {
            $query->where('altitude_meters', '<=', $request->max_altitude);
        }

        $query->orderBy('recorded_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            GpsReading::with(['device.animal', 'weatherData'])->findOrFail($id),
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|exists:tracking_devices,device_id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'recorded_at' => 'required|date',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|numeric|min:0',
        ]);

        $reading = $this->createReading($data);

        return $this->success(
            $reading->load(['device.animal', 'weatherData']),
            'GPS reading berhasil dibuat',
            201,
        );
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'readings' => 'required|array',
            'readings.*.device_id' => 'required|exists:tracking_devices,device_id',
            'readings.*.latitude' => 'required|numeric|between:-90,90',
            'readings.*.longitude' => 'required|numeric|between:-180,180',
            'readings.*.recorded_at' => 'required|date',
            'readings.*.altitude_meters' => 'nullable|numeric',
            'readings.*.accuracy_meters' => 'nullable|numeric|min:0',
        ]);

        $created = [];

        foreach ($validated['readings'] as $data) {
            $created[] = $this->createReading($data);
        }

        return $this->success($created, count($created) . ' GPS readings berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $reading = GpsReading::findOrFail($id);
        $data = $request->validate([
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'recorded_at' => 'sometimes|date',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|numeric|min:0',
        ]);

        $reading->update($data);

        return $this->success(
            $reading->fresh(['device.animal', 'weatherData']),
            'GPS reading diperbarui',
        );
    }

    public function destroy($id)
    {
        GpsReading::findOrFail($id)->delete();

        return $this->success(null, 'GPS reading dihapus');
    }

    public function byAnimal($animalId)
    {
        $readings = GpsReading::whereHas('device', fn($q) => $q->where('animal_id', $animalId))
            ->with(['device.animal'])
            ->orderBy('recorded_at', 'desc')
            ->limit(1000)
            ->get();

        return $this->list($readings);
    }

    public function byDevice($deviceId)
    {
        $readings = GpsReading::where('device_id', $deviceId)
            ->with(['device.animal'])
            ->orderBy('recorded_at', 'desc')
            ->limit(1000)
            ->get();

        return $this->list($readings);
    }

    private function createReading(array $data): GpsReading
    {
        $payload = [
            'device_id' => $data['device_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'recorded_at' => $data['recorded_at'],
            'altitude_meters' => $data['altitude_meters'] ?? null,
            'accuracy_meters' => $data['accuracy_meters'] ?? null,
        ];
        $payload['checksum'] = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return GpsReading::create($payload);
    }
}
