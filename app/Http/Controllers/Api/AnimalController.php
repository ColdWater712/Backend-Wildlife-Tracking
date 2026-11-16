<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalRequest;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['species', 'trackingDevice'])->get();

        return response()->json([
            'success' => true,
            'data' => $animals,
        ], 200);
    }

    public function store(StoreAnimalRequest $request)
    {
        $validated = $request->validated();

        $validated['is_active'] = true;

        $animal = Animal::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Satwa berhasil ditambahkan',
            'data' => $animal,
        ], 201);
    }

    public function show($id)
    {
        $animal = Animal::with(['species', 'trackingDevice'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $animal,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $animal = Animal::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
            'gender' => 'sometimes|in:male,female,unknown',
            'birth_date' => 'sometimes|date|before_or_equal:today',
            'is_active' => 'sometimes|boolean',
        ]);

        $animal->update($request->only(['name', 'gender', 'birth_date', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Data satwa berhasil diperbarui',
            'data' => $animal,
        ], 200);
    }

    public function destroy($id)
    {
        $animal = Animal::findOrFail($id);
        $animal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satwa berhasil dihapus',
        ], 200);
    }
}