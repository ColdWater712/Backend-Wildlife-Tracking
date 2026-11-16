<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalRequest;
use App\Models\Animal;

class AnimalController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['species', 'trackingDevice'])->get();

        return response()->json([
            'success' => true,
            'data' => $animals
        ], 200);
    }

    public function store(StoreAnimalRequest $request)
    {
        $animal = Animal::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Satwa berhasil ditambahkan',
            'data' => $animal
        ], 201);
    }
}