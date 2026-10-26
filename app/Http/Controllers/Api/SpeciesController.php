<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Species;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    public function index()
    {
        $species = Species::all();

        return response()->json([
            'success' => true,
            'message' => 'Data spesies berhasil diambil.',
            'data' => $species,
            'errors' => null
        ], 200);
    }
}