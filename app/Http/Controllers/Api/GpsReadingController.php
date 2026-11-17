<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGpsReadingRequest;
use App\Http\Traits\ApiResponse;
use App\Models\GpsReading;
use Illuminate\Http\Request;

class GpsReadingController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GpsReading::with(['device.animal.species']);

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('animal_id')) {
            $query->whereHas('device', fn($q) => $q->where('animal_id', $request->animal_id));
        }

        if ($request->filled('start_date')) {
            $query->where('recorded_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('recorded_at', '<=', $request->end_date);
        }

        if ($request->filled('min_lat') && $request->filled('max_lat') && $request->filled('min_lon') && $request->filled('max_lon')) {
            $query->whereBetween('latitude', [$request->min_lat, $request->max_lat])
                  ->whereBetween('longitude', [$request->min_lon, $request->max_lon]);
        }

        $query->orderBy('recorded_at', 'desc');

        return $this->paginated($query->paginate($request->input('per_page', 100)));
    }

    public function show($id)
    {
        $reading = GpsReading::with(['device.animal.species', 'weatherData'])->findOrFail($id);
        return $this->success($reading);
    }

    public function store(StoreGpsReadingRequest $request)
    {
        $validated = $request->validated();

        $existing = GpsReading::where('device_id', $validated['device_id'])
            ->where('recorded_at', $validated['recorded_at'])
            ->first();

        if ($existing) {
            return $this->success($existing, 'Data GPS sudah tersimpan (idempotent)', 200);
        }

        $payload = json_encode([
            'device_id' => $validated['device_id'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'recorded_at' => $validated['recorded_at'],
        ]);
        $validated['checksum'] = hash('sha256', $payload);

        $reading = GpsReading::create($validated);

        return $this->success($reading, 'Data GPS tersimpan dengan checksum SHA-256.', 201);
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'readings' => 'required|array|min:1',
            'readings.*.device_id' => 'required|integer|exists:tracking_devices,device_id',
            'readings.*.latitude' => 'required|numeric|between:-90,90',
            'readings.*.longitude' => 'required|numeric|between:-180,180',
            'readings.*.recorded_at' => 'required|date',
        ]);

        $created = [];
        foreach ($request->readings as $r) {
            $payload = json_encode([
                'device_id' => $r['device_id'],
                'latitude' => $r['latitude'],
                'longitude' => $r['longitude'],
                'recorded_at' => $r['recorded_at'],
            ]);
            $r['checksum'] = hash('sha256', $payload);
            $created[] = GpsReading::updateOrCreate(
                ['device_id' => $r['device_id'], 'recorded_at' => $r['recorded_at']],
                $r
            );
        }

        return $this->success($created, count($created) . ' pembacaan GPS diproses', 201);
    }

    public function byDevice($deviceId)
    {
        $readings = GpsReading::where('device_id', $deviceId)
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();
        return $this->success($readings);
    }

    public function byAnimal($animalId)
    {
        $readings = GpsReading::whereHas('device', fn($q) => $q->where('animal_id', $animalId))
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();
        return $this->success($readings);
    }

    public function update(Request $request, $id)
    {
        $reading = GpsReading::findOrFail($id);
        $request->validate([
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'altitude_meters' => 'sometimes|nullable|numeric|min:0',
            'accuracy_meters' => 'sometimes|nullable|numeric|min:0',
        ]);
        $reading->update($request->validated());
        return $this->success($reading->fresh(), 'Pembacaan GPS diperbarui');
    }

    public function destroy($id)
    {
        GpsReading::findOrFail($id)->delete();
        return $this->success(null, 'Pembacaan GPS dihapus');
    }
}