<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AnimalGeoZone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AnimalGeoZoneController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = AnimalGeoZone::with(['animal.species', 'zone']);

        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->animal_id);
        }
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }
        if ($request->filled('start_date')) {
            $query->where('entered_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('entered_at', '<=', $request->end_date);
        }

        $query->orderBy('entered_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            AnimalGeoZone::with(['animal.species', 'zone'])->findOrFail($id),
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required|exists:animals,animal_id',
            'zone_id' => 'required|exists:geo_zones,zone_id',
            'entry_reading_id' => 'nullable|exists:gps_readings,reading_id',
            'entered_at' => 'required|date',
        ]);

        $visit = AnimalGeoZone::create($request->only([
            'animal_id',
            'zone_id',
            'entry_reading_id',
            'entered_at',
        ]));

        return $this->success(
            $visit->load(['animal.species', 'zone']),
            'Visit berhasil dibuat',
            201,
        );
    }

    public function update(Request $request, $id)
    {
        $visit = AnimalGeoZone::findOrFail($id);
        $request->validate(['exited_at' => 'required|date']);

        $exitedAt = Carbon::parse($request->exited_at);

        if ($exitedAt->lessThanOrEqualTo($visit->entered_at)) {
            throw ValidationException::withMessages([
                'exited_at' => ['Waktu keluar harus setelah waktu masuk.'],
            ]);
        }

        $visit->update(['exited_at' => $exitedAt]);

        return $this->success(
            $visit->fresh(['animal.species', 'zone']),
            'Visit diperbarui',
        );
    }

    public function exit($id)
    {
        $visit = AnimalGeoZone::findOrFail($id);
        $visit->update(['exited_at' => now()]);

        return $this->success($visit->fresh(), 'Animal keluar dari zone');
    }

    public function destroy($id)
    {
        AnimalGeoZone::findOrFail($id)->delete();

        return $this->success(null, 'Visit dihapus');
    }
}
