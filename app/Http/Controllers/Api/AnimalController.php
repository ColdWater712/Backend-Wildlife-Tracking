<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Animal::with(['species', 'device']);

        if ($request->filled('species_id')) $query->where('species_id', $request->species_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('gender')) $query->where('gender', $request->gender);
        if ($request->filled('device_id')) $query->whereHas('device', fn($q) => $q->where('device_id', $request->device_id));
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(Animal::with(['species', 'device', 'gpsReadings', 'incidents'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'species_id' => 'required|exists:species,species_id',
            'gender'     => 'required|in:male,female',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'is_active'  => 'boolean',
        ]);

        $animal = Animal::create([
            'name'       => $request->name,
            'species_id' => $request->species_id,
            'gender'     => $request->gender,
            'birth_date' => $request->birth_date,
            'is_active'  => $request->input('is_active', true),
        ]);

        return $this->success($animal->load(['species']), 'Animal berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $animal = Animal::findOrFail($id);
        $request->validate([
            'name'       => 'sometimes|string|max:100',
            'species_id' => 'sometimes|exists:species,species_id',
            'gender'     => 'sometimes|in:male,female',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'is_active'  => 'boolean',
        ]);
        $animal->update($request->only(['name', 'species_id', 'gender', 'birth_date', 'is_active']));
        return $this->success($animal->fresh(['species']), 'Animal diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $animal = Animal::findOrFail($id);
        $request->validate(['is_active' => 'required|boolean']);
        $animal->update(['is_active' => $request->is_active]);
        return $this->success($animal->fresh(), 'Status animal diperbarui');
    }

    public function destroy($id)
    {
        Animal::findOrFail($id)->delete();
        return $this->success(null, 'Animal dihapus');
    }
}