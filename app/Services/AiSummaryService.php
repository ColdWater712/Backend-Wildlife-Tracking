<?php

namespace App\Services;

use App\Services\Ai\GroqClient;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    public function __construct(
        private GroqClient $groq,
        private MonitoringStatsService $stats,
    ) {}

    public function generateSummary(string $start, string $end): array
    {
        $data = $this->stats->collect($start, $end);
        $summary = null;
        $usedAi = false;

        if ($this->groq->isConfigured()) {
            try {
                $summary = $this->groq->chatText(
                    'Buat ringkasan eksekutif profesional (maks. 5 kalimat, bahasa Indonesia, tanpa markdown) '
                    . 'dari data monitoring satwa TN Gunung Leuser berikut. Soroti jumlah satwa, insiden kritis, '
                    . 'deteksi ancaman AI (tembakan/gergaji/perburuan), kondisi kesehatan satwa, dan rekomendasi tindakan. Data: '
                    . json_encode($data, JSON_UNESCAPED_UNICODE),
                );
                $usedAi = $summary !== '';
            } catch (\Throwable $e) {
                Log::warning('Ringkasan AI gagal, memakai template: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'period' => ['start' => $start, 'end' => $end],
            'summary' => $usedAi ? $summary : $this->templateSummary($data, $start, $end),
            'used_ai' => $usedAi,
            'statistics' => $data['statistics'],
            'species_breakdown' => $data['species_breakdown'],
            'incidents_by_type' => $data['incidents_by_type'],
            'ai_insights' => $data['ai_insights'],
            'recommendations' => $this->recommendations($data),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function templateSummary(array $data, string $start, string $end): string
    {
        $s = $data['statistics'];

        return "Periode {$start} s/d {$end}: {$s['active_animals']} dari {$s['total_animals']} satwa aktif dipantau, "
            . "{$s['total_readings']} pembacaan GPS, {$s['total_incidents']} insiden ({$s['critical_incidents']} kritis). "
            . "AI memproses {$s['total_predictions']} media dengan {$s['threat_detections']} deteksi ancaman. "
            . "Rata-rata suhu {$s['avg_temperature']}°C, curah hujan total {$s['total_rainfall']} mm.";
    }

    private function recommendations(array $data): array
    {
        $s = $data['statistics'];
        $ai = $data['ai_insights'];
        $recs = [];

        if ($s['threat_detections'] > 0) {
            $recs[] = ['priority' => 'critical', 'icon' => '🚨', 'title' => 'Ancaman Terdeteksi AI',
                'message' => "{$s['threat_detections']} deteksi ancaman (tembakan/gergaji/aktivitas manusia). Kirim tim patroli ke lokasi terkait."];
        }
        if ($s['critical_incidents'] > 0) {
            $recs[] = ['priority' => 'high', 'icon' => '⚠️', 'title' => 'Insiden Kritis',
                'message' => "Ditemukan {$s['critical_incidents']} insiden kritis. Perlu investigasi lapangan."];
        }
        $unwell = ($ai['physical_conditions']['injured'] ?? 0) + ($ai['physical_conditions']['sick'] ?? 0) + ($ai['physical_conditions']['emaciated'] ?? 0);
        if ($unwell > 0) {
            $recs[] = ['priority' => 'high', 'icon' => '🩺', 'title' => 'Kesehatan Satwa',
                'message' => "{$unwell} pengamatan satwa terluka/sakit/kurus. Koordinasikan pemeriksaan dokter hewan."];
        }
        if ($s['low_battery_devices'] > 0) {
            $recs[] = ['priority' => 'medium', 'icon' => '🔋', 'title' => 'Baterai Rendah',
                'message' => "{$s['low_battery_devices']} perangkat baterainya < 20%. Jadwalkan penggantian."];
        }
        if ($s['avg_temperature'] > 30) {
            $recs[] = ['priority' => 'medium', 'icon' => '🌡️', 'title' => 'Suhu Tinggi',
                'message' => "Rata-rata suhu {$s['avg_temperature']}°C. Pantau risiko dehidrasi dan kebakaran."];
        }
        if ($recs === []) {
            $recs[] = ['priority' => 'low', 'icon' => '✅', 'title' => 'Kondisi Normal',
                'message' => 'Semua parameter monitoring normal. Lanjutkan pemantauan rutin.'];
        }

        return $recs;
    }
}
