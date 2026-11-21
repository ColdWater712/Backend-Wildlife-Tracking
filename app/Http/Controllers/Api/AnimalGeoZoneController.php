<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalGeoZoneRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AnimalGeoZone;
use Illuminate\Http\Request;

class AnimalGeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = AnimalGeoZone::with(['animal.species', 'zone', 'entryReading']);

        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('zone_id'))   $query->where('zone_id', $request->zone_id);
        if ($request->has('is_active')) {
            $request->boolean('is_active') ? $query->whereNull('exited_at') : $query->whereNotNull('exited_at');
        }

        $query->orderBy('entered_at', 'desc');

        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(AnimalGeoZone::with(['animal.species', 'zone', 'entryReading'])->findOrFail($id));
    }

    public function store(StoreAnimalGeoZoneRequest $request)
    {
        $active = AnimalGeoZone::where('animal_id', $request->animal_id)
            ->where('zone_id', $request->zone_id)->whereNull('exited_at')->first();
        if ($active) return $this->error('Satwa sudah berada di zona ini', 422);

        $visit = AnimalGeoZone::create($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'ZONE_ENTRY',
            'details' => json_encode(['visit_id' => $visit->visit_id]), 'timestamp' => now(),
        ]);

        return $this->success($visit->load(['animal.species', 'zone']), 'Kunjungan zona dicatat', 201);
    }

    public function exit(Request $request, $id)
    {
        $visit = AnimalGeoZone::findOrFail($id);
        if ($visit->exited_at) return $this->error('Kunjungan sudah selesai', 422);
        $request->validate(['exited_at' => 'required|date|after:' . $visit->entered_at]);
        $visit->update(['exited_at' => $request->exited_at]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'ZONE_EXIT',
            'details' => json_encode(['visit_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success($visit->fresh(), 'Kunjungan selesai');
    }

    public function update(Request $request, $id)
    {
        $visit = AnimalGeoZone::findOrFail($id);
        $request->validate([
            'entry_reading_id' => 'sometimes|nullable|integer|exists:gps_readings,reading_id',
            'entered_at' => 'sometimes|date',
            'exited_at' => 'sometimes|nullable|date|after:entered_at',
        ]);
        $visit->update($request->validated());
        return $this->success($visit->fresh(), 'Kunjungan diperbarui');
    }

    public function destroy($id)
    {
        AnimalGeoZone::findOrFail($id)->delete();
        return $this->success(null, 'Kunjungan dihapus');
    }
}