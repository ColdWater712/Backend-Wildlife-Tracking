<?php

namespace App\Services\Ai;

/**
 * Ekstraksi fitur akustik sederhana dari file WAV PCM (8/16/24/32-bit).
 * Fitur dipakai sebagai petunjuk klasifikasi (tembakan = impuls tajam,
 * gergaji mesin = suara keras berkelanjutan, dsb.) ketika model audio khusus tidak tersedia.
 */
class AudioFeatureExtractor
{
    private const FRAME_MS = 50;
    private const MAX_SECONDS = 120;

    public function extract(string $path): ?array
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return null;
        }

        try {
            $header = fread($handle, 12);
            if (strlen($header) < 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WAVE') {
                return null;
            }

            $format = null;
            while (! feof($handle)) {
                $chunkHeader = fread($handle, 8);
                if (strlen($chunkHeader) < 8) {
                    return null;
                }
                ['id' => $id, 'size' => $size] = unpack('a4id/Vsize', $chunkHeader);

                if ($id === 'fmt ') {
                    $format = unpack('vaudioFormat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample', fread($handle, 16));
                    if ($size > 16) {
                        fseek($handle, $size - 16 + ($size % 2), SEEK_CUR);
                    }
                } elseif ($id === 'data') {
                    return $format ? $this->analyse($handle, $format, $size) : null;
                } else {
                    fseek($handle, $size + ($size % 2), SEEK_CUR);
                }
            }

            return null;
        } finally {
            fclose($handle);
        }
    }

    private function analyse($handle, array $format, int $dataSize): ?array
    {
        $channels = max(1, (int) $format['channels']);
        $bits = (int) $format['bitsPerSample'];
        $rate = (int) $format['sampleRate'];
        $bytesPerSample = intdiv($bits, 8);

        if (! in_array($bits, [8, 16, 24, 32], true) || $rate <= 0 || (int) $format['audioFormat'] !== 1) {
            return null;
        }

        $frameBytes = $bytesPerSample * $channels;
        $totalFrames = intdiv($dataSize, $frameBytes);
        $duration = $totalFrames / $rate;
        $maxFrames = min($totalFrames, self::MAX_SECONDS * $rate);
        // Decimasi agar tetap ringan di PHP: analisa ±8000 sampel/detik.
        $step = max(1, intdiv($rate, 8000));
        $windowSamples = max(1, intdiv((int) ($rate * self::FRAME_MS / 1000), $step));
        $fullScale = 2 ** ($bits - 1);

        $rms = [];
        $zcrs = [];
        $peak = 0.0;
        $sumSq = 0.0;
        $count = 0;
        $windowSq = 0.0;
        $windowN = 0;
        $windowZc = 0;
        $prev = 0.0;

        $read = 0;
        $chunkFrames = 4096;
        while ($read < $maxFrames) {
            $n = min($chunkFrames, $maxFrames - $read);
            $buffer = fread($handle, $n * $frameBytes);
            if ($buffer === false || $buffer === '') {
                break;
            }
            $got = intdiv(strlen($buffer), $frameBytes);

            for ($i = 0; $i < $got; $i += $step) {
                $sample = $this->sample($buffer, $i * $frameBytes, $bits) / $fullScale;
                $abs = abs($sample);
                $peak = max($peak, $abs);
                $sumSq += $sample * $sample;
                $count++;
                $windowSq += $sample * $sample;
                $windowN++;
                if (($sample >= 0) !== ($prev >= 0)) {
                    $windowZc++;
                }
                $prev = $sample;

                if ($windowN >= $windowSamples) {
                    $rms[] = sqrt($windowSq / $windowN);
                    $zcrs[] = $windowZc / $windowN;
                    $windowSq = 0.0;
                    $windowN = 0;
                    $windowZc = 0;
                }
            }
            $read += $got;
        }

        if ($count === 0 || $rms === []) {
            return null;
        }

        $sorted = $rms;
        sort($sorted);
        $median = $sorted[intdiv(count($sorted), 2)] ?: 1e-6;
        $rmsMean = sqrt($sumSq / $count);

        // Peristiwa impulsif: lonjakan energi > 8x median yang lalu cepat turun (≤ 150 ms).
        $impulses = 0;
        $loudFrames = 0;
        $frames = count($rms);
        for ($i = 0; $i < $frames; $i++) {
            // Frame "keras" absolut (> ~-26 dBFS): suara mesin/gergaji terdengar konstan keras.
            if ($rms[$i] > 0.05) {
                $loudFrames++;
            }
            if ($rms[$i] > 8 * $median && $rms[$i] > 0.05 && ($i === 0 || $rms[$i - 1] < 0.5 * $rms[$i])) {
                $decay = $rms[$i + 3] ?? 0;
                if ($decay < 0.5 * $rms[$i]) {
                    $impulses++;
                }
            }
        }

        $zcrMean = array_sum($zcrs) / count($zcrs);
        $effectiveRate = $rate / $step;

        return [
            'format' => 'wav',
            'sample_rate' => $rate,
            'channels' => $channels,
            'duration_seconds' => round($duration, 2),
            'analysed_seconds' => round(min($duration, self::MAX_SECONDS), 2),
            'rms_mean' => round($rmsMean, 5),
            'rms_median' => round($median, 5),
            'peak' => round($peak, 5),
            'crest_factor' => round($peak / max($rmsMean, 1e-6), 2),
            'zero_crossing_rate' => round($zcrMean, 4),
            'approx_dominant_hz' => (int) round($zcrMean * $effectiveRate / 2),
            'impulsive_events' => $impulses,
            'loud_frame_ratio' => round($loudFrames / $frames, 3),
            'is_silent' => $peak < 0.01,
        ];
    }

    private function sample(string $buffer, int $offset, int $bits): float
    {
        return match ($bits) {
            8 => ord($buffer[$offset]) - 128,
            16 => unpack('s', $buffer, $offset)[1],
            24 => (($v = ord($buffer[$offset]) | (ord($buffer[$offset + 1]) << 8) | (ord($buffer[$offset + 2]) << 16)) & 0x800000)
                ? $v - 0x1000000 : $v,
            32 => unpack('l', $buffer, $offset)[1],
        };
    }
}
