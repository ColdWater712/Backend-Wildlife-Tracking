<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpeciesRequest;
use App\Models\Species;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    public function index(Request $request)
    {
        $query = Species::with('animals');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('common_name', 'like', '%' . $search . '%')
                  ->orWhere('scientific_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('conservation_status') && $request->conservation_status !== 'all') {
            $query->where('conservation_status', $request->conservation_status);
        }

        $species = $query->get();

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
        $species = Species::with(['animals' => function ($query) use ($id) {
            $query->where('species_id', $id);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $species,
        ], 200);
    }
}