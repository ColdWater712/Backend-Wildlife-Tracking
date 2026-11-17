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

        $sort = $request->input('orderBy', 'species_id');
        $dir = $request->input('order', 'asc');
        $query->orderBy($sort, $dir);

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        $species = Species::with(['animals' => fn($q) => $q->where('species_id', $id)])->findOrFail($id);
        return $this->success($species);
    }

    public function store(StoreSpeciesRequest $request)
    {
        $species = Species::create($request->validated());
        return $this->success($species, 'Spesies berhasil dibuat', 201);
    }

    public function update(UpdateSpeciesRequest $request, $id)
    {
        $species = Species::findOrFail($id);
        $species->update($request->validated());
        return $this->success($species->fresh(), 'Spesies diperbarui');
    }

    public function destroy($id)
    {
        $species = Species::findOrFail($id);
        if ($species->animals()->count() > 0) {
            return $this->error('Spesies masih memiliki satwa terkait', 422);
        }
        $species->delete();
        return $this->success(null, 'Spesies dihapus');
    }
}