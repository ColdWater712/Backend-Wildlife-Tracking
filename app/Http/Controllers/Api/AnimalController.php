<?php

namespace App\Http\Controllers\Api;

use App\Models\AiPrediction;
use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnimalController extends ApiController
{
    public function index(Request $request)
    {
        $query = Animal::query()->with(['species', 'trackingDevice']);

        if ($request->filled('species_id')) {
            $query->where('species_id', $request->integer('species_id'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('device_id')) {
            $query->whereHas('trackingDevice', fn ($q) => $q->where('device_id', $request->integer('device_id')));
        }
        if ($request->filled('search')) {
            $query->where('name_animal', 'like', '%' . $request->search . '%');
        }

        $this->applySort($query, $request, ['animal_id', 'name_animal', 'birth_date', 'created_at'], 'animal_id');

        return $this->list($query);
    }

    public function show(int $id)
    {
        $animal = Animal::with(['species', 'trackingDevice.latestReading'])->findOrFail($id);
        $animal->setAttribute('latest_ai_observation', $this->latestObservation($id));

        return $this->success($animal);
    }

    public function store(Request $request)
    {
        $animal = Animal::create($this->validated($request));

        return $this->success($animal->load(['species', 'trackingDevice']), 'Satwa berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $animal = Animal::findOrFail($id);
        $animal->update($this->validated($request, $animal));

        return $this->success($animal->fresh(['species', 'trackingDevice']), 'Satwa diperbarui');
    }

    public function updateStatus(Request $request, int $id)
    {
        $animal = Animal::findOrFail($id);
        $animal->update($request->validate(['is_active' => 'required|boolean']));

        return $this->success($animal->fresh(['species', 'trackingDevice']), 'Status satwa diperbarui');
    }

    /** Soft delete; kalung GPS dilepas agar bisa dipasang ke satwa lain. */
    public function destroy(int $id)
    {
        $animal = Animal::findOrFail($id);

        DB::transaction(function () use ($animal) {
            $animal->trackingDevice()?->update(['animal_id' => null]);
            $animal->update(['is_active' => false]);
            $animal->delete();
        });

        return $this->success(null, 'Satwa dihapus');
    }

    public function track(Request $request, int $animalId)
    {
        Animal::findOrFail($animalId);
        $query = GpsReading::query()
            ->with('device.animal')
            ->whereHas('device', fn ($q) => $q->where('animal_id', $animalId));

        if ($request->filled('start_date')) {
            $query->where('recorded_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('recorded_at', '<=', $request->date('end_date')->endOfDay());
        }

        return $this->list($query->orderByDesc('recorded_at')->limit(min(5000, $request->integer('limit', 1000))));
    }

    public function zoneVisits(int $animalId)
    {
        Animal::findOrFail($animalId);

        return $this->list(AnimalGeoZone::query()
            ->with(['zone', 'entryReading'])
            ->where('animal_id', $animalId)
            ->orderByDesc('entered_at'));
    }

    public function incidents(int $animalId)
    {
        Animal::findOrFail($animalId);

        return $this->list(IncidentReport::query()
            ->with(['reporter', 'species', 'zone'])
            ->where('animal_id', $animalId)
            ->orderByDesc('timestamp'));
    }

    /** Riwayat pengamatan AI (kondisi fisik & perilaku) untuk satu individu. */
    public function aiObservations(int $animalId)
    {
        Animal::findOrFail($animalId);

        return $this->list(AiPrediction::query()
            ->with(['media.zone', 'media.device', 'predictedSpecies'])
            ->where(fn ($q) => $q->where('predicted_animal_id', $animalId)->orWhere('verified_animal_id', $animalId))
            ->where('verification_status', '!=', 'rejected')
            ->orderByDesc('processed_at'));
    }

    private function latestObservation(int $animalId): ?array
    {
        $p = AiPrediction::query()
            ->where(fn ($q) => $q->where('predicted_animal_id', $animalId)->orWhere('verified_animal_id', $animalId))
            ->where('processing_status', 'completed')
            ->where('verification_status', '!=', 'rejected')
            ->latest('processed_at')
            ->first();

        return $p ? [
            'prediction_id' => $p->prediction_id,
            'physical_condition' => $p->physical_condition,
            'condition_notes' => $p->condition_notes,
            'behavior' => $p->behavior,
            'behavior_notes' => $p->behavior_notes,
            'observed_at' => $p->processed_at,
        ] : null;
    }

    private function validated(Request $request, ?Animal $animal = null): array
    {
        // `name` (dipakai frontend) diterima sebagai alias `name_animal`.
        if (! $request->has('name_animal') && $request->has('name')) {
            $request->merge(['name_animal' => $request->input('name')]);
        }

        $required = $animal ? 'sometimes' : 'required';

        $data = $request->validate([
            'name_animal' => [$required, 'string', 'max:100',
                Rule::unique('animals', 'name_animal')->ignore($animal?->animal_id, 'animal_id')],
            'species_id' => "{$required}|integer|exists:species,species_id",
            'gender' => "{$required}|in:male,female,unknown",
            'birth_date' => 'nullable|date|before_or_equal:today',
            'distinctive_marks' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = $request->boolean('is_active');
        }

        return $data;
    }
}
