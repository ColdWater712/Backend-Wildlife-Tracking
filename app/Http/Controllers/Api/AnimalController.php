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
        if ($request->filled('species_id')) $query->where('species_id', $request->species_id);
        if ($request->filled('gender'))     $query->where('gender', $request->gender);
        if ($request->has('is_active') && $request->input('is_active') !== 'all') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy($request->input('orderBy', 'animal_id'), $request->input('order', 'asc'));

        // ✅ FE animal.service pakai unwrapData → array
        return $this->list($query->get());
    }

    public function show($id)
    {
        $animal = Animal::with(['species', 'trackingDevice.gpsReadings', 'geoZoneVisits.zone'])->findOrFail($id);
        return $this->success($animal);
    }

    public function store(StoreAnimalRequest $request)
    {
        $animal = Animal::create($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'CREATE_ANIMAL',
            'details' => json_encode(['animal_id' => $animal->animal_id]), 'timestamp' => now(),
        ]);

        return $this->success($animal->load('species'), 'Satwa berhasil dibuat', 201);
    }

    public function update(UpdateAnimalRequest $request, $id)
    {
        $animal = Animal::findOrFail($id);
        $animal->update($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_ANIMAL',
            'details' => json_encode(['animal_id' => $animal->animal_id]), 'timestamp' => now(),
        ]);

        return $this->success($animal->fresh()->load('species'), 'Satwa diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $animal = Animal::findOrFail($id);
        $animal->update(['is_active' => $request->boolean('is_active')]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_ANIMAL_STATUS',
            'details' => json_encode(['animal_id' => $animal->animal_id, 'is_active' => $animal->is_active]), 'timestamp' => now(),
        ]);

        return $this->success($animal->fresh(), 'Status satwa diperbarui');
    }

    public function destroy($id)
    {
        $animal = Animal::findOrFail($id);
        $animal->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'DELETE_ANIMAL',
            'details' => json_encode(['animal_id' => $animal->animal_id]), 'timestamp' => now(),
        ]);

        return $this->success(null, 'Satwa dihapus (soft delete)');
    }
}