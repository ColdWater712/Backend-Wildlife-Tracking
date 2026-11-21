<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AnimalGeoZoneVisit;
use Illuminate\Http\Request;

class AnimalGeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = AnimalGeoZoneVisit::with(['animal.species', 'zone']);

        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('zone_id')) $query->where('zone_id', $request->zone_id);
        if ($request->filled('start_date')) $query->where('entered_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('entered_at', '<=', $request->end_date);

        $query->orderBy('entered_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            AnimalGeoZoneVisit::with(['animal.species', 'zone'])->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id'  => 'required|exists:animals,animal_id',
            'zone_id'    => 'required|exists:geo_zones,zone_id',
            'entered_at' => 'required|date',
        ]);

        $visit = AnimalGeoZoneVisit::create([
            'animal_id'  => $request->animal_id,
            'zone_id'    => $request->zone_id,
            'entered_at' => $request->entered_at,
        ]);

        return $this->success($visit->load(['animal.species', 'zone']), 'Visit berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $visit = AnimalGeoZoneVisit::findOrFail($id);
        $request->validate([
            'exited_at' => 'required|date|after:visit.entered_at',
        ]);

        $visit->update(['exited_at' => $request->exited_at]);
        return $this->success($visit->fresh(['animal.species', 'zone']), 'Visit diperbarui');
    }

    public function exit($id)
    {
        $visit = AnimalGeoZoneVisit::findOrFail($id);
        $visit->update(['exited_at' => now()]);
        return $this->success($visit->fresh(), 'Animal keluar dari zone');
    }

    public function destroy($id)
    {
        AnimalGeoZoneVisit::findOrFail($id)->delete();
        return $this->success(null, 'Visit dihapus');
    }
}