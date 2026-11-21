<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGeoZoneRequest;
use App\Http\Requests\UpdateGeoZoneRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GeoZone::with('creator');

        if ($request->filled('search')) $query->where('zone_name', 'like', '%' . $request->search . '%');
        if ($request->filled('zone_type') && $request->zone_type !== 'all') $query->where('zone_type', $request->zone_type);

        $query->orderBy($request->input('orderBy', 'zone_id'), $request->input('order', 'asc'));

        // ✅ FE geo-zone.service pakai unwrapData → array
        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(GeoZone::with('creator')->findOrFail($id));
    }

    public function store(StoreGeoZoneRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->user_id;
        $zone = GeoZone::create($validated);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'CREATE_GEOZONE',
            'details' => json_encode(['zone_id' => $zone->zone_id]), 'timestamp' => now(),
        ]);

        return $this->success($zone->load('creator'), 'Zona berhasil dibuat', 201);
    }

    public function update(UpdateGeoZoneRequest $request, $id)
    {
        $zone = GeoZone::findOrFail($id);
        $zone->update($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_GEOZONE',
            'details' => json_encode(['zone_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success($zone->fresh(), 'Zona diperbarui');
    }

    public function destroy($id)
    {
        GeoZone::findOrFail($id)->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'DELETE_GEOZONE',
            'details' => json_encode(['zone_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success(null, 'Zona dihapus');
    }

    public function animals($id)
    {
        $zone = GeoZone::findOrFail($id);
        $animals = Animal::whereHas('geoZoneVisits', fn($q) => $q->where('zone_id', $id))
            ->with('species')->get();
        return $this->list($animals);
    }

    public function visits($id)
    {
        return $this->list(
            AnimalGeoZone::with(['animal.species', 'entryReading'])->where('zone_id', $id)
                ->orderBy('entered_at', 'desc')->get()
        );
    }
}