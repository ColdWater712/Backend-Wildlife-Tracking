<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiPrediction;
use Illuminate\Http\Request;

class AiPredictionController extends Controller
{
    public function index()
    {
        $predictions = AiPrediction::with(['media', 'predictedSpecies', 'verifiedSpecies'])->get();

        return response()->json([
            'success' => true,
            'data' => $predictions,
        ], 200);
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'verification_status' => 'required|in:confirmed,rejected',
            'verified_species_id' => 'required|integer|exists:species,species_id',
        ]);

        $prediction = AiPrediction::findOrFail($id);

        $prediction->update([
            'verification_status' => $request->verification_status,
            'verified_species_id' => $request->verified_species_id,
            'verified_by' => $request->user()->user_id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prediksi berhasil diverifikasi',
            'data' => $prediction,
        ], 200);
    }
}