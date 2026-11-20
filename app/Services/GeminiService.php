<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = (string) env('GEMINI_API_KEY', '');
        $this->model   = (string) env('GEMINI_MODEL', 'gemini-3.8-flash');
        $this->timeout = (int) env('GEMINI_TIMEOUT', 60);
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

        if (empty($this->apiKey)) {
            throw new \Exception('GEMINI_API_KEY tidak ditemukan di .env');
        }
    }

    protected function http()
    {
        return Http::timeout($this->timeout)
            ->withOptions(['verify' => false]);
    }

    /**
     * Klasifikasi gambar satwa
     */
    public function classifyImage(string $imagePath, ?string $model = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';
            $modelName = $model ?? $this->model;

            // ✅ PROMPT SUPER KETAT: minta jawaban SANGAT PENDEK agar tidak terpotong
            $prompt = "What animal is in this image? Answer with ONLY this exact JSON format, nothing else:
[{\"label\":\"<animal>\",\"score\":<0.0-1.0>}]
Rules:
- label must be ONE word or short phrase in Indonesian (e.g. Harimau, Gajah, Orangutan)
- score is confidence 0.0 to 1.0
- if no animal visible, use label \"unknown\" and score 0.0
- NO explanation, NO markdown, NO extra text. Just the JSON array.
- Keep response under 50 characters total.";

            $response = $this->http()
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$modelName}:generateContent", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => base64_encode($imageData),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.0,          // ✅ Deterministic, tidak kreatif
                        'maxOutputTokens' => 1024,     // ✅ NAIKKAN dari 200 → 1024
                        'responseMimeType' => 'application/json', // ✅ PAKSA JSON
                    ],
                ]);

            Log::info('🤖 GEMINI RAW RESPONSE', [
                'model'  => $modelName,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $text = $response->json('candidates.0.content.parts.0.text') ?? '';

            Log::info('🤖 GEMINI PARSED TEXT', ['text' => $text]);

            $predictions = $this->extractJsonArray($text);

            if (empty($predictions)) {
                throw new \Exception('Gemini tidak mengembalikan prediksi valid. Raw: ' . $text);
            }

            return [
                'success'     => true,
                'model'       => $modelName . ' (vision)',
                'predictions' => $predictions,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini classify failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deteksi objek
     */
    public function detectObjects(string $imagePath, ?string $model = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';
            $modelName = $model ?? $this->model;

            $prompt = "Detect objects in this image. Return ONLY a JSON array, nothing else:
[{\"box\":{\"xmin\":0,\"ymin\":0,\"xmax\":100,\"ymax\":100},\"label\":\"<object>\",\"score\":0.9}]
Coordinates are percentages 0-100. Keep response concise.";

            $response = $this->http()
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$modelName}:generateContent", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => base64_encode($imageData),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.0,
                        'maxOutputTokens' => 2048,     // ✅ NAIKKAN
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            Log::info('🤖 GEMINI DETECT RAW', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $text    = $response->json('candidates.0.content.parts.0.text') ?? '';
            $objects = $this->extractJsonArray($text);

            return [
                'success' => true,
                'model'   => $modelName . ' (vision)',
                'objects' => $objects,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini detect failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ringkasan teks
     */
    public function summarizeText(string $text, ?string $model = null, int $maxLength = 200): array
    {
        try {
            $modelName = $model ?? $this->model;
            $prompt = "Buat ringkasan profesional dalam bahasa Indonesia dari data monitoring satwa ini. Maksimal {$maxLength} karakter. Jangan gunakan markdown. Data: {$text}";

            $response = $this->http()
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$modelName}:generateContent", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 1024,     // ✅ NAIKKAN
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $summary = trim($response->json('candidates.0.content.parts.0.text') ?? '');

            return [
                'success' => true,
                'model'   => $modelName . ' (text)',
                'summary' => $summary,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini summarize failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Health check
     */
    public function healthCheck(): array
    {
        try {
            $response = $this->http()
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}");

            return $response->successful()
                ? ['status' => 'ok', 'message' => 'Gemini API aktif', 'model' => $this->model]
                : ['status' => 'error', 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * ✅ Extract JSON array — sekarang lebih tahan terhadap response terpotong
     */
    protected function extractJsonArray(string $text): array
    {
        $text = trim($text);
        if (empty($text)) return [];

        // Strategi 1: Hapus markdown code fence
        if (preg_match('/```(?:json)?\s*(\[[\s\S]*?\])\s*```/', $text, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) return $decoded;
        }

        // Strategi 2: Decode langsung
        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        // Strategi 3: Cari array JSON utuh
        if (preg_match('/\[\s*\{.*?\}\s*\]/', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) return $decoded;
        }

        // Strategi 4: ✅ PERBAIKAN — coba "tutup" JSON yang terpotong
        // Jika text mulai dengan [ tapi tidak berakhir dengan ], coba lengkapi
        if (str_starts_with($text, '[') && !str_ends_with($text, ']')) {
            $candidates = [
                $text . '}]',           // tutup string + object + array
                $text . '"}]',          // tutup string dengan quote + object + array
                $text . ',"score":0.5}]',
            ];
            foreach ($candidates as $candidate) {
                $decoded = json_decode($candidate, true);
                if (is_array($decoded) && !empty($decoded)) {
                    Log::info('🔧 JSON terpotong berhasil diperbaiki', ['original' => $text, 'fixed' => $candidate]);
                    return $decoded;
                }
            }
        }

        // Strategi 5: ✅ Regex fallback — extract label & score manual
        if (preg_match('/"label"\s*:\s*"([^"]*)"/', $text, $mLabel)) {
            $score = 0.7; // default jika terpotong
            if (preg_match('/"score"\s*:\s*([0-9.]+)/', $text, $mScore)) {
                $score = (float) $mScore[1];
            }
            Log::info('🔧 Fallback regex extract berhasil', ['label' => $mLabel[1], 'score' => $score]);
            return [['label' => $mLabel[1], 'score' => $score]];
        }

        Log::warning('⚠️ Gagal parse JSON dari Gemini', ['text' => $text]);
        return [];
    }
}