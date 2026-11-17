<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGeoZoneRequest;
use App\Http\Requests\UpdateGeoZoneRequest;
use App\Http\Traits\ApiResponse;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class GeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GeoZone::with('creator');

        if ($request->filled('search')) {
            $query->where('zone_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('zone_type') && $request->zone_type !== 'all') {
            $query->where('zone_type', $request->zone_type);
        }

        $sort = $request->input('orderBy', 'zone_id');
        $dir = $request->input('order', 'asc');
        $query->orderBy($sort, $dir);

        return $this->paginated($query->paginate($request->input('per_page', 25)));
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
        return $this->success($zone->load('creator'), 'Zona berhasil dibuat', 201);
    }

    public function update(UpdateGeoZoneRequest $request, $id)
    {
        $zone = GeoZone::findOrFail($id);
        $zone->update($request->validated());
        return $this->success($zone->fresh(), 'Zona diperbarui');
    }

    public function destroy($id)
    {
        GeoZone::findOrFail($id)->delete();
        return $this->success(null, 'Zona dihapus');
    }

    public function animals($id)
    {
        $zone = GeoZone::findOrFail($id);
        $animals = $zone->animalVisits()->with('animal.species')->get()->pluck('animal');
        return $this->success($animals);
    }

    public function visits($id)
    {
        $visits = GeoZone::findOrFail($id)->animalVisits()->with(['animal.species', 'entryReading'])->get();
        return $this->success($visits);
    }
}