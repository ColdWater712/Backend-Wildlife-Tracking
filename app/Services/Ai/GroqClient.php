<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien tipis untuk Groq (API kompatibel OpenAI).
 * Tidak melempar exception saat dibuat walau API key kosong, sehingga endpoint
 * non-AI tetap jalan; cek isConfigured() sebelum memanggil.
 */
class GroqClient
{
    private string $baseUrl = 'https://api.groq.com/openai/v1';

    public function isConfigured(): bool
    {
        return (string) config('services.groq.key') !== '';
    }

    public function visionModel(): string
    {
        return (string) config('services.groq.vision_model');
    }

    public function textModel(): string
    {
        return (string) config('services.groq.text_model');
    }

    public function audioModel(): string
    {
        return (string) config('services.groq.audio_model');
    }

    /**
     * Chat completion yang mengharapkan objek JSON.
     *
     * @return array{json: array, raw: string, model: string}
     */
    public function chatJson(array $messages, ?string $model = null, int $maxTokens = 1200): array
    {
        $model ??= $this->textModel();

        $response = $this->http()->post("{$this->baseUrl}/chat/completions", [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.1,
            'max_completion_tokens' => $maxTokens,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Groq API error (' . $response->status() . '): ' . mb_substr($response->body(), 0, 300));
        }

        $text = (string) $response->json('choices.0.message.content', '');

        return ['json' => self::decodeJsonObject($text), 'raw' => $text, 'model' => $model];
    }

    public function chatText(string $prompt, ?string $model = null, int $maxTokens = 600): string
    {
        $response = $this->http()->post("{$this->baseUrl}/chat/completions", [
            'model' => $model ?? $this->textModel(),
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.4,
            'max_completion_tokens' => $maxTokens,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Groq API error (' . $response->status() . '): ' . mb_substr($response->body(), 0, 300));
        }

        return trim((string) $response->json('choices.0.message.content', ''));
    }

    /** Pesan user berisi teks + gambar (base64 data URL). */
    public function imageMessage(string $prompt, string $imagePath): array
    {
        $mime = mime_content_type($imagePath) ?: 'image/jpeg';

        return [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => [
                    'url' => "data:{$mime};base64," . base64_encode((string) file_get_contents($imagePath)),
                ]],
            ],
        ];
    }

    /** Transkripsi audio (Whisper). Mengembalikan teks + segmen. */
    public function transcribe(string $audioPath): array
    {
        $response = $this->http(multipart: true)
            ->attach('file', fopen($audioPath, 'r'), basename($audioPath))
            ->post("{$this->baseUrl}/audio/transcriptions", [
                'model' => $this->audioModel(),
                'response_format' => 'verbose_json',
                'temperature' => 0,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Groq transcription error (' . $response->status() . '): ' . mb_substr($response->body(), 0, 300));
        }

        return [
            'text' => trim((string) $response->json('text', '')),
            'language' => $response->json('language'),
            'segments' => array_map(fn ($s) => [
                'start' => $s['start'] ?? null,
                'end' => $s['end'] ?? null,
                'text' => $s['text'] ?? '',
                'no_speech_prob' => $s['no_speech_prob'] ?? null,
            ], (array) $response->json('segments', [])),
        ];
    }

    public function healthCheck(): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'not_configured', 'message' => 'GROQ_API_KEY belum diisi di .env'];
        }

        try {
            $response = $this->http(timeout: 10)->get("{$this->baseUrl}/models");

            return $response->successful()
                ? ['status' => 'ok', 'message' => 'Groq API aktif', 'models' => [
                    'vision' => $this->visionModel(),
                    'text' => $this->textModel(),
                    'audio' => $this->audioModel(),
                ]]
                : ['status' => 'error', 'message' => 'Groq merespons ' . $response->status()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /** Ambil objek JSON dari teks model (toleran terhadap markdown fence / teks tambahan). */
    public static function decodeJsonObject(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        $decoded = json_decode($text, true);

        if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $text, $m)) {
            $decoded = json_decode($m[0], true);
        }

        if (is_array($decoded) && array_is_list($decoded)) {
            $decoded = is_array($decoded[0] ?? null) ? $decoded[0] : [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function http(?int $timeout = null, bool $multipart = false)
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('GROQ_API_KEY belum dikonfigurasi.');
        }

        $request = Http::timeout($timeout ?? (int) config('services.groq.timeout', 60))
            ->withToken((string) config('services.groq.key'))
            ->acceptJson();

        return $multipart ? $request : $request->asJson();
    }
}
