<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiMediaRequest;
use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Services\AiPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiPredictionController extends Controller
{
    protected $aiService;

    // Inject Service melalui Constructor
    public function __construct(AiPredictionService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * GET: List semua prediksi AI
     */
    public function index()
    {
        $predictions = AiPrediction::with([
            'media', 
            'predictedSpecies', 
            'verifiedSpecies',
            'verifier'
        ])
        ->orderBy('processed_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $predictions,
        ], 200);
    }

    /**
     * POST: Upload Gambar & Trigger AI
     */
    public function upload(StoreAiMediaRequest $request)
    {
        // 1. Simpan File ke Storage (public/ai-uploads)
        $file = $request->file('file');
        $path = $file->store('ai-uploads', 'public');

        // 2. Buat Record Media Upload
        $media = AiMediaUpload::create([
            'uploaded_by' => $request->user()->user_id,
            'report_id' => $request->input('report_id'),
            'media_type' => 'image',
            'file_path' => $path,
        ]);

        // 3. Jalankan AI Processing
        try {
            $prediction = $this->aiService->processImage($media);
            
            return response()->json([
                'success' => true,
                'message' => 'Gambar berhasil diunggah dan dianalisis oleh AI.',
                'data' => [
                    'media' => $media,
                    'prediction' => $prediction->load(['predictedSpecies'])
                ]
            ], 201);

        } catch (\Exception $e) {
            // Rollback: Hapus file jika AI gagal memproses
            Storage::disk('public')->delete($path);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses gambar dengan AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: Verifikasi Prediksi AI (Human-in-the-loop)
     */
    public function verify(Request $request, $id)
    {
        $request->validate([
            'verification_status' => 'required|in:confirmed,rejected',
            'verified_species_id' => 'required|integer|exists:species,species_id',
        ]);

        $prediction = AiPrediction::findOrFail($id);

        // Update data verifikasi
        $prediction->update([
            'verification_status' => $request->verification_status,
            'verified_species_id' => $request->verified_species_id,
            'verified_by' => $request->user()->user_id, // Dari token, bukan input
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prediksi AI berhasil diverifikasi.',
            'data' => $prediction->fresh(['verifiedSpecies', 'verifier'])
        ], 200);
    }
}