<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use App\Models\WeatherData;
use App\Models\Species;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Generate AI Summary Report
     */
    public function generateSummary(array $options = []): array
    {
        $periodStart = $options['start_date'] ?? now()->subDays(7)->format('Y-m-d');
        $periodEnd   = $options['end_date']   ?? now()->format('Y-m-d');

        $data      = $this->collectMonitoringData($periodStart, $periodEnd);
        $narrative = $this->buildNarrative($data, $periodStart, $periodEnd);

        $summary = '';
        $usedAi  = false;

        try {
            $result  = $this->geminiService->summarizeText($narrative);
            $summary = $result['summary'] ?? '';
            $usedAi  = true;
            Log::info("Gemini Summary OK untuk periode {$periodStart} - {$periodEnd}");
        } catch (\Exception $e) {
            Log::warning('Gemini Summarization failed, pakai template fallback: ' . $e->getMessage());
            $summary = $this->buildTemplateSummary($data, $periodStart, $periodEnd);
        }

        return [
            'success'             => true,
            'period'              => ['start' => $periodStart, 'end' => $periodEnd],
            'summary'             => $summary,
            'used_ai'             => $usedAi,
            'statistics'          => $data['statistics'],
            'species_breakdown'   => $data['species_breakdown'],
            'incidents_by_type'   => $data['incidents_by_type'],
            'recommendations'     => $this->buildRecommendations($data),
            'generated_at'        => now()->toIso8601String(),
        ];
    }

    /**
     * Deteksi objek (dipanggil controller via summaryService)
     */
    public function detectObjects(string $imagePath, float $threshold = 0.5): array
    {
        try {
            $result = $this->geminiService->detectObjects($imagePath);
            return [
                'success'      => true,
                'model'        => $result['model'],
                'objects'      => $result['objects'],
                'object_count' => count($result['objects']),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini detect failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prediksi spesies (dipanggil controller via summaryService)
     */
    public function predictSpecies(string $imagePath, int $topK = 5): array
    {
        try {
            $result = $this->geminiService->classifyImage($imagePath);
            return [
                'success'        => true,
                'model'          => $result['model'],
                'predictions'    => $result['predictions'],
                'top_prediction' => $result['predictions'][0] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Gemini predict failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Health check
     */
    public function healthCheck(): array
    {
        try {
            return [
                'success'    => true,
                'ai_service' => $this->geminiService->healthCheck(),
                'timestamp'  => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'success'    => false,
                'ai_service' => ['status' => 'error', 'message' => $e->getMessage()],
                'timestamp'  => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Kumpulkan data monitoring (SUDAH DISesuaikan dengan skema wildlife_tracking)
     */
    protected function collectMonitoringData(string $start, string $end): array
    {
        $animals  = Animal::with('species')->get();
        $species  = Species::withCount('animals')->get();
        $readings = GpsReading::whereBetween('recorded_at', [$start, $end])->get();
        $incidents = IncidentReport::whereBetween('timestamp', [$start, $end])->get();
        $weather  = WeatherData::whereBetween('observed_at', [$start, $end])->get();
        $devices  = TrackingDevice::all();

        $activeAnimals     = Animal::where('is_active', true)->count();
        $lowBatteryDevices = TrackingDevice::where('battery_level', '<', 20)->count();
        $criticalIncidents = IncidentReport::whereIn('incident_type', ['konflik', 'perambahan'])
            ->whereBetween('timestamp', [$start, $end])->count();

        $avgTemp      = $weather->avg('temperature_c');
        $totalRain    = $weather->sum('rainfall_mm');
        $avgHumidity  = $weather->avg('humidity_percent');

        return [
            'statistics' => [
                'total_animals'      => $animals->count(),
                'active_animals'     => $activeAnimals,
                'total_species'      => $species->count(),
                'total_readings'     => $readings->count(),
                'total_incidents'    => $incidents->count(),
                'critical_incidents' => $criticalIncidents,
                'total_devices'      => $devices->count(),
                'low_battery_devices'=> $lowBatteryDevices,
                'avg_temperature'    => round($avgTemp ?? 0, 1),
                'avg_humidity'       => round($avgHumidity ?? 0, 1),
                'total_rainfall'     => round($totalRain ?? 0, 1),
            ],
            'species_breakdown' => $species->map(fn($s) => [
                'name'            => $s->common_name,
                'scientific_name' => $s->scientific_name,
                'count'           => $s->animals_count,
                'status'          => $s->conservation_status,
            ])->toArray(),
            'incidents_by_type' => $incidents->groupBy('incident_type')
                ->map(fn($group) => $group->count())->toArray(),
            'recent_incidents'  => $incidents->sortByDesc('timestamp')->take(5)->values(),
        ];
    }

    protected function buildNarrative(array $data, string $start, string $end): string
    {
        $stats = $data['statistics'];
        $speciesParts = [];
        foreach ($data['species_breakdown'] as $sp) {
            $speciesParts[] = "{$sp['name']} ({$sp['count']} individu, {$sp['status']})";
        }
        $incidentParts = [];
        foreach ($data['incidents_by_type'] as $type => $count) {
            $incidentParts[] = "{$type} ({$count})";
        }

        return "Periode {$start} hingga {$end} TN Gunung Leuser: " .
               "{$stats['active_animals']} satwa aktif dari {$stats['total_species']} spesies. " .
               "{$stats['total_readings']} pembacaan GPS, {$stats['total_incidents']} insiden ({$stats['critical_incidents']} kritis). " .
               "Spesies: " . implode(', ', $speciesParts) . ". " .
               "Insiden: " . implode(', ', $incidentParts) . ". " .
               "Cuaca: {$stats['avg_temperature']}°C, kelembapan {$stats['avg_humidity']}%, hujan {$stats['total_rainfall']}mm. " .
               "Perangkat: {$stats['total_devices']} total, {$stats['low_battery_devices']} baterai rendah.";
    }

    protected function buildTemplateSummary(array $data, string $start, string $end): string
    {
        $stats = $data['statistics'];
        return "RINGKASAN {$start} - {$end}: {$stats['active_animals']} satwa aktif, " .
               "{$stats['total_incidents']} insiden ({$stats['critical_incidents']} kritis). " .
               "Suhu {$stats['avg_temperature']}°C, hujan {$stats['total_rainfall']}mm.";
    }

    protected function buildRecommendations(array $data): array
    {
        $stats = $data['statistics'];
        $recs  = [];

        if ($stats['low_battery_devices'] > 0) {
            $recs[] = ['priority' => 'high', 'icon' => '🔋', 'title' => 'Baterai Rendah',
                       'message' => "{$stats['low_battery_devices']} perangkat <20%. Segera ganti baterai."];
        }
        if ($stats['critical_incidents'] > 0) {
            $recs[] = ['priority' => 'critical', 'icon' => '🚨', 'title' => 'Insiden Kritis',
                       'message' => "Ditemukan {$stats['critical_incidents']} insiden kritis. Perlu investigasi lapangan."];
        }
        if ($stats['avg_temperature'] > 30) {
            $recs[] = ['priority' => 'medium', 'icon' => '🌡️', 'title' => 'Suhu Tinggi',
                       'message' => "Suhu {$stats['avg_temperature']}°C. Pantau risiko dehidrasi satwa."];
        }
        if (empty($recs)) {
            $recs[] = ['priority' => 'low', 'icon' => '✅', 'title' => 'Kondisi Normal',
                       'message' => 'Semua parameter monitoring normal. Lanjutkan pemantauan rutin.'];
        }

        return $recs;
    }
}