<?php

namespace App\Services;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\TrackingDevice;
use App\Services\Ai\AnalysisVocabulary;
use App\Services\Ai\AnimalIdentifier;
use App\Services\Ai\AudioAnalyzer;
use App\Services\Ai\ThreatResponder;
use App\Services\Ai\VisionAnalyzer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Orkestrasi pipeline AI:
 *   media (kamera / audio / unggahan petugas)
 *     -> analisis (vision: spesies, kondisi fisik, perilaku, ancaman | audio: jenis suara)
 *     -> identifikasi individu satwa (lokasi perangkat + GPS + zona + ciri fisik)
 *     -> respon ancaman (insiden otomatis + notifikasi).
 */
class AiPredictionService
{
    public const DISK = 'local';

    public function __construct(
        private VisionAnalyzer $vision,
        private AudioAnalyzer $audio,
        private AnimalIdentifier $identifier,
        private ThreatResponder $responder,
        private GeoService $geo,
    ) {}

    public static function mediaTypeFromMime(?string $mime): ?string
    {
        return match (true) {
            str_starts_with((string) $mime, 'image/') => 'image',
            str_starts_with((string) $mime, 'video/') => 'video',
            str_starts_with((string) $mime, 'audio/') => 'audio',
            default => null,
        };
    }

    /**
     * Simpan berkas ke private storage + metadata. Lokasi diambil dari input,
     * atau dari titik pemasangan perangkat kamera/audio.
     */
    public function storeMedia(UploadedFile $file, array $attrs): AiMediaUpload
    {
        $mime = $file->getMimeType();
        $type = self::mediaTypeFromMime($mime) ?? throw new RuntimeException('Tipe berkas tidak didukung.');

        $device = isset($attrs['device_id']) ? TrackingDevice::find($attrs['device_id']) : null;
        $lat = $attrs['latitude'] ?? $device?->latitude;
        $lon = $attrs['longitude'] ?? $device?->longitude;
        $zoneId = $attrs['zone_id'] ?? $device?->zone_id
            ?? $this->geo->resolveZone($lat !== null ? (float) $lat : null, $lon !== null ? (float) $lon : null)?->zone_id;

        $path = $file->store("ai-media/{$type}/" . now()->format('Y/m'), self::DISK);

        return AiMediaUpload::create([
            'uploaded_by' => $attrs['uploaded_by'] ?? null,
            'report_id' => $attrs['report_id'] ?? null,
            'device_id' => $device?->device_id,
            'zone_id' => $zoneId,
            'media_type' => $type,
            'file_path' => $path,
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'latitude' => $lat,
            'longitude' => $lon,
            'captured_at' => $attrs['captured_at'] ?? now(),
            'uploaded_at' => now(),
        ]);
    }

    public function deleteMedia(AiMediaUpload $media): void
    {
        DB::transaction(function () use ($media) {
            $path = $media->file_path;
            $media->predictions()->delete();
            $media->delete();
            Storage::disk(self::DISK)->delete($path);
        });
    }

    /** Jalankan analisis untuk media. Selalu mengembalikan prediksi (status failed bila gagal). */
    public function process(AiMediaUpload $media, ?AiPrediction $existing = null): AiPrediction
    {
        $media->loadMissing(['zone', 'device']);
        $analysisType = $media->media_type === 'audio' ? 'audio' : 'image';
        $path = Storage::disk(self::DISK)->path($media->file_path);
        $context = [
            'zone_name' => $media->zone?->zone_name,
            'zone_type' => $media->zone?->zone_type,
            'captured_at' => optional($media->captured_at)->toDateTimeString(),
            'device' => $media->device ? "{$media->device->device_type} {$media->device->model}" : null,
        ];

        $tempFrame = null;
        try {
            if ($media->media_type === 'video') {
                $tempFrame = $this->extractVideoFrame($path);
                $path = $tempFrame;
            }

            $attributes = $analysisType === 'audio'
                ? $this->fromAudio($this->audio->analyze($path, $context), $media)
                : $this->fromVision($this->vision->analyze($path, $context), $media);
        } catch (\Throwable $e) {
            Log::error('Analisis AI gagal', ['media_id' => $media->media_id, 'error' => $e->getMessage()]);
            $attributes = [
                'model_version' => $analysisType === 'audio' ? 'audio-pipeline' : $this->vision->modelVersion(),
                'processing_status' => 'failed',
                'ai_summary' => 'Analisis gagal: ' . mb_substr($e->getMessage(), 0, 400),
                'threat_level' => 'none',
            ];
        } finally {
            if ($tempFrame && is_file($tempFrame)) {
                @unlink($tempFrame);
            }
        }

        $attributes += ['analysis_type' => $analysisType, 'processed_at' => now()];

        $prediction = $this->save($media, $attributes, $existing);

        if ($prediction->processing_status === 'completed') {
            try {
                $this->responder->respond($prediction, $media->fresh(['zone', 'device']));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $prediction->fresh(['media', 'predictedSpecies', 'predictedAnimal', 'verifiedSpecies', 'verifier']);
    }

    private function fromVision(array $r, AiMediaUpload $media): array
    {
        $identity = $this->identifier->identify(
            $r['species'],
            $media->latitude, $media->longitude, $media->zone_id,
            $media->captured_at ?? now(),
            $r['distinctive_features'],
        );

        $flags = $r['human_activity'];
        $threat = AnalysisVocabulary::maxThreat(
            ($flags['weapons'] || $flags['traps_or_snares']) ? 'critical' : 'none',
            ($flags['logging'] || $flags['fire_or_smoke']) ? 'high' : 'none',
            ($flags['humans_present'] && in_array($media->zone?->zone_type, ['koridor_ekologis', null], true)) ? 'medium' : 'none',
            $flags['vehicles'] ? 'low' : 'none',
            $r['physical_condition'] === 'injured' ? 'medium' : 'none',
        );

        $status = match (true) {
            $r['animal_detected'] && $r['species'] !== null => 'completed',
            ! $r['animal_detected'] && AiPrediction::threatRank($threat) > 0 => 'completed',
            default => 'unrecognized',
        };

        return [
            'model_version' => $this->vision->modelVersion(),
            'processing_status' => $status,
            'predicted_species_id' => $r['species']?->species_id,
            'confidence' => $r['confidence'],
            'detected_label' => mb_substr((string) ($r['species_common_name'] ?? $r['species_scientific_name'] ?? ($r['animal_detected'] ? 'unknown' : 'no_animal')), 0, 100),
            'individual_count' => $r['individual_count'],
            'predicted_animal_id' => $identity['animal']?->animal_id,
            'animal_match_confidence' => $identity['confidence'],
            'animal_match_method' => $identity['method'],
            'physical_condition' => $r['physical_condition'],
            'condition_notes' => $r['condition_notes'],
            'behavior' => $r['behavior'],
            'behavior_notes' => $r['behavior_notes'],
            'threat_level' => $threat,
            'detected_objects' => $r['objects'],
            'ai_summary' => $r['summary'],
            'raw_response' => [
                'human_activity' => $flags,
                'distinctive_features' => $r['distinctive_features'],
                'species_label' => [$r['species_common_name'], $r['species_scientific_name']],
                'identification_candidates' => $identity['candidates'],
                'model_output' => $r['raw'],
            ],
        ];
    }

    private function fromAudio(array $r, AiMediaUpload $media): array
    {
        $identity = $r['species']
            ? $this->identifier->identify($r['species'], $media->latitude, $media->longitude, $media->zone_id, $media->captured_at ?? now())
            : ['animal' => null, 'confidence' => null, 'method' => null, 'candidates' => []];

        return [
            'model_version' => $r['model_version'],
            'processing_status' => $r['sound_category'] === 'unknown' ? 'unrecognized' : 'completed',
            'predicted_species_id' => $r['species']?->species_id,
            'confidence' => $r['confidence'],
            'detected_label' => $r['sound_label'],
            'predicted_animal_id' => $identity['animal']?->animal_id,
            'animal_match_confidence' => $identity['confidence'],
            'animal_match_method' => $identity['method'],
            'sound_category' => $r['sound_category'],
            'sound_label' => $r['sound_label'],
            'threat_level' => $r['threat_level'],
            'ai_summary' => $r['summary'],
            'raw_response' => [
                'acoustic_features' => $r['features'],
                'identification_candidates' => $identity['candidates'],
                'engine_errors' => $r['engine_errors'] ?? [],
                'model_output' => $r['raw'],
            ],
        ];
    }

    /** Hormati unique(media_id, model_version): retry memperbarui baris yang sama. */
    private function save(AiMediaUpload $media, array $attributes, ?AiPrediction $existing): AiPrediction
    {
        $existing ??= AiPrediction::query()
            ->where('media_id', $media->media_id)
            ->where('model_version', $attributes['model_version'])
            ->first();

        $reset = [
            'verification_status' => 'pending', 'verified_species_id' => null,
            'verified_animal_id' => null, 'verified_by' => null, 'verified_at' => null,
        ];

        if ($existing) {
            $existing->fill(array_merge(array_fill_keys([
                'predicted_species_id', 'confidence', 'detected_label', 'individual_count', 'predicted_animal_id',
                'animal_match_confidence', 'animal_match_method', 'physical_condition', 'condition_notes',
                'behavior', 'behavior_notes', 'sound_category', 'sound_label', 'detected_objects', 'ai_summary', 'raw_response',
            ], null), $reset, $attributes))->save();

            return $existing;
        }

        return AiPrediction::create(array_merge(['media_id' => $media->media_id], $reset, $attributes));
    }

    private function extractVideoFrame(string $videoPath): string
    {
        $ffmpeg = (string) config('services.ffmpeg.path', 'ffmpeg');
        $target = tempnam(sys_get_temp_dir(), 'frame_') . '.jpg';
        $cmd = sprintf('%s -y -loglevel error -ss 00:00:01 -i %s -frames:v 1 %s 2>&1',
            escapeshellcmd($ffmpeg), escapeshellarg($videoPath), escapeshellarg($target));

        exec($cmd, $output, $code);

        if ($code !== 0 || ! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('Tidak dapat mengambil frame video (pastikan ffmpeg terpasang).');
        }

        return $target;
    }
}
