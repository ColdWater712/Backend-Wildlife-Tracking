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
        $this->model   = (string) env('GEMINI_MODEL', 'gemini-1.5-flash');
        $this->timeout = (int) env('GEMINI_TIMEOUT', 60);
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

        if (empty($this->apiKey)) {
            throw new \Exception('GEMINI_API_KEY tidak ditemukan di .env');
        }
    }

    /**
     * ✅ Klasifikasi gambar satwa (prediksi spesies)
     * Mengembalikan format sama dengan HuggingFaceService agar controller tidak perlu diubah
     */
    public function classifyImage(string $imagePath, ?string $model = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';

            $prompt = "You are a wildlife identification expert for Taman Nasional Gunung Leuser.
Analyze this image and identify the animal species.
Return ONLY a valid JSON array (no markdown, no code fences, no extra text) in this EXACT format:
[{\"label\": \"species common name in Indonesian\", \"score\": 0.95}]
List up to 3 possible species with confidence scores. Use Indonesian common names like 'Orangutan Sumatra', 'Harimau Sumatra', 'Gajah Sumatra', 'Badak Sumatra', 'Rusa Sambar', 'Beruang Madu'.";

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$this->model}:generateContent", [
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
                        'temperature' => 0.2,
                        'maxOutputTokens' => 500,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $text = $response->json('candidates.0.content.parts.0.text') ?? '';
            $predictions = $this->extractJsonArray($text);

            if (empty($predictions)) {
                throw new \Exception('Gemini tidak mengembalikan prediksi valid: ' . $text);
            }

            Log::info('Gemini classify OK', ['path' => basename($imagePath), 'result' => $predictions[0] ?? null]);

            return [
                'success' => true,
                'model' => $this->model . ' (vision)',
                'predictions' => $predictions,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini classify failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Deteksi objek dalam gambar (bounding box)
     */
    public function detectObjects(string $imagePath, ?string $model = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';

            $prompt = "Detect all animals and objects in this image.
Return ONLY a valid JSON array (no markdown, no extra text) in this EXACT format:
[{\"box\": {\"xmin\": 0, \"ymin\": 0, \"xmax\": 100, \"ymax\": 100}, \"label\": \"object name\", \"score\": 0.9}]
Use percentage-based coordinates (0-100). List all detected animals/objects.";

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$this->model}:generateContent", [
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
                        'temperature' => 0.2,
                        'maxOutputTokens' => 800,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $text = $response->json('candidates.0.content.parts.0.text') ?? '';
            $objects = $this->extractJsonArray($text);

            Log::info('Gemini detect OK', ['count' => count($objects)]);

            return [
                'success' => true,
                'model' => $this->model . ' (vision)',
                'objects' => $objects,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini detect failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Ringkasan teks untuk AI Summary Report
     */
    public function summarizeText(string $text, ?string $model = null, int $maxLength = 200): array
    {
        try {
            $prompt = "Buatlah ringkasan eksekutif profesional dalam bahasa Indonesia dari data monitoring satwa berikut.
Fokus pada: jumlah satwa aktif, insiden kritis, kondisi perangkat, dan rekomendasi tindakan.
Maksimal {$maxLength} karakter. Jangan gunakan markdown, hanya teks murni.

DATA:
{$text}";

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$this->model}:generateContent", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 400,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API error: ' . $response->body());
            }

            $summary = trim($response->json('candidates.0.content.parts.0.text') ?? '');

            Log::info('Gemini summarize OK', ['length' => strlen($summary)]);

            return [
                'success' => true,
                'model' => $this->model . ' (text)',
                'summary' => $summary,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini summarize failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Health check API Gemini
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}");

            if ($response->successful()) {
                return [
                    'status' => 'ok',
                    'message' => 'Gemini API aktif dan terhubung',
                    'model' => $this->model,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Gemini API menolak: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper: Extract JSON array dari response LLM yang mungkin mengandung markdown
     */
    protected function extractJsonArray(string $text): array
    {
        $text = trim($text);

        // Hapus markdown code fence ```json ... ```
        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        // Cari array JSON pertama
        if (preg_match('/\[\s*\{.*?\}\s*\]/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fallback: coba decode langsung
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : [];
    }
}