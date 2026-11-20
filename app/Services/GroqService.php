<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected string $apiKey;
    protected string $visionModel;
    protected string $textModel;
    protected int $timeout;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey      = (string) env('GROQ_API_KEY', '');
        $this->visionModel = (string) env('GROQ_VISION_MODEL', 'qwen/qwen3.8-27b');
        $this->textModel   = (string) env('GROQ_TEXT_MODEL', 'qwen/qwen3.8-27b');
        $this->timeout     = (int) env('GROQ_TIMEOUT', 30);
        $this->baseUrl     = 'https://api.groq.com/openai/v1';

        if (empty($this->apiKey)) {
            throw new \Exception('GROQ_API_KEY tidak ditemukan di .env');
        }
    }

    protected function http()
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ]);
    }

    /**
     * Klasifikasi gambar satwa via Groq Vision (Qwen)
     */
    public function classifyImage(string $imagePath, ?string $model = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';
            $modelName = $model ?? $this->visionModel;

            $prompt = "Identify the main animal in this image. Respond with ONLY a valid JSON array, no markdown, no extra text. Format: [{\"label\":\"<animal name in Indonesian>\",\"score\":<0.0-1.0>}]. Use short Indonesian names like Harimau, Gajah, Orangutan, Badak, Rusa, Beruang. If no animal visible, use label \"unknown\" and score 0.0.";

            $response = $this->http()->post("{$this->baseUrl}/chat/completions", [
                'model'    => $modelName,
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64," . base64_encode($imageData),
                                ],
                            ],
                        ],
                    ],
                ],
                'temperature'         => 0.0,
                'max_completion_tokens' => 200,
                'response_format'     => ['type' => 'json_object'], // Native JSON mode Qwen
            ]);

            Log::info('🤖 GROQ RAW RESPONSE', [
                'model'  => $modelName,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 800),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Groq API error: ' . $response->body());
            }

            $text = $response->json('choices.0.message.content') ?? '';
            Log::info('🤖 GROQ PARSED TEXT', ['text' => $text]);

            $predictions = $this->extractJsonArray($text);

            if (empty($predictions)) {
                throw new \Exception('Groq tidak mengembalikan prediksi valid. Raw: ' . $text);
            }

            return [
                'success'     => true,
                'model'       => $modelName . ' (vision)',
                'predictions' => $predictions,
            ];

        } catch (\Exception $e) {
            Log::error('Groq classify failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deteksi objek dalam gambar
     */
    public function detectObjects(string $imagePath, ?string $model = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';
            $modelName = $model ?? $this->visionModel;

            $prompt = "Detect all objects in this image. Return ONLY a valid JSON array, no other text. Format: [{\"box\":{\"xmin\":0,\"ymin\":0,\"xmax\":100,\"ymax\":100},\"label\":\"<object>\",\"score\":0.9}]. Coordinates are percentages 0-100.";

            $response = $this->http()->post("{$this->baseUrl}/chat/completions", [
                'model'    => $modelName,
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64," . base64_encode($imageData),
                                ],
                            ],
                        ],
                    ],
                ],
                'temperature'         => 0.0,
                'max_completion_tokens' => 600,
                'response_format'     => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Groq API error: ' . $response->body());
            }

            $text    = $response->json('choices.0.message.content') ?? '';
            $objects = $this->extractJsonArray($text);

            return [
                'success' => true,
                'model'   => $modelName . ' (vision)',
                'objects' => $objects,
            ];

        } catch (\Exception $e) {
            Log::error('Groq detect failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ringkasan teks untuk AI Summary Report
     */
    public function summarizeText(string $text, ?string $model = null, int $maxLength = 200): array
    {
        try {
            $modelName = $model ?? $this->textModel;
            $prompt = "Buat ringkasan eksekutif profesional dalam bahasa Indonesia dari data monitoring satwa berikut. Maksimal {$maxLength} karakter. Jangan gunakan markdown, hanya teks murni. Fokus pada jumlah satwa, insiden kritis, dan rekomendasi tindakan. Data: {$text}";

            $response = $this->http()->post("{$this->baseUrl}/chat/completions", [
                'model'    => $modelName,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature'         => 0.4,
                'max_completion_tokens' => 500,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Groq API error: ' . $response->body());
            }

            $summary = trim($response->json('choices.0.message.content') ?? '');

            return [
                'success' => true,
                'model'   => $modelName . ' (text)',
                'summary' => $summary,
            ];

        } catch (\Exception $e) {
            Log::error('Groq summarize failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Health check
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
                ->get("{$this->baseUrl}/models");

            if ($response->successful()) {
                $models = array_column($response->json('data') ?? [], 'id');
                return [
                    'status'    => 'ok',
                    'message'   => 'Groq API aktif dan terhubung',
                    'model'     => $this->visionModel,
                    'available' => array_values(array_filter($models, fn($m) => str_contains($m, 'qwen') || str_contains($m, 'image'))),
                ];
            }

            return ['status' => 'error', 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract JSON array dari response (tahan terpotong)
     */
    protected function extractJsonArray(string $text): array
    {
        $text = trim($text);
        if (empty($text)) return [];

        // 1. Hapus markdown fence
        if (preg_match('/```(?:json)?\s*(\[[\s\S]*?\])\s*```/', $text, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) return $decoded;
        }

        // 2. Decode langsung
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            if (isset($decoded['label'])) {
                return [['label' => $decoded['label'], 'score' => $decoded['score'] ?? 0.8]];
            }
            return $decoded;
        }

        // 3. Cari array utuh
        if (preg_match('/\[\s*\{.*?\}\s*\]/', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) return $decoded;
        }

        // 4. Perbaiki JSON terpotong
        if (str_starts_with($text, '[') && !str_ends_with($text, ']')) {
            foreach ([$text . '}]', $text . '"}]', $text . ',"score":0.7}]'] as $candidate) {
                $decoded = json_decode($candidate, true);
                if (is_array($decoded) && !empty($decoded)) return $decoded;
            }
        }

        // 5. Regex fallback
        if (preg_match('/"label"\s*:\s*"([^"]+)"/', $text, $mLabel)) {
            $score = 0.7;
            if (preg_match('/"score"\s*:\s*([0-9.]+)/', $text, $mScore)) {
                $score = (float) $mScore[1];
            }
            return [['label' => $mLabel[1], 'score' => $score]];
        }

        Log::warning('⚠️ Gagal parse JSON dari Groq', ['text' => $text]);
        return [];
    }
}