<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index()
    {
        // Eager loading relasi species dan trackingDevice
        $animals = Animal::with(['species', 'trackingDevice'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $animals
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'species_id' => 'required|exists:species,species_id',
            'name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,unknown',
            'birth_date' => 'nullable|date',
        ]);

        $animal = Animal::create($request->all());
        
        return response()->json([
            'success' => true, 
            'message' => 'Satwa berhasil ditambahkan', 
            'data' => $animal
        ], 201);
    }
}