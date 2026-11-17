<?php

namespace App\Services;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\Species;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPredictionService
{
    protected $aiServiceUrl;

    // 🎯 PETA LABEL AI → KEYWORD DI DATABASE
    // Tambahkan semua variasi yang mungkin muncul
    protected $labelMapping = [
        'orangutan' => ['orangutan', 'pongo'],
        'harimau_sumatra' => ['harimau', 'tiger', 'panthera'],
        'badak_sumatra' => ['badak', 'rhino', 'rhinoceros'],
        'gajah_sumatra' => ['gajah', 'elephant', 'elephas'],
        // Fallback untuk label COCO yang mungkin muncul
        'bear' => ['beruang', 'bear', 'ursus'],
        'cat' => ['kucing', 'cat', 'prionailurus'],
        'zebra' => ['harimau', 'tiger', 'panthera'], // zebra = belang = harimau
        'elephant' => ['gajah', 'elephant', 'elephas'],
    ];

    // 🎯 MINIMUM CONFIDENCE untuk percaya pada AI
    protected $minConfidence = 0.50;

    public function __construct()
    {
        $this->aiServiceUrl = config('services.ai.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001'));
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
        $modelVersion = 'YOLOv8-Real';
        $aiLabel = null;

        // =====================================================
        // STEP 1: PANGGIL AI SERVICE
        // =====================================================
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File gambar tidak ditemukan: {$filePath}");
            }

            Log::info("AI Request: Media #{$media->media_id}, Path: {$filePath}");

            $response = Http::timeout(30) // naikkan ke 30 detik
                ->attach('file', file_get_contents($filePath), 'image.jpg')
                ->post("{$this->aiServiceUrl}/predict");

            Log::info("AI Response Status: {$response->status()}, Body: {$response->body()}");

            if ($response->successful() && $response->json('success')) {
                $aiLabel = strtolower(trim($response->json('label')));
                $confidence = (float) $response->json('confidence');

                Log::info("AI Detected: {$aiLabel} with confidence {$confidence}");

                // =====================================================
                // STEP 2: VALIDASI CONFIDENCE
                // =====================================================
                if ($confidence < $this->minConfidence) {
                    Log::warning("AI confidence terlalu rendah ({$confidence} < {$this->minConfidence}), skip");
                    $aiLabel = null;
                } else {
                    // =====================================================
                    // STEP 3: MATCH LABEL KE DATABASE
                    // =====================================================
                    $predictedSpecies = $this->matchSpeciesByLabel($aiLabel, $species);
                    
                    if (!$predictedSpecies) {
                        Log::warning("Label '{$aiLabel}' tidak match dengan spesies di database");
                    }
                }
            } else {
                Log::error("AI Service return unsuccessful: {$response->body()}");
            }

        } catch (\Exception $e) {
            Log::error("AI Service error: " . $e->getMessage());
        }

        // =====================================================
        // STEP 4: DETERMINISTIC FALLBACK (bukan random!)
        // =====================================================
        if (!$predictedSpecies) {
            // ✅ FIX: Gunakan fallback deterministik berdasarkan hash file
            // Ini memastikan gambar yang sama SELALU dapat hasil yang sama
            $fileHash = crc32(file_get_contents($filePath));
            $index = $fileHash % $species->count();
            $predictedSpecies = $species->values()[$index];
            
            $confidence = 0.30; // confidence rendah = perlu verifikasi manusia
            $modelVersion = 'Deterministic-Fallback';
            
            Log::info("Using Deterministic Fallback: {$predictedSpecies->common_name} (hash-based)");
        }

        // =====================================================
        // STEP 5: SIMPAN HASIL
        // =====================================================
        $prediction = AiPrediction::create([
            'media_id' => $media->media_id,
            'predicted_species_id' => $predictedSpecies->species_id,
            'confidence' => $confidence,
            'model_version' => $modelVersion,
            'processing_status' => 'completed',
            'processed_at' => now(),
            'verification_status' => 'pending',
        ]);

        Log::info("Final Prediction: Media #{$media->media_id} → {$predictedSpecies->common_name} ({$confidence}) [{$modelVersion}]");

        return $prediction;
    }

    /**
     * 🎯 Fungsi matching label AI ke spesies database
     */
    protected function matchSpeciesByLabel(string $label, $species)
    {
        // 1. Cek mapping langsung
        if (isset($this->labelMapping[$label])) {
            $keywords = $this->labelMapping[$label];
            $matched = $species->first(function ($sp) use ($keywords) {
                $commonName = strtolower($sp->common_name);
                $sciName = strtolower($sp->scientific_name);
                foreach ($keywords as $keyword) {
                    if (str_contains($commonName, $keyword) || str_contains($sciName, $keyword)) {
                        return true;
                    }
                }
                return false;
            });
            if ($matched) return $matched;
        }

        // 2. Fuzzy match: cari spesies yang namanya mengandung label
        $matched = $species->first(function ($sp) use ($label) {
            $commonName = strtolower($sp->common_name);
            $sciName = strtolower($sp->scientific_name);
            return str_contains($commonName, $label) || str_contains($sciName, $label);
        });
        if ($matched) return $matched;

        // 3. Reverse match: label mengandung nama spesies
        $matched = $species->first(function ($sp) use ($label) {
            $commonName = strtolower($sp->common_name);
            $sciName = strtolower($sp->scientific_name);
            return str_contains($label, $commonName) || str_contains($label, $sciName);
        });
        if ($matched) return $matched;

        return null;
    }
}