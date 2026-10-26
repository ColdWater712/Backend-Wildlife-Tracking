<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index()
    {
        // Eager load relasi species dan trackingDevice
        $animals = Animal::with(['species', 'trackingDevice'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data satwa berhasil diambil.',
            'data' => $animals,
            'errors' => null
        ], 200);
    }
}