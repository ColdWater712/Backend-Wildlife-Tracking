<?php

namespace App\Http\Controllers\Api;

use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GeoZone;
use App\Models\TrackingDevice;
use App\Services\GeoService;
use Illuminate\Http\Request;

class GeoZoneController extends ApiController
{
    public const ZONE_TYPES = ['permukiman', 'perkebunan', 'jalan_raya', 'koridor_ekologis', 'hutan_lindung', 'zona_inti'];

    public function __construct(private GeoService $geo) {}

    public function index(Request $request)
    {
        $query = GeoZone::query()->withCount(['visits as active_visits_count' => fn ($q) => $q->whereNull('exited_at')]);

        if ($request->filled('zone_type')) {
            $query->where('zone_type', $request->zone_type);
        }
        if ($request->filled('search')) {
            $query->where('zone_name', 'like', '%' . $request->search . '%');
        }

        $this->applySort($query, $request, ['zone_id', 'zone_name', 'zone_type', 'created_at'], 'zone_id', 'asc');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(GeoZone::with(['creator', 'devices'])
            ->withCount(['visits as active_visits_count' => fn ($q) => $q->whereNull('exited_at')])
            ->findOrFail($id));
    }

    public function store(Request $request)
    {
        $zone = GeoZone::create($this->validated($request) + ['created_by' => $request->user()->user_id]);

        return $this->success($zone->fresh(), 'Zona berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $zone = GeoZone::findOrFail($id);
        $zone->update($this->validated($request, $zone));

        return $this->success($zone->fresh(), 'Zona diperbarui');
    }

    public function destroy(int $id)
    {
        $zone = GeoZone::withCount('visits')->findOrFail($id);

        // Soft delete: riwayat kunjungan tetap utuh untuk keperluan analisis.
        $zone->delete();

        return $this->success(null, 'Zona dihapus' . ($zone->visits_count ? " (riwayat {$zone->visits_count} kunjungan tetap disimpan)" : ''));
    }

    /** Satwa yang sedang (default) atau pernah (?history=1) berada di zona. */
    public function animals(Request $request, int $id)
    {
        GeoZone::findOrFail($id);

        $animalIds = AnimalGeoZone::query()
            ->where('zone_id', $id)
            ->when(! $request->boolean('history'), fn ($q) => $q->whereNull('exited_at'))
            ->pluck('animal_id')->unique();

        return $this->list(Animal::query()->with(['species', 'trackingDevice'])->whereIn('animal_id', $animalIds));
    }

    public function visits(int $id)
    {
        GeoZone::findOrFail($id);

        return $this->list(AnimalGeoZone::query()->with(['animal.species', 'entryReading'])
            ->where('zone_id', $id)->orderByDesc('entered_at'));
    }

    public function devices(int $id)
    {
        GeoZone::findOrFail($id);

        return $this->list(TrackingDevice::query()->with('animal')->where('zone_id', $id));
    }

    /** Cek zona untuk sebuah koordinat (?lat=&lon=). */
    public function locate(Request $request)
    {
        $data = $request->validate(['lat' => 'required|numeric|between:-90,90', 'lon' => 'required|numeric|between:-180,180']);

        return $this->list($this->geo->zonesContaining((float) $data['lat'], (float) $data['lon']));
    }

    private function validated(Request $request, ?GeoZone $zone = null): array
    {
        // Frontend mengirim polygon_geojson sebagai string JSON.
        $polygon = $request->input('polygon_geojson');
        if (is_string($polygon)) {
            $request->merge(['polygon_geojson' => trim($polygon) === '' ? null : json_decode($polygon, true) ?? '__invalid__']);
        }

        $required = $zone ? 'sometimes' : 'required';

        $data = $request->validate([
            'zone_name' => "{$required}|string|max:150",
            'zone_type' => "{$required}|string|max:50",
            'center_lat' => 'nullable|numeric|between:-90,90|required_with:center_lon,radius_km',
            'center_lon' => 'nullable|numeric|between:-180,180|required_with:center_lat,radius_km',
            'radius_km' => 'nullable|numeric|min:0.001|max:1000',
            'polygon_geojson' => ['nullable', function ($attr, $value, $fail) {
                if (! $this->geo->isValidPolygon($value)) {
                    $fail('polygon_geojson harus GeoJSON Polygon/MultiPolygon yang valid (koordinat [lon, lat]).');
                }
            }],
        ]);

        $merged = array_merge($zone?->only(['center_lat', 'center_lon', 'radius_km', 'polygon_geojson']) ?? [], $data);
        $hasCircle = ($merged['center_lat'] ?? null) !== null && ($merged['radius_km'] ?? null) !== null;
        if (! $hasCircle && empty($merged['polygon_geojson'])) {
            abort(response()->json([
                'success' => false,
                'message' => 'Zona wajib memiliki titik pusat + radius atau polygon GeoJSON.',
                'data' => null,
                'errors' => ['radius_km' => ['Isi center_lat, center_lon, radius_km atau polygon_geojson.']],
            ], 422));
        }

        return $data;
    }
}
