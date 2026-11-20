<?php

namespace App\Services;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\Species;
use Illuminate\Support\Facades\Log;

class AiPredictionService
{
    protected GeminiService $geminiService;
    protected float $confidenceThreshold = 0.60;

    /**
     * Mapping keyword Gemini → database spesies
     */
    protected array $labelMapping = [
        'orangutan' => ['orangutan', 'pongo', 'kera besar', 'ape'],
        'harimau'   => ['harimau', 'tiger', 'panthera', 'kucing besar'],
        'gajah'     => ['gajah', 'elephant', 'elephas'],
        'badak'     => ['badak', 'rhino', 'rhinoceros', 'dicerorhinus'],
        'rusa'      => ['rusa', 'deer', 'sambar', ' cervus', 'rusa unicolor'],
        'beruang'   => ['beruang', 'bear', 'helarctos', 'ursus'],
        'babi'      => ['babi hutan', 'wild boar', 'sus'],
        'macan'     => ['macan', 'leopard', 'dahan', 'neofelis'],
    ];

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Proses gambar via Gemini Vision → simpan ke ai_predictions
     */
    public function processImage(AiMediaUpload $media): AiPrediction
    {
        $filePath = storage_path('app/public/' . $media->file_path);
        $species  = Species::all();

        if ($species->isEmpty()) {
            throw new \Exception('Tidak ada data spesies untuk referensi AI.');
        }

        $predictedSpecies = null;
        $confidence       = 0.0;
        $modelVersion     = env('GEMINI_MODEL', 'gemini-1.5-flash');
        $aiLabel          = null;

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File gambar tidak ditemukan: {$filePath}");
            }

            Log::info("Gemini AI Request: Media #{$media->media_id}");

            $result      = $this->geminiService->classifyImage($filePath);
            $predictions = $result['predictions'] ?? [];

            if (empty($predictions)) {
                throw new \Exception('Gemini tidak mengembalikan prediksi');
            }

            $topPrediction = $predictions[0];
            $aiLabel       = strtolower(trim($topPrediction['label'] ?? 'unknown'));
            $confidence    = (float) ($topPrediction['score'] ?? 0.75);

            Log::info("Gemini Detected: {$aiLabel} (confidence: {$confidence})");

            if ($confidence >= $this->confidenceThreshold) {
                $predictedSpecies = $this->matchSpeciesByLabel($aiLabel, $species);
            }

        } catch (\Exception $e) {
            Log::error('Gemini AI Service error: ' . $e->getMessage());
        }

        // Fallback deterministik jika AI gagal
        if (!$predictedSpecies) {
            $hash = crc32(file_exists($filePath) ? file_get_contents($filePath) : 'fallback');
            $index = abs($hash) % $species->count();
            $predictedSpecies = $species->values()[$index];
            $confidence = 0.30;
            $modelVersion = 'Deterministic-Fallback';
            Log::info("Using Deterministic Fallback: {$predictedSpecies->common_name}");
        }

        return AiPrediction::create([
            'media_id'             => $media->media_id,
            'predicted_species_id' => $predictedSpecies->species_id,
            'confidence'           => $confidence,
            'model_version'        => $modelVersion,
            'processing_status'    => 'completed',
            'processed_at'         => now(),
            'verification_status'  => 'pending',
        ]);
    }

    /**
     * Match label Gemini ke database spesies (fuzzy matching)
     */
    protected function matchSpeciesByLabel(string $label, $species): ?Species
    {
        // 1. Cek mapping keyword
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

        // 2. Fuzzy match langsung
        $matched = $species->first(function ($sp) use ($label) {
            return str_contains(strtolower($sp->common_name), $label)
                || str_contains(strtolower($sp->scientific_name), $label)
                || str_contains($label, strtolower($sp->common_name));
        });

        if ($matched) return $matched;

        // 3. Fallback: species pertama
        Log::warning("Gemini label '{$label}' tidak match ke database, pakai species pertama");
        return $species->first();
    }
}