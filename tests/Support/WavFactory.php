<?php

namespace Tests\Support;

/** Membuat WAV PCM 16-bit mono sintetis untuk pengujian. */
final class WavFactory
{
    private const RATE = 16000;

    public static function sustainedTone(float $seconds, int $hz, float $amplitude = 0.6): string
    {
        $samples = [];
        $n = (int) ($seconds * self::RATE);
        for ($i = 0; $i < $n; $i++) {
            $samples[] = (int) ($amplitude * 32767 * sin(2 * M_PI * $hz * $i / self::RATE));
        }

        return self::encode($samples);
    }

    /** Latar sunyi dengan satu lonjakan tajam ±30 ms di tengah (menyerupai tembakan). */
    public static function impulse(float $seconds): string
    {
        $samples = [];
        $n = (int) ($seconds * self::RATE);
        $start = intdiv($n, 2);
        for ($i = 0; $i < $n; $i++) {
            $noise = random_int(-150, 150);
            $t = $i - $start;
            $burst = ($t >= 0 && $t < 480) ? (int) (30000 * exp(-$t / 120) * (random_int(0, 1) ? 1 : -1)) : 0;
            $samples[] = max(-32768, min(32767, $noise + $burst));
        }

        return self::encode($samples);
    }

    private static function encode(array $samples): string
    {
        $data = pack('s*', ...$samples);

        return 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVE'
            . 'fmt ' . pack('VvvVVvv', 16, 1, 1, self::RATE, self::RATE * 2, 2, 16)
            . 'data' . pack('V', strlen($data)) . $data;
    }
}
