<?php

namespace App\Http\Controllers\Api;

use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpeciesController extends ApiController
{
    public const CONSERVATION_STATUSES = [
        'Least Concern', 'Near Threatened', 'Vulnerable', 'Endangered',
        'Critically Endangered', 'Extinct in the Wild', 'Extinct', 'Data Deficient',
    ];

    public function index(Request $request)
    {
        $query = Species::query()->withCount('animals as animal_count');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('common_name', 'like', "%{$s}%")->orWhere('scientific_name', 'like', "%{$s}%"));
        }
        if ($request->filled('conservation_status')) {
            $query->where('conservation_status', $request->conservation_status);
        }

        $this->applySort($query, $request, ['species_id', 'common_name', 'scientific_name', 'conservation_status', 'created_at'], 'species_id', 'asc');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(
            Species::withCount('animals as animal_count')->with('animals')->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $species = Species::create($this->validated($request));

        return $this->success($species->loadCount('animals as animal_count'), 'Spesies berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $species = Species::findOrFail($id);
        $species->update($this->validated($request, $species));

        return $this->success($species->fresh()->loadCount('animals as animal_count'), 'Spesies diperbarui');
    }

    public function destroy(int $id)
    {
        $species = Species::withCount('animals')->findOrFail($id);

        if ($species->animals_count > 0) {
            return $this->error("Spesies masih memiliki {$species->animals_count} satwa terkait.", 409);
        }

        $species->delete();

        return $this->success(null, 'Spesies dihapus');
    }

    private function validated(Request $request, ?Species $species = null): array
    {
        $required = $species ? 'sometimes' : 'required';

        return $request->validate([
            'common_name' => "{$required}|string|max:100",
            'scientific_name' => [$required, 'string', 'max:150',
                Rule::unique('species', 'scientific_name')->ignore($species?->species_id, 'species_id')],
            'conservation_status' => [$required, Rule::in(self::CONSERVATION_STATUSES)],
        ]);
    }
}
