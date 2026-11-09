<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Species;

class SpeciesController extends Controller
{
    public function index()
    {
        $species = Species::all();
        return response()->json([
            'success' => true,
            'data' => $species
        ], 200);
    }

    public function show($id)
    {
        // Mencari berdasarkan species_id (bukan id)
        $species = Species::with('animals')->find($id);
        
        if (!$species) {
            return response()->json(['message' => 'Spesies tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $species
        ], 200);
    }
}