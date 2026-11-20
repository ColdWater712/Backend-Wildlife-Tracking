<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceService
{
    protected $apiToken;
    protected $apiUrl;
    protected $timeout;

    public function __construct()
    {
        $this->apiToken = env('HF_API_TOKEN');
        $this->apiUrl = rtrim(env('HF_API_URL', 'https://api-inference.huggingface.co/models'), '/');
        $this->timeout = (int) env('HF_API_TIMEOUT', 30);
    }

    /**
     * Klasifikasi gambar (prediksi spesies satwa)
     */
    public function classifyImage(string $imagePath, string $model = null): array
    {
        $model = $model ?? env('HF_MODEL_IMAGE_CLASSIFICATION', 'google/vit-base-patch16-224');
        
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                ])
                ->attach('file', file_get_contents($imagePath), 'image.jpg')
                ->post("{$this->apiUrl}/{$model}");

            if (!$response->successful()) {
                throw new \Exception("HF API error: " . $response->body());
            }

            return [
                'success' => true,
                'model' => $model,
                'predictions' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error("HF Image Classification failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deteksi objek dalam gambar (bounding box)
     */
    public function detectObjects(string $imagePath, string $model = null): array
    {
        $model = $model ?? env('HF_MODEL_OBJECT_DETECTION', 'facebook/detr-resnet-50');
        
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                ])
                ->attach('file', file_get_contents($imagePath), 'image.jpg')
                ->post("{$this->apiUrl}/{$model}");

            if (!$response->successful()) {
                throw new \Exception("HF API error: " . $response->body());
            }

            return [
                'success' => true,
                'model' => $model,
                'objects' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error("HF Object Detection failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate ringkasan teks (untuk summary report)
     */
    public function summarizeText(string $text, string $model = null, int $maxLength = 150): array
    {
        $model = $model ?? env('HF_MODEL_TEXT_SUMMARIZATION', 'facebook/bart-large-cnn');

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiUrl}/{$model}", [
                    'inputs' => $text,
                    'parameters' => [
                        'max_length' => $maxLength,
                        'min_length' => 50,
                        'do_sample' => false,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception("HF API error: " . $response->body());
            }

            return [
                'success' => true,
                'model' => $model,
                'summary' => $response->json()[0]['summary_text'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error("HF Text Summarization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cek kesehatan API
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                ])
                ->get("{$this->apiUrl}/google/vit-base-patch16-224");

            return [
                'status' => $response->successful() ? 'ok' : 'error',
                'message' => $response->successful() ? 'API aktif' : 'API error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}