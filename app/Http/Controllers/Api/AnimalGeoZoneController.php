<?php

namespace App\Http\Controllers\Api;

use App\Models\AnimalGeoZone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Kunjungan satwa ke zona: masuk (POST) dan keluar (PATCH /exit). */
class AnimalGeoZoneController extends ApiController
{
    public function index(Request $request)
    {
        $query = AnimalGeoZone::query()->with(['animal.species', 'zone', 'entryReading']);

        foreach (['animal_id', 'zone_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }
        if ($request->filled('active')) {
            $request->boolean('active') ? $query->whereNull('exited_at') : $query->whereNotNull('exited_at');
        }
        if ($request->filled('start_date')) {
            $query->where('entered_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('entered_at', '<=', $request->date('end_date')->endOfDay());
        }

        return $this->list($query->orderByDesc('entered_at'));
    }

    public function show(int $id)
    {
        return $this->success(AnimalGeoZone::with(['animal.species', 'zone', 'entryReading'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'animal_id' => ['required', 'integer', Rule::exists('animals', 'animal_id')->whereNull('deleted_at')],
            'zone_id' => ['required', 'integer', Rule::exists('geo_zones', 'zone_id')->whereNull('deleted_at')],
            'entry_reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'entered_at' => 'required|date|before_or_equal:now',
        ]);

        $active = AnimalGeoZone::query()
            ->where('animal_id', $data['animal_id'])
            ->where('zone_id', $data['zone_id'])
            ->whereNull('exited_at')
            ->exists();

        if ($active) {
            return $this->error('Satwa masih tercatat berada di zona ini. Catat keluar terlebih dahulu.', 409);
        }

        $visit = AnimalGeoZone::create($data);

        return $this->success($visit->load(['animal', 'zone']), 'Kunjungan zona dicatat', 201);
    }

    public function exit(Request $request, int $id)
    {
        $visit = AnimalGeoZone::findOrFail($id);

        if ($visit->exited_at !== null) {
            return $this->error('Kunjungan ini sudah ditutup.', 409);
        }

        $data = $request->validate([
            'exited_at' => 'nullable|date|before_or_equal:now|after_or_equal:' . $visit->entered_at->toDateTimeString(),
        ]);

        $visit->update(['exited_at' => $data['exited_at'] ?? now()]);

        return $this->success($visit->fresh(['animal', 'zone']), 'Satwa tercatat keluar zona');
    }
}
