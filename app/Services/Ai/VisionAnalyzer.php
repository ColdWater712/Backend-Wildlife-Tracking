<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Analisis gambar kamera jebak: spesies, jumlah individu, kondisi fisik,
 * perilaku, ciri pembeda individu, serta tanda aktivitas manusia/ancaman.
 */
class VisionAnalyzer
{
    public function __construct(
        private GroqClient $groq,
        private SpeciesMatcher $matcher,
    ) {}

    public function modelVersion(): string
    {
        return 'groq:' . $this->groq->visionModel();
    }

    /**
     * @param  array{zone_name?: ?string, zone_type?: ?string, captured_at?: ?string, device?: ?string}  $context
     */
    public function analyze(string $imagePath, array $context = []): array
    {
        if (! is_file($imagePath)) {
            throw new RuntimeException('File gambar tidak ditemukan.');
        }

        $result = $this->groq->chatJson(
            [$this->groq->imageMessage($this->prompt($context), $imagePath)],
            $this->groq->visionModel(),
            1500,
        );

        return $this->normalize($result['json'], $result['raw']);
    }

    private function prompt(array $context): string
    {
        $species = $this->matcher->promptList() ?: '- (belum ada data spesies)';
        $where = trim(sprintf(
            '%s %s',
            $context['zone_name'] ?? '',
            isset($context['zone_type']) ? "(tipe zona: {$context['zone_type']})" : '',
        ));
        $when = $context['captured_at'] ?? 'tidak diketahui';
        $device = $context['device'] ?? 'kamera lapangan';

        return <<<PROMPT
Anda adalah ahli ekologi satwa liar Sumatra yang menganalisis foto kamera jebak.
Lokasi: {$where}. Waktu pengambilan: {$when}. Sumber: {$device}.

Spesies yang dipantau di sistem (prioritaskan jika cocok):
{$species}

Analisis gambar dan balas HANYA satu objek JSON valid dengan kunci berikut:
{
  "animal_detected": true|false,
  "species_common_name": "nama umum spesies (bahasa Indonesia) atau null",
  "species_scientific_name": "nama ilmiah atau null",
  "confidence": 0.0-1.0,
  "individual_count": bilangan bulat,
  "physical_condition": "healthy|injured|sick|emaciated|unknown",
  "condition_notes": "penjelasan singkat kondisi fisik (luka, bekas jerat, kurus, bulu rontok, dll)",
  "behavior": "foraging|resting|moving|running|drinking|social|parenting|mating|alert|aggressive|unknown",
  "behavior_notes": "penjelasan singkat perilaku",
  "distinctive_features": "ciri fisik pembeda individu (bekas luka, pola belang, telinga sobek, ukuran cula, dll) atau null",
  "human_activity": {
    "humans_present": true|false,
    "weapons": true|false,
    "traps_or_snares": true|false,
    "logging": true|false,
    "vehicles": true|false,
    "fire_or_smoke": true|false,
    "notes": "keterangan singkat"
  },
  "objects": [{"label": "nama objek", "score": 0.0-1.0, "box": {"xmin": 0-100, "ymin": 0-100, "xmax": 0-100, "ymax": 0-100}}],
  "summary": "ringkasan 1-2 kalimat dalam bahasa Indonesia"
}
Koordinat box dalam persen lebar/tinggi gambar. Jika ragu, gunakan "unknown" dan confidence rendah. Jangan mengarang.
PROMPT;
    }

    private function normalize(array $json, string $raw): array
    {
        $human = is_array($json['human_activity'] ?? null) ? $json['human_activity'] : [];
        $flags = [
            'humans_present' => (bool) ($human['humans_present'] ?? false),
            'weapons' => (bool) ($human['weapons'] ?? false),
            'traps_or_snares' => (bool) ($human['traps_or_snares'] ?? $human['traps_snares'] ?? false),
            'logging' => (bool) ($human['logging'] ?? false),
            'vehicles' => (bool) ($human['vehicles'] ?? false),
            'fire_or_smoke' => (bool) ($human['fire_or_smoke'] ?? $human['fire'] ?? false),
            'notes' => isset($human['notes']) ? (string) $human['notes'] : null,
        ];

        $objects = collect(is_array($json['objects'] ?? null) ? $json['objects'] : [])
            ->filter(fn ($o) => is_array($o) && isset($o['label']))
            ->map(fn ($o) => [
                'label' => (string) $o['label'],
                'score' => isset($o['score']) ? round(min(1, max(0, (float) $o['score'])), 4) : null,
                'box' => is_array($o['box'] ?? null) ? array_map('floatval', array_intersect_key(
                    $o['box'], array_flip(['xmin', 'ymin', 'xmax', 'ymax'])
                )) : null,
            ])
            ->take(30)->values()->all();

        $common = $this->str($json['species_common_name'] ?? $json['label'] ?? null);
        $scientific = $this->str($json['species_scientific_name'] ?? null);
        $animalDetected = (bool) ($json['animal_detected'] ?? ($common !== null));

        return [
            'animal_detected' => $animalDetected,
            'species_common_name' => $common,
            'species_scientific_name' => $scientific,
            'species' => $animalDetected ? $this->matcher->match($scientific, $common) : null,
            'confidence' => round(min(1, max(0, (float) ($json['confidence'] ?? $json['score'] ?? 0))), 4),
            'individual_count' => max(0, (int) ($json['individual_count'] ?? ($animalDetected ? 1 : 0))),
            'physical_condition' => $animalDetected ? AnalysisVocabulary::condition($json['physical_condition'] ?? null) : null,
            'condition_notes' => $this->str($json['condition_notes'] ?? null),
            'behavior' => $animalDetected ? AnalysisVocabulary::behavior($json['behavior'] ?? null) : null,
            'behavior_notes' => $this->str($json['behavior_notes'] ?? null),
            'distinctive_features' => $this->str($json['distinctive_features'] ?? null),
            'human_activity' => $flags,
            'objects' => $objects,
            'summary' => $this->str($json['summary'] ?? null),
            'raw' => mb_substr($raw, 0, 4000),
        ];
    }

    private function str(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' || in_array(mb_strtolower($value), ['null', 'none', 'unknown', '-'], true) ? null : $value;
    }
}
