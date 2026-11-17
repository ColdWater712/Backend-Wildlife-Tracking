<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpeciesRequest;
use App\Http\Requests\UpdateSpeciesRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Species;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Species::withCount('animals');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('common_name', 'like', "%{$s}%")->orWhere('scientific_name', 'like', "%{$s}%"));
        }
        if ($request->filled('conservation_status') && $request->conservation_status !== 'all') {
            $query->where('conservation_status', $request->conservation_status);
        }

        $query->orderBy($request->input('orderBy', 'species_id'), $request->input('order', 'asc'));

        // ✅ FE species.service pakai unwrapData → array
        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(Species::with(['animals' => fn($q) => $q->where('species_id', $id)])->findOrFail($id));
    }

    public function store(StoreSpeciesRequest $request)
    {
        $species = Species::create($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'CREATE_SPECIES',
            'details' => json_encode(['species_id' => $species->species_id]), 'timestamp' => now(),
        ]);

        return $this->success($species, 'Spesies berhasil dibuat', 201);
    }

    public function update(UpdateSpeciesRequest $request, $id)
    {
        $species = Species::findOrFail($id);
        $species->update($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_SPECIES',
            'details' => json_encode(['species_id' => $species->species_id]), 'timestamp' => now(),
        ]);

        return $this->success($species->fresh(), 'Spesies diperbarui');
    }

    public function destroy($id)
    {
        $species = Species::findOrFail($id);
        if ($species->animals()->count() > 0) return $this->error('Spesies masih memiliki satwa terkait', 422);
        $species->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'DELETE_SPECIES',
            'details' => json_encode(['species_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success(null, 'Spesies dihapus');
    }
}