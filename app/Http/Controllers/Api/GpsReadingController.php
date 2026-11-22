<?php

namespace App\Http\Controllers\Api;

use App\Models\GpsReading;
use App\Models\TrackingDevice;
use App\Services\GeofenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Data telemetri bersifat append-only: tidak ada endpoint update/delete. */
class GpsReadingController extends ApiController
{
    public function __construct(private GeofenceService $geofence) {}

    public function index(Request $request)
    {
        $query = GpsReading::query()->with(['device.animal', 'weatherData']);

        if ($request->filled('animal_id')) {
            $query->whereHas('device', fn ($q) => $q->where('animal_id', $request->integer('animal_id')));
        }
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->integer('device_id'));
        }
        if ($request->filled('start_date')) {
            $query->where('recorded_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('recorded_at', '<=', $request->date('end_date')->endOfDay());
        }
        if ($request->filled(['min_lat', 'max_lat', 'min_lon', 'max_lon'])) {
            $query->whereBetween('latitude', [$request->float('min_lat'), $request->float('max_lat')])
                ->whereBetween('longitude', [$request->float('min_lon'), $request->float('max_lon')]);
        }
        if ($request->boolean('latest_only')) {
            $query->whereIn('reading_id', GpsReading::query()->selectRaw('MAX(reading_id)')->groupBy('device_id'));
        }

        $query->orderByDesc('recorded_at');

        // Tanpa pagination: batasi ke data terbaru agar dashboard/peta tidak menarik seluruh tabel telemetri.
        if (! $request->filled('per_page') && ! $request->filled('page')) {
            $query->limit(min(5000, $request->integer('limit', 2000)));
        }

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(GpsReading::with(['device.animal', 'weatherData', 'zoneVisits.zone'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $reading = DB::transaction(fn () => $this->ingest($data));

        if ($reading === null) {
            return $this->error('Pembacaan untuk perangkat & waktu tersebut sudah ada.', 409);
        }

        return $this->success($reading->load(['device.animal', 'zoneVisits.zone']), 'Pembacaan GPS berhasil disimpan', 201);
    }

    /** Ingest batch; duplikat (device_id, recorded_at) dilewati (idempotent). */
    public function bulk(Request $request)
    {
        $rules = ['readings' => 'required|array|min:1|max:1000'];
        foreach ($this->rules() as $field => $rule) {
            $rules["readings.*.{$field}"] = $rule;
        }
        $validated = $request->validate($rules);

        $created = [];
        $skipped = 0;
        DB::transaction(function () use ($validated, &$created, &$skipped) {
            foreach (collect($validated['readings'])->sortBy('recorded_at') as $row) {
                $reading = $this->ingest($row);
                $reading ? $created[] = $reading : $skipped++;
            }
        });

        return $this->success([
            'created' => count($created),
            'skipped_duplicates' => $skipped,
            'readings' => $created,
        ], count($created) . ' pembacaan GPS disimpan', 201);
    }

    private function rules(): array
    {
        return [
            'device_id' => 'required|integer|exists:tracking_devices,device_id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'recorded_at' => 'required|date|before_or_equal:' . now()->addMinutes(5)->toDateTimeString(),
            'altitude_meters' => 'nullable|numeric|between:-500,9000',
            'accuracy_meters' => 'nullable|numeric|between:0,9999',
            'checksum' => 'nullable|string|size:64',
            'battery_level' => 'nullable|integer|between:0,100',
            'signal_strength' => 'nullable|integer|between:0,100',
        ];
    }

    private function ingest(array $data): ?GpsReading
    {
        $recordedAt = Carbon::parse($data['recorded_at']);

        $exists = GpsReading::query()
            ->where('device_id', $data['device_id'])
            ->where('recorded_at', $recordedAt)
            ->exists();
        if ($exists) {
            return null;
        }

        $payload = [
            'device_id' => (int) $data['device_id'],
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'recorded_at' => $recordedAt->toDateTimeString(),
            'altitude_meters' => $data['altitude_meters'] ?? null,
            'accuracy_meters' => $data['accuracy_meters'] ?? null,
        ];
        $payload['checksum'] = $data['checksum'] ?? hash('sha256', json_encode($payload));

        $reading = GpsReading::create($payload);

        $device = TrackingDevice::find($payload['device_id']);
        if (isset($data['battery_level']) || isset($data['signal_strength'])) {
            $device->update(array_filter([
                'battery_level' => $data['battery_level'] ?? null,
                'signal_strength' => $data['signal_strength'] ?? null,
            ], fn ($v) => $v !== null));
        }

        $reading->setRelation('device', $device);
        $this->geofence->handleReading($reading);

        return $reading;
    }
}
