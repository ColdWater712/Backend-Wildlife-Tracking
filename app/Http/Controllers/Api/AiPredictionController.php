<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiPrediction;
use Illuminate\Http\Request;

class AiPredictionController extends Controller
{
    public function index()
    {
        // Ambil prediksi beserta relasi media dan spesies
        $predictions = AiPrediction::with(['media', 'predictedSpecies', 'verifiedSpecies'])
            ->orderBy('processed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $predictions
        ], 200);
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'verification_status' => 'required|in:confirmed,rejected',
            'verified_species_id' => 'nullable|exists:species,species_id',
        ]);

        $prediction = AiPrediction::find($id);

        if (!$prediction) {
            return response()->json(['message' => 'Prediksi tidak ditemukan'], 404);
        }

        // Update status verifikasi
        $prediction->verification_status = $request->verification_status;
        $prediction->verified_species_id = $request->verified_species_id;
        $prediction->verified_by = $request->user()->user_id;
        $prediction->verified_at = now();
        $prediction->save();

        return response()->json([
            'success' => true,
            'message' => 'Prediksi berhasil diverifikasi',
            'data' => $prediction
        ], 200);
    }
}