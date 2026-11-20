<?php

namespace App\Services;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\Species;
use Illuminate\Support\Facades\Log;

class AiPredictionService
{
    protected GeminiService $geminiService;
    protected float $confidenceThreshold = 0.50;

    // ✅ Mapping lebih fleksibel: kata kunci pendek saja
    protected array $labelMapping = [
        'harimau'   => ['harimau', 'tiger', 'panthera', 'macan'],
        'gajah'     => ['gajah', 'elephant', 'elephas'],
        'orangutan' => ['orangutan', 'pongo', 'ape', 'primate', 'kera', 'monkey'],
        'badak'     => ['badak', 'rhino', 'rhinoceros'],
        'rusa'      => ['rusa', 'deer', 'sambar', 'cervus'],
        'beruang'   => ['beruang', 'bear', 'helarctos'],
        'babi'      => ['babi', 'boar', 'pig', 'sus'],
        'macan'     => ['macan', 'leopard', 'dahan', 'neofelis'],
    ];

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function processImage(AiMediaUpload $media): AiPrediction
    {
        $filePath = storage_path('app/public/' . $media->file_path);
        $species  = Species::all();

        if ($species->isEmpty()) {
            throw new \Exception('Tidak ada data spesies untuk referensi AI.');
        }

        $predictedSpecies = null;
        $confidence       = 0.0;
        $modelVersion     = env('GEMINI_MODEL', 'gemini-3.8-flash');
        $processingStatus = 'completed';

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File gambar tidak ditemukan: {$filePath}");
            }

            $result      = $this->geminiService->classifyImage($filePath);
            $predictions = $result['predictions'] ?? [];

            if (empty($predictions)) {
                throw new \Exception('Gemini tidak mengembalikan prediksi');
            }

            $topPrediction = $predictions[0];
            $aiLabel       = strtolower(trim($topPrediction['label'] ?? 'unknown'));
            $confidence    = (float) ($topPrediction['score'] ?? 0.0);

            Log::info("🤖 GEMINI PREDICTION", [
                'media_id'   => $media->media_id,
                'label'      => $aiLabel,
                'confidence' => $confidence,
            ]);

            $predictedSpecies = $this->matchSpeciesByLabel($aiLabel, $species);

            if (!$predictedSpecies) {
                Log::warning("⚠️ Label '{$aiLabel}' tidak cocok dengan database spesies");
                $processingStatus = 'unrecognized';
            } elseif ($confidence < $this->confidenceThreshold) {
                Log::info("📉 Confidence rendah: {$confidence}");
            }

        } catch (\Exception $e) {
            Log::error('❌ Gemini AI error: ' . $e->getMessage());
            $processingStatus = 'failed';
        }

        return AiPrediction::create([
            'media_id'             => $media->media_id,
            'predicted_species_id' => $predictedSpecies?->species_id,
            'confidence'           => $confidence,
            'model_version'        => $modelVersion,
            'processing_status'    => $processingStatus,
            'processed_at'         => now(),
            'verification_status'  => 'pending',
        ]);
    }

    protected function matchSpeciesByLabel(string $label, $species): ?Species
    {
        foreach ($this->labelMapping as $keyword => $aliases) {
            if (str_contains($label, $keyword)) {
                foreach ($aliases as $alias) {
                    $matched = $species->first(function ($sp) use ($alias) {
                        return str_contains(strtolower($sp->common_name), trim($alias))
                            || str_contains(strtolower($sp->scientific_name), trim($alias));
                    });
                    if ($matched) return $matched;
                }
            }
        }

        return $species->first(function ($sp) use ($label) {
            return str_contains(strtolower($sp->common_name), $label)
                || str_contains(strtolower($sp->scientific_name), $label);
        });
    }
}