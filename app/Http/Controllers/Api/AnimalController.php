<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Animal::with(['species', 'trackingDevice']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhereHas('species', fn($sq) => $sq->where('common_name', 'like', "%{$s}%")));
        }

        if ($request->filled('species_id')) {
            $query->where('species_id', $request->species_id);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('is_active') && $request->input('is_active') !== 'all') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $sort = $request->input('orderBy', 'animal_id');
        $dir = $request->input('order', 'asc');
        $query->orderBy($sort, $dir);

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        $animal = Animal::with(['species', 'trackingDevice.gpsReadings', 'geoZoneVisits.zone'])->findOrFail($id);
        return $this->success($animal);
    }

    public function store(StoreAnimalRequest $request)
    {
        $animal = Animal::create($request->validated());
        return $this->success($animal->load('species'), 'Satwa berhasil dibuat', 201);
    }

    public function update(UpdateAnimalRequest $request, $id)
    {
        $animal = Animal::findOrFail($id);
        $animal->update($request->validated());
        return $this->success($animal->fresh()->load('species'), 'Satwa diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $animal = Animal::findOrFail($id);
        $animal->update(['is_active' => $request->boolean('is_active')]);
        return $this->success($animal->fresh(), 'Status satwa diperbarui');
    }

    public function destroy($id)
    {
        $animal = Animal::findOrFail($id);
        $animal->delete();
        return $this->success(null, 'Satwa dihapus (soft delete)');
    }
}