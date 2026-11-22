<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Services\AiPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Siklus hidup media & prediksi AI:
 * upload -> analisis (spesies, individu, kondisi fisik, perilaku, suara, ancaman) -> verifikasi petugas.
 */
class AiPredictionController extends ApiController
{
    private const RELATIONS = ['media.uploader', 'media.zone', 'media.device', 'predictedSpecies', 'predictedAnimal', 'verifiedSpecies', 'verifiedAnimal', 'verifier'];

    public function __construct(private AiPredictionService $ai) {}

    public function index(Request $request)
    {
        $query = AiPrediction::query()->with(self::RELATIONS);

        foreach (['processing_status', 'verification_status', 'model_version', 'analysis_type', 'threat_level',
            'physical_condition', 'behavior', 'sound_category'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        foreach (['media_id', 'predicted_species_id', 'predicted_animal_id', 'verified_species_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }
        if ($request->filled('device_id')) {
            $query->whereHas('media', fn ($q) => $q->where('device_id', $request->integer('device_id')));
        }
        if ($request->filled('zone_id')) {
            $query->whereHas('media', fn ($q) => $q->where('zone_id', $request->integer('zone_id')));
        }
        if ($request->filled('min_threat')) {
            $levels = array_slice(AiPrediction::THREAT_LEVELS, AiPrediction::threatRank($request->min_threat));
            $query->whereIn('threat_level', $levels);
        }
        if ($request->filled('min_confidence')) {
            $query->where('confidence', '>=', $request->float('min_confidence'));
        }
        if ($request->filled('start_date')) {
            $query->where('processed_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('processed_at', '<=', $request->date('end_date')->endOfDay());
        }

        return $this->list($query->orderByDesc('processed_at')->orderByDesc('prediction_id'));
    }

    public function show(int $id)
    {
        $prediction = AiPrediction::with(self::RELATIONS)->findOrFail($id);
        $prediction->makeVisible('raw_response');

        return $this->success($prediction);
    }

    public function pendingVerification()
    {
        return $this->list(AiPrediction::query()
            ->with(self::RELATIONS)
            ->where('verification_status', 'pending')
            ->whereIn('processing_status', ['completed', 'unrecognized'])
            ->orderByRaw("CASE threat_level WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByDesc('processed_at'));
    }

    /**
     * Unggah gambar / video / audio untuk dianalisis.
     * Opsional: report_id, device_id (kamera/audio sumber), zone_id, latitude, longitude, captured_at.
     */
    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,mov,webm,wav,mp3,m4a,ogg,flac|max:51200',
            'report_id' => ['nullable', 'integer', Rule::exists('incident_reports', 'report_id')->whereNull('deleted_at')],
            'device_id' => 'nullable|integer|exists:tracking_devices,device_id',
            'zone_id' => 'nullable|integer|exists:geo_zones,zone_id',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
            'captured_at' => 'nullable|date|before_or_equal:' . now()->addMinutes(5)->toDateTimeString(),
        ]);

        $media = $this->ai->storeMedia($request->file('file'), $data + ['uploaded_by' => $request->user()->user_id]);
        $prediction = $this->ai->process($media);

        $message = match ($prediction->processing_status) {
            'completed' => 'Media berhasil diunggah dan dianalisis oleh AI.',
            'unrecognized' => 'Media diunggah, namun AI tidak dapat mengenali objek secara pasti.',
            default => 'Media diunggah, namun analisis AI gagal. Gunakan endpoint retry untuk mencoba lagi.',
        };

        return $this->success([
            'media' => $media->fresh(['uploader', 'zone', 'device', 'report']),
            'prediction' => $prediction,
        ], $message, 201);
    }

    public function verify(Request $request, int $id)
    {
        $prediction = AiPrediction::findOrFail($id);
        $data = $request->validate([
            'verification_status' => 'required|in:confirmed,rejected',
            'verified_species_id' => [
                Rule::requiredIf(fn () => $request->input('verification_status') === 'confirmed' && $prediction->analysis_type === 'image'),
                'nullable', 'integer', 'exists:species,species_id',
            ],
            'verified_animal_id' => ['nullable', 'integer', Rule::exists('animals', 'animal_id')->whereNull('deleted_at')],
        ]);

        DB::transaction(function () use ($prediction, $data, $request) {
            $prediction->update([
                'verification_status' => $data['verification_status'],
                'verified_species_id' => $data['verified_species_id'] ?? null,
                'verified_animal_id' => $data['verified_animal_id'] ?? null,
                'verified_by' => $request->user()->user_id,
                'verified_at' => now(),
            ]);

            ActivityLog::record($request->user()->user_id, 'VERIFY_AI_PREDICTION', [
                'prediction_id' => $prediction->prediction_id,
                'status' => $data['verification_status'],
                'predicted_species_id' => $prediction->predicted_species_id,
                'verified_species_id' => $data['verified_species_id'] ?? null,
            ]);
        });

        return $this->success($prediction->fresh(self::RELATIONS), 'Prediksi AI berhasil diverifikasi.');
    }

    /** Ulangi analisis (memperbarui baris yang sama agar unique(media_id, model_version) terjaga). */
    public function retry(int $id)
    {
        $prediction = AiPrediction::with('media')->findOrFail($id);

        if ($prediction->verification_status !== 'pending') {
            return $this->error('Prediksi yang sudah diverifikasi tidak dapat diulang.', 409);
        }
        if (! $prediction->media) {
            return $this->error('Media prediksi tidak ditemukan.', 404);
        }

        return $this->success($this->ai->process($prediction->media, $prediction), 'Analisis AI diulang');
    }

    public function destroy(int $id)
    {
        $prediction = AiPrediction::findOrFail($id);

        if ($prediction->verification_status !== 'pending') {
            return $this->error('Prediksi yang sudah diverifikasi tidak dapat dihapus.', 409);
        }

        $prediction->delete();

        return $this->success(null, 'Prediksi dihapus');
    }

    // ---------------------------------------------------------------- media

    public function mediaUploads(Request $request)
    {
        $query = AiMediaUpload::query()->with(['uploader', 'report', 'device', 'zone', 'predictions']);

        foreach (['uploaded_by', 'report_id', 'device_id', 'zone_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }
        if ($request->filled('media_type')) {
            $query->where('media_type', $request->media_type);
        }
        if ($request->filled('start_date')) {
            $query->where('uploaded_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('uploaded_at', '<=', $request->date('end_date')->endOfDay());
        }

        return $this->list($query->orderByDesc('uploaded_at'));
    }

    public function showMedia(int $id)
    {
        return $this->success(AiMediaUpload::with(['uploader', 'report', 'device', 'zone', 'predictions.predictedSpecies'])->findOrFail($id));
    }

    /** Streaming berkas dari private storage (hanya untuk user terautentikasi). */
    public function file(int $id)
    {
        $media = AiMediaUpload::findOrFail($id);
        $disk = Storage::disk(AiPredictionService::DISK);

        if (! $disk->exists($media->file_path)) {
            return $this->error('Berkas tidak ditemukan', 404);
        }

        return $disk->response($media->file_path, basename($media->file_path), [
            'Content-Type' => $media->mime_type ?: $disk->mimeType($media->file_path),
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function destroyMedia(int $id)
    {
        $media = AiMediaUpload::withCount(['predictions as verified_count' => fn ($q) => $q->where('verification_status', '!=', 'pending')])->findOrFail($id);

        if ($media->verified_count > 0) {
            return $this->error('Media memiliki prediksi terverifikasi dan disimpan sebagai bukti.', 409);
        }

        $this->ai->deleteMedia($media);

        return $this->success(null, 'Media dan berkasnya dihapus');
    }

    public function triggerPrediction(int $mediaId)
    {
        $media = AiMediaUpload::findOrFail($mediaId);

        return $this->success($this->ai->process($media), 'Analisis AI selesai diproses', 201);
    }

    public function mediaPredictions(int $mediaId)
    {
        AiMediaUpload::findOrFail($mediaId);

        return $this->list(AiPrediction::query()->with(self::RELATIONS)->where('media_id', $mediaId)->orderByDesc('processed_at'));
    }
}
