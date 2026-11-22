<?php

namespace App\Services\Ai;

/**
 * Kosakata baku hasil analisis AI (kode disimpan di DB, label untuk tampilan).
 */
final class AnalysisVocabulary
{
    public const PHYSICAL_CONDITIONS = [
        'healthy' => 'Sehat',
        'injured' => 'Terluka',
        'sick' => 'Sakit / tampak lemah',
        'emaciated' => 'Kurus / malnutrisi',
        'unknown' => 'Tidak diketahui',
    ];

    public const BEHAVIORS = [
        'foraging' => 'Mencari makan / makan',
        'resting' => 'Istirahat / tidur',
        'moving' => 'Berjalan / berpindah',
        'running' => 'Berlari / melarikan diri',
        'drinking' => 'Minum',
        'social' => 'Interaksi sosial',
        'parenting' => 'Mengasuh anak',
        'mating' => 'Kawin / bercumbu',
        'alert' => 'Waspada',
        'aggressive' => 'Agresif',
        'unknown' => 'Tidak diketahui',
    ];

    public const SOUND_CATEGORIES = [
        'animal_call' => ['label' => 'Suara satwa', 'threat' => 'none', 'incident' => null],
        'gunshot' => ['label' => 'Tembakan', 'threat' => 'critical', 'incident' => 'perburuan'],
        'chainsaw' => ['label' => 'Gergaji mesin', 'threat' => 'high', 'incident' => 'perambahan'],
        'tree_falling' => ['label' => 'Pohon tumbang', 'threat' => 'high', 'incident' => 'perambahan'],
        'fire' => ['label' => 'Kebakaran', 'threat' => 'high', 'incident' => 'kebakaran_hutan'],
        'engine' => ['label' => 'Mesin / alat berat', 'threat' => 'medium', 'incident' => 'perambahan'],
        'vehicle' => ['label' => 'Kendaraan', 'threat' => 'medium', 'incident' => null],
        'human_voice' => ['label' => 'Suara manusia', 'threat' => 'low', 'incident' => null],
        'dog_bark' => ['label' => 'Gonggongan anjing (indikasi pemburu)', 'threat' => 'medium', 'incident' => null],
        'environment' => ['label' => 'Suara alam (hujan/angin/serangga)', 'threat' => 'none', 'incident' => null],
        'unknown' => ['label' => 'Tidak dikenali', 'threat' => 'none', 'incident' => null],
    ];

    public const THREAT_LEVELS = [
        'none' => 'Tidak ada ancaman',
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
        'critical' => 'Kritis',
    ];

    /** Sinonim -> kode baku. */
    private const CONDITION_SYNONYMS = [
        'injured' => ['injur', 'luka', 'wound', 'bleed', 'darah', 'pincang', 'limp', 'cacat'],
        'sick' => ['sick', 'sakit', 'illness', 'lemah', 'weak', 'lethargic', 'disease', 'penyakit', 'infeksi'],
        'emaciated' => ['emaciat', 'kurus', 'thin', 'malnutri', 'underweight', 'skinny'],
        'healthy' => ['healthy', 'sehat', 'good condition', 'baik'],
    ];

    private const BEHAVIOR_SYNONYMS = [
        'foraging' => ['forag', 'feed', 'eat', 'makan', 'graz', 'browsing'],
        'resting' => ['rest', 'sleep', 'istirahat', 'tidur', 'lying', 'berbaring', 'duduk'],
        'drinking' => ['drink', 'minum'],
        'running' => ['run', 'lari', 'flee', 'kabur'],
        'moving' => ['walk', 'move', 'jalan', 'berpindah', 'travel', 'climb', 'memanjat', 'swim'],
        'social' => ['social', 'groom', 'play', 'bermain', 'interaksi'],
        'parenting' => ['nurs', 'parent', 'mother', 'induk', 'menyusui', 'anak'],
        'mating' => ['mating', 'kawin', 'courtship'],
        'aggressive' => ['aggress', 'agresif', 'attack', 'menyerang', 'charging', 'fight'],
        'alert' => ['alert', 'waspada', 'vigilan', 'watch', 'staring'],
    ];

    private const SOUND_SYNONYMS = [
        'gunshot' => ['gunshot', 'gun', 'shot', 'tembak', 'senapan', 'rifle', 'firearm', 'explosion', 'ledakan'],
        'chainsaw' => ['chainsaw', 'chain saw', 'gergaji', 'saw'],
        'tree_falling' => ['tree fall', 'falling tree', 'pohon tumbang', 'timber', 'crack'],
        'fire' => ['fire', 'api', 'kebakaran', 'crackling', 'burn'],
        'engine' => ['engine', 'mesin', 'machinery', 'generator', 'excavator', 'bulldozer'],
        'vehicle' => ['vehicle', 'car', 'truck', 'motor', 'motorcycle', 'kendaraan', 'truk', 'mobil'],
        'dog_bark' => ['dog', 'bark', 'anjing', 'gonggong'],
        'human_voice' => ['speech', 'voice', 'talk', 'human', 'manusia', 'bicara', 'teriak', 'shout'],
        'environment' => ['rain', 'wind', 'insect', 'thunder', 'hujan', 'angin', 'serangga', 'stream', 'water', 'silence'],
        'animal_call' => ['call', 'roar', 'growl', 'bird', 'song', 'trumpet', 'howl', 'auman', 'kicau', 'animal', 'satwa'],
    ];

    public static function condition(?string $value): string
    {
        return self::normalize($value, self::PHYSICAL_CONDITIONS, self::CONDITION_SYNONYMS);
    }

    public static function behavior(?string $value): string
    {
        return self::normalize($value, self::BEHAVIORS, self::BEHAVIOR_SYNONYMS);
    }

    public static function soundCategory(?string $value): string
    {
        return self::normalize($value, self::SOUND_CATEGORIES, self::SOUND_SYNONYMS);
    }

    public static function threat(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return array_key_exists($value, self::THREAT_LEVELS) ? $value : 'none';
    }

    public static function maxThreat(string ...$levels): string
    {
        $keys = array_keys(self::THREAT_LEVELS);
        $max = 0;
        foreach ($levels as $level) {
            $max = max($max, (int) array_search(self::threat($level), $keys, true));
        }

        return $keys[$max];
    }

    private static function normalize(?string $value, array $vocabulary, array $synonyms): string
    {
        $value = mb_strtolower(trim((string) $value));

        if ($value === '') {
            return 'unknown';
        }
        if (array_key_exists($value, $vocabulary)) {
            return $value;
        }
        foreach ($synonyms as $code => $words) {
            foreach ($words as $word) {
                if (str_contains($value, $word)) {
                    return $code;
                }
            }
        }

        return 'unknown';
    }
}
