<?php

namespace App\Services;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\Species;
use App\Services\HuggingFaceService;
use Illuminate\Support\Facades\Log;

class AiPredictionService
{
    protected $hfService;
    protected $confidenceThreshold;

    // Mapping label HuggingFace → keyword database spesies
    protected $labelMapping = [
        'orangutan' => ['orangutan', 'pongo', 'primate', 'ape', 'monkey'],
        'tiger' => ['tiger', 'panthera', 'cat', 'feline'],
        'elephant' => ['elephant', 'elephas', 'pachyderm'],
        'rhinoceros' => ['rhino', 'rhinoceros', 'dicerorhinus'],
        'bear' => ['bear', 'ursus', 'helarctos'],
        'deer' => ['deer', 'rusa', 'cervus', 'sambar'],
    ];

    public function __construct(HuggingFaceService $hfService)
    {
        $this->hfService = $hfService;
        $this->confidenceThreshold = 0.60;
    }

    public function processImage(AiMediaUpload $media): AiPrediction
    {
        $filePath = storage_path('app/public/' . $media->file_path);
        $species = Species::all();

        if ($species->isEmpty()) {
            throw new \Exception("Tidak ada data spesies untuk referensi AI.");
        }

        $predictedSpecies = null;
        $confidence = 0.0;
        $modelVersion = env('HF_MODEL_IMAGE_CLASSIFICATION', 'google/vit-base-patch16-224');
        $aiLabel = null;

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File gambar tidak ditemukan: {$filePath}");
            }

            Log::info("HF AI Request: Media #{$media->media_id}, Path: {$filePath}");

            // Panggil Hugging Face API
            $result = $this->hfService->classifyImage($filePath);
            $predictions = $result['predictions'] ?? [];

            if (empty($predictions)) {
                throw new \Exception("Tidak ada prediksi dari Hugging Face");
            }

            // Ambil prediksi dengan score tertinggi
            $topPrediction = $predictions[0];
            $aiLabel = strtolower(trim($topPrediction['label']));
            $confidence = (float) $topPrediction['score'];

            Log::info("HF AI Detected: {$aiLabel} (confidence: {$confidence})");

            // Validasi confidence
            if ($confidence < $this->confidenceThreshold) {
                Log::warning("Confidence {$confidence} di bawah threshold {$this->confidenceThreshold}");
                $aiLabel = null;
            } else {
                // Match label ke database spesies
                $predictedSpecies = $this->matchSpeciesByLabel($aiLabel, $species);
            }

        } catch (\Exception $e) {
            Log::error("HF AI Service error: " . $e->getMessage());
        }

        // Fallback deterministik jika AI gagal
        if (!$predictedSpecies) {
            $fileHash = crc32(file_get_contents($filePath));
            $index = abs($fileHash) % $species->count();
            $predictedSpecies = $species->values()[$index];
            $confidence = 0.30;
            $modelVersion = 'Deterministic-Fallback';

            Log::info("Using Deterministic Fallback: {$predictedSpecies->common_name}");
        }

        return AiPrediction::create([
            'media_id' => $media->media_id,
            'predicted_species_id' => $predictedSpecies->species_id,
            'confidence' => $confidence,
            'model_version' => $modelVersion,
            'processing_status' => 'completed',
            'processed_at' => now(),
            'verification_status' => 'pending',
        ]);
    }

    protected function matchSpeciesByLabel(string $label, $species)
    {
        // Cek mapping langsung
        foreach ($this->labelMapping as $keyword => $aliases) {
            if (str_contains($label, $keyword)) {
                foreach ($aliases as $alias) {
                    $matched = $species->first(function ($sp) use ($alias) {
                        $commonName = strtolower($sp->common_name);
                        $sciName = strtolower($sp->scientific_name);
                        return str_contains($commonName, $alias) || str_contains($sciName, $alias);
                    });
                    if ($matched) return $matched;
                }
            }
        }

        // Fuzzy match
        return $species->first(function ($sp) use ($label) {
            $commonName = strtolower($sp->common_name);
            $sciName = strtolower($sp->scientific_name);
            return str_contains($commonName, $label) || str_contains($sciName, $label);
        });
    }
}