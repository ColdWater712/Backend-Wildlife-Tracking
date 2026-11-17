<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiMediaRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Services\AiPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiPredictionController extends Controller
{
    use ApiResponse;
    protected $aiService;

    public function __construct(AiPredictionService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $query = AiPrediction::with(['media', 'predictedSpecies', 'verifiedSpecies', 'verifier']);

        if ($request->filled('media_id')) {
            $query->where('media_id', $request->media_id);
        }
        if ($request->filled('processing_status')) {
            $query->where('processing_status', $request->processing_status);
        }
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }
        if ($request->filled('model_version')) {
            $query->where('model_version', $request->model_version);
        }

        $query->orderBy('processed_at', 'desc');

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        return $this->success(AiPrediction::with(['media', 'predictedSpecies', 'verifiedSpecies', 'verifier'])->findOrFail($id));
    }

    public function pendingVerification()
    {
        $predictions = AiPrediction::with(['media', 'predictedSpecies'])
            ->where('verification_status', 'pending')
            ->orderBy('processed_at', 'desc')
            ->get();
        return $this->success($predictions);
    }

    public function upload(StoreAiMediaRequest $request)
    {
        $file = $request->file('file');
        $path = $file->store('ai-uploads', 'public');

        $media = AiMediaUpload::create([
            'uploaded_by' => $request->user()->user_id,
            'report_id' => $request->input('report_id'),
            'media_type' => 'image',
            'file_path' => $path,
        ]);

        try {
            $prediction = $this->aiService->processImage($media);

            // Log aktivitas
            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->user_id,
                'action' => 'UPLOAD_AI_MEDIA',
                'details' => json_encode(['media_id' => $media->media_id, 'prediction_id' => $prediction->prediction_id]),
                'timestamp' => now(),
            ]);

            return $this->success([
                'media' => $media,
                'prediction' => $prediction->load(['predictedSpecies']),
            ], 'Gambar berhasil diunggah dan dianalisis oleh AI.', 201);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($path);
            $media->delete();
            return $this->error('Gagal memproses gambar dengan AI: ' . $e->getMessage(), 500);
        }
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

        // Log aktivitas
        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id,
            'action' => 'VERIFY_AI_PREDICTION',
            'details' => json_encode([
                'prediction_id' => $id,
                'status' => $request->verification_status,
                'species_id' => $request->verified_species_id,
            ]),
            'timestamp' => now(),
        ]);

        return $this->success(
            $prediction->fresh(['verifiedSpecies', 'verifier']),
            'Prediksi AI berhasil diverifikasi.'
        );
    }

    public function retry($id)
    {
        $prediction = AiPrediction::findOrFail($id);
        $media = $prediction->media;

        try {
            $newPrediction = $this->aiService->processImage($media);

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'RETRY_AI_PREDICTION',
                'details' => json_encode(['prediction_id' => $id, 'new_prediction_id' => $newPrediction->prediction_id]),
                'timestamp' => now(),
            ]);

            return $this->success($newPrediction->load(['predictedSpecies']), 'Prediksi diulang', 201);
        } catch (\Exception $e) {
            return $this->error('Gagal mengulang prediksi: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $prediction = AiPrediction::findOrFail($id);
        $request->validate([
            'processing_status' => 'sometimes|in:queued,processing,completed,failed',
            'confidence' => 'sometimes|numeric|between:0,1',
        ]);
        $prediction->update($request->validated());
        return $this->success($prediction->fresh(), 'Prediksi diperbarui');
    }

    public function destroy($id)
    {
        AiPrediction::findOrFail($id)->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'DELETE_AI_PREDICTION',
            'details' => json_encode(['prediction_id' => $id]),
            'timestamp' => now(),
        ]);

        return $this->success(null, 'Prediksi dihapus');
    }

    public function mediaUploads(Request $request)
    {
        $query = AiMediaUpload::with(['uploader', 'report', 'predictions']);

        if ($request->filled('uploaded_by')) {
            $query->where('uploaded_by', $request->uploaded_by);
        }
        if ($request->filled('report_id')) {
            $query->where('report_id', $request->report_id);
        }
        if ($request->filled('media_type')) {
            $query->where('media_type', $request->media_type);
        }

        $query->orderBy('uploaded_at', 'desc');

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function showMedia($id)
    {
        return $this->success(AiMediaUpload::with(['uploader', 'report', 'predictions'])->findOrFail($id));
    }

    public function file($id)
    {
        $media = AiMediaUpload::findOrFail($id);
        if (!Storage::disk('public')->exists($media->file_path)) {
            return $this->error('File tidak ditemukan', 404);
        }
        return Storage::disk('public')->response($media->file_path);
    }

    public function destroyMedia($id)
    {
        $media = AiMediaUpload::findOrFail($id);
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }
        $media->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'DELETE_AI_MEDIA',
            'details' => json_encode(['media_id' => $id]),
            'timestamp' => now(),
        ]);

        return $this->success(null, 'Media dihapus');
    }

    // ✅ Endpoint baru: Trigger prediction untuk media
    public function triggerPrediction($mediaId)
    {
        $media = AiMediaUpload::findOrFail($mediaId);

        try {
            $prediction = $this->aiService->processImage($media);

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'TRIGGER_AI_PREDICTION',
                'details' => json_encode(['media_id' => $mediaId, 'prediction_id' => $prediction->prediction_id]),
                'timestamp' => now(),
            ]);

            return $this->success($prediction->load(['predictedSpecies']), 'Prediksi AI berhasil diproses', 201);
        } catch (\Exception $e) {
            return $this->error('Gagal memproses prediksi: ' . $e->getMessage(), 500);
        }
    }

    // ✅ Endpoint baru: Get predictions untuk media tertentu
    public function getMediaPredictions($mediaId)
    {
        $media = AiMediaUpload::findOrFail($mediaId);
        $predictions = $media->predictions()->with(['predictedSpecies', 'verifiedSpecies', 'verifier'])->get();
        return $this->success($predictions);
    }
}