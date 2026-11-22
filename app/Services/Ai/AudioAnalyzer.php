<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Klasifikasi rekaman perangkat audio: suara satwa (dan spesiesnya) atau
 * suara ancaman seperti tembakan, gergaji mesin, kendaraan, suara manusia.
 *
 * Urutan engine:
 *  1. Layanan audio eksternal (AI_AUDIO_SERVICE_URL, mis. YAMNet/BirdNET/PANNs) bila dikonfigurasi.
 *  2. Groq: transkripsi Whisper + fitur akustik -> LLM klasifikasi.
 *  3. Heuristik fitur akustik (fallback offline, akurasi terbatas).
 */
class AudioAnalyzer
{
    public function __construct(
        private GroqClient $groq,
        private SpeciesMatcher $matcher,
        private AudioFeatureExtractor $features,
    ) {}

    public function analyze(string $audioPath, array $context = []): array
    {
        if (! is_file($audioPath)) {
            throw new RuntimeException('File audio tidak ditemukan.');
        }

        $features = $this->features->extract($audioPath);
        $errors = [];

        if ($serviceUrl = (string) config('services.ai_audio.url')) {
            try {
                return $this->finalize($this->viaService($serviceUrl, $audioPath), $features, 'service:' . config('services.ai_audio.model', 'audio-classifier'));
            } catch (\Throwable $e) {
                $errors[] = 'service: ' . $e->getMessage();
                Log::warning('AI audio service gagal', ['error' => $e->getMessage()]);
            }
        }

        if ($this->groq->isConfigured()) {
            try {
                return $this->finalize($this->viaGroq($audioPath, $features, $context), $features, 'groq:' . $this->groq->audioModel() . '+' . $this->groq->textModel());
            } catch (\Throwable $e) {
                $errors[] = 'groq: ' . $e->getMessage();
                Log::warning('Analisis audio Groq gagal', ['error' => $e->getMessage()]);
            }
        }

        if ($features === null) {
            throw new RuntimeException('Audio tidak dapat dianalisis' . ($errors ? ' (' . implode('; ', $errors) . ')' : '. Gunakan WAV PCM atau konfigurasi AI_AUDIO_SERVICE_URL / GROQ_API_KEY.'));
        }

        $result = $this->finalize($this->viaHeuristics($features), $features, 'heuristic-audio-v1');
        $result['engine_errors'] = $errors;

        return $result;
    }

    /** Kontrak layanan: POST multipart `file` -> {label, confidence, category?, species?, predictions?: [{label, score}]} */
    private function viaService(string $url, string $path): array
    {
        $response = Http::timeout((int) config('services.ai_audio.timeout', 60))
            ->attach('file', fopen($path, 'r'), basename($path))
            ->post(rtrim($url, '/') . '/classify-audio');

        if (! $response->successful()) {
            throw new RuntimeException('HTTP ' . $response->status());
        }

        $json = (array) $response->json();
        $top = $json['predictions'][0] ?? [];
        $label = (string) ($json['label'] ?? $top['label'] ?? 'unknown');

        return [
            'sound_category' => $json['category'] ?? $label,
            'sound_label' => $label,
            'confidence' => (float) ($json['confidence'] ?? $top['score'] ?? 0),
            'species_common_name' => $json['species'] ?? null,
            'summary' => $json['summary'] ?? null,
            'raw' => $json,
        ];
    }

    private function viaGroq(string $path, ?array $features, array $context): array
    {
        $transcript = null;
        try {
            $transcript = $this->groq->transcribe($path);
        } catch (\Throwable $e) {
            Log::info('Transkripsi audio dilewati: ' . $e->getMessage());
        }

        $species = $this->matcher->promptList() ?: '- (belum ada data spesies)';
        $featureJson = json_encode($features, JSON_UNESCAPED_UNICODE) ?: 'tidak tersedia';
        $text = $transcript['text'] ?? '';
        $zone = trim(($context['zone_name'] ?? '') . ' ' . (isset($context['zone_type']) ? "({$context['zone_type']})" : ''));
        $categories = implode('|', array_keys(AnalysisVocabulary::SOUND_CATEGORIES));
        $when = $context['captured_at'] ?? 'tidak diketahui';

        $prompt = <<<PROMPT
Anda adalah sistem bioakustik untuk patroli hutan Sumatra. Klasifikasikan rekaman dari perangkat audio lapangan.
Lokasi: {$zone}. Waktu: {$when}.
Fitur akustik (RMS, crest factor tinggi + impulsive_events = suara impulsif seperti tembakan;
loud_frame_ratio tinggi dan berkelanjutan = mesin/gergaji): {$featureJson}
Transkrip Whisper (bisa kosong / berisi tag non-ucapan seperti [music], [chainsaw]): "{$text}"
Spesies terpantau:
{$species}

Balas HANYA objek JSON:
{"sound_category": "{$categories}",
 "sound_label": "label singkat spesifik (mis. 'tembakan senapan', 'gergaji mesin', 'auman harimau')",
 "confidence": 0.0-1.0,
 "species_common_name": "jika suara satwa, nama spesies; selain itu null",
 "human_speech_detected": true|false,
 "summary": "ringkasan 1-2 kalimat bahasa Indonesia"}
Jika bukti lemah, gunakan confidence rendah. Jangan mengarang.
PROMPT;

        $result = $this->groq->chatJson([['role' => 'user', 'content' => $prompt]], $this->groq->textModel(), 600);
        $json = $result['json'];

        return [
            'sound_category' => $json['sound_category'] ?? null,
            'sound_label' => $json['sound_label'] ?? null,
            'confidence' => (float) ($json['confidence'] ?? 0),
            'species_common_name' => $json['species_common_name'] ?? null,
            'summary' => $json['summary'] ?? null,
            'raw' => ['llm' => mb_substr($result['raw'], 0, 3000), 'transcript' => $transcript],
        ];
    }

    private function viaHeuristics(array $f): array
    {
        [$category, $label, $confidence] = match (true) {
            $f['is_silent'] => ['environment', 'hening / tidak ada suara signifikan', 0.6],
            $f['impulsive_events'] >= 1 && $f['crest_factor'] >= 8 => ['gunshot', 'suara impulsif keras (indikasi tembakan)', 0.45],
            $f['loud_frame_ratio'] >= 0.5 && $f['approx_dominant_hz'] >= 600 && $f['approx_dominant_hz'] <= 4000 => ['chainsaw', 'suara mesin berkelanjutan (indikasi gergaji mesin)', 0.4],
            $f['loud_frame_ratio'] >= 0.5 && $f['approx_dominant_hz'] < 600 => ['engine', 'suara mesin frekuensi rendah', 0.35],
            $f['approx_dominant_hz'] >= 4000 => ['environment', 'suara frekuensi tinggi (serangga/kicau)', 0.3],
            default => ['unknown', 'tidak dikenali', 0.2],
        };

        return [
            'sound_category' => $category,
            'sound_label' => $label,
            'confidence' => $confidence,
            'species_common_name' => null,
            'summary' => "Klasifikasi heuristik (tanpa model AI): {$label}. Perlu verifikasi petugas.",
            'raw' => ['heuristic' => true],
        ];
    }

    private function finalize(array $r, ?array $features, string $model): array
    {
        $category = AnalysisVocabulary::soundCategory($r['sound_category'] ?? $r['sound_label'] ?? null);
        $species = $category === 'animal_call'
            ? $this->matcher->match($r['species_common_name'] ?? null, $r['sound_label'] ?? null)
            : null;

        return [
            'sound_category' => $category,
            'sound_label' => mb_substr((string) ($r['sound_label'] ?? AnalysisVocabulary::SOUND_CATEGORIES[$category]['label']), 0, 100),
            'confidence' => round(min(1, max(0, (float) ($r['confidence'] ?? 0))), 4),
            'species_common_name' => $r['species_common_name'] ?? null,
            'species' => $species,
            'threat_level' => AnalysisVocabulary::SOUND_CATEGORIES[$category]['threat'],
            'summary' => $r['summary'] ?? null,
            'features' => $features,
            'model_version' => mb_substr($model, 0, 100),
            'raw' => $r['raw'] ?? null,
        ];
    }
}
