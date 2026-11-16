<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpeciesRequest;
use App\Models\Species;

class SpeciesController extends Controller
{
    public function index()
    {
        $species = Species::with('animals')->get();

        return response()->json([
            'success' => true,
            'data' => $species,
        ], 200);
    }

    public function store(StoreSpeciesRequest $request)
    {
        $species = Species::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Spesies berhasil ditambahkan',
            'data' => $species,
        ], 201);
    }

    public function show($id)
    {
        $species = Species::with('animals')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $species,
        ], 200);
    }
}