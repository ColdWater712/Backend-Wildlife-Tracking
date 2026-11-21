<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\GeoZone;
use App\Models\AnimalGeoZoneVisit;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GeoZone::withCount('visits');

        if ($request->filled('zone_type')) $query->where('zone_type', $request->zone_type);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name', 'asc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(GeoZone::withCount('visits')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'zone_type' => 'required|string',
            'polygon'   => 'required|json',
            'is_active' => 'boolean',
        ]);

        $zone = GeoZone::create([
            'name'      => $request->name,
            'zone_type' => $request->zone_type,
            'polygon'   => $request->polygon,
            'is_active' => $request->input('is_active', true),
        ]);

        return $this->success($zone, 'Geo zone berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $zone = GeoZone::findOrFail($id);
        $request->validate([
            'name'      => 'sometimes|string|max:100',
            'zone_type' => 'sometimes|string',
            'polygon'   => 'sometimes|json',
            'is_active' => 'boolean',
        ]);

        $zone->update($request->only(['name', 'zone_type', 'polygon', 'is_active']));
        return $this->success($zone->fresh(), 'Geo zone diperbarui');
    }

    public function destroy($id)
    {
        GeoZone::findOrFail($id)->delete();
        return $this->success(null, 'Geo zone dihapus');
    }

    public function animals($id)
    {
        $zone = GeoZone::findOrFail($id);
        $visits = AnimalGeoZoneVisit::where('zone_id', $id)
            ->with(['animal.species'])
            ->get();
        
        $uniqueAnimals = $visits->pluck('animal')->unique('animal_id')->values();
        return $this->list($uniqueAnimals);
    }

    public function visits($id)
    {
        $visits = AnimalGeoZoneVisit::where('zone_id', $id)
            ->with(['animal.species'])
            ->orderBy('entered_at', 'desc')
            ->get();
        return $this->list($visits);
    }
}