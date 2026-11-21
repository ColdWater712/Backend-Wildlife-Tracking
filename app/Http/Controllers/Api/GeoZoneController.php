<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AnimalGeoZone;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GeoZone::withCount('animalVisits');

        if ($request->filled('zone_type')) {
            $query->where('zone_type', $request->zone_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('zone_name', 'like', "%{$search}%");
        }

        $query->orderBy('zone_name', 'asc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(GeoZone::withCount('animalVisits')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'zone_name' => 'required|string|max:150',
            'zone_type' => 'required|string|max:50',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lon' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0',
            'polygon_geojson' => 'nullable|json',
        ]);

        $data['created_by'] = $request->user()->user_id;

        $zone = GeoZone::create($data);

        return $this->success($zone, 'Geo zone berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $zone = GeoZone::findOrFail($id);
        $data = $request->validate([
            'zone_name' => 'sometimes|string|max:150',
            'zone_type' => 'sometimes|string|max:50',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lon' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0',
            'polygon_geojson' => 'nullable|json',
        ]);

        $zone->update($data);

        return $this->success($zone->fresh(), 'Geo zone diperbarui');
    }

    public function destroy($id)
    {
        GeoZone::findOrFail($id)->delete();

        return $this->success(null, 'Geo zone dihapus');
    }

    public function animals($id)
    {
        $visits = AnimalGeoZone::where('zone_id', $id)
            ->with('animal.species')
            ->get();

        $uniqueAnimals = $visits->pluck('animal')
            ->filter()
            ->unique('animal_id')
            ->values();

        return $this->list($uniqueAnimals);
    }

    public function visits($id)
    {
        $visits = AnimalGeoZone::where('zone_id', $id)
            ->with('animal.species')
            ->orderBy('entered_at', 'desc')
            ->get();

        return $this->list($visits);
    }
}
