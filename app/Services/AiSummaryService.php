<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use App\Models\WeatherData;
use App\Models\Species;
use App\Services\HuggingFaceService;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    protected HuggingFaceService $hfService;

    public function __construct(HuggingFaceService $hfService)
    {
        $this->hfService = $hfService;
    }

    public function generateSummary(array $options = []): array
    {
        $periodStart = $options['start_date'] ?? now()->subDays(7)->format('Y-m-d');
        $periodEnd = $options['end_date'] ?? now()->format('Y-m-d');

        $data = $this->collectMonitoringData($periodStart, $periodEnd);
        $narrative = $this->buildNarrative($data, $periodStart, $periodEnd);

        $summary = '';
        $usedAi = false;

        try {
            $result = $this->hfService->summarizeText($narrative, null, 200);
            $summary = $result['summary'] ?? '';
            $usedAi = true;
            Log::info("AI Summary generated with HF for period {$periodStart} - {$periodEnd}");
        } catch (\Exception $e) {
            Log::warning("HF Summarization failed, using template fallback: " . $e->getMessage());
            $summary = $this->buildTemplateSummary($data, $periodStart, $periodEnd);
        }

        $recommendations = $this->buildRecommendations($data);

        return [
            'success' => true,
            'period' => ['start' => $periodStart, 'end' => $periodEnd],
            'summary' => $summary,
            'used_ai' => $usedAi,
            'statistics' => $data['statistics'],
            'species_breakdown' => $data['species_breakdown'],
            'incidents_by_type' => $data['incidents_by_type'],
            'recommendations' => $recommendations,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function predictSpecies(string $imagePath, int $topK = 5): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $result = $this->hfService->classifyImage($imagePath);
            return [
                'success' => true,
                'model' => $result['model'],
                'predictions' => $result['predictions'],
                'top_prediction' => $result['predictions'][0] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("AI Species prediction failed: " . $e->getMessage());
            return [
                'success' => false,
                'model' => 'Fallback-Mock',
                'message' => 'AI Service tidak tersedia. Menggunakan data mock.',
                'predictions' => [
                    ['label' => 'Orangutan Sumatra', 'score' => 0.85],
                    ['label' => 'Harimau Sumatra', 'score' => 0.10],
                ],
                'top_prediction' => ['label' => 'Orangutan Sumatra', 'score' => 0.85],
            ];
        }
    }

    public function detectObjects(string $imagePath, float $threshold = 0.5): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan: {$imagePath}");
        }

        try {
            $result = $this->hfService->detectObjects($imagePath);
            return [
                'success' => true,
                'model' => $result['model'],
                'objects' => $result['objects'],
                'object_count' => count($result['objects']),
            ];
        } catch (\Exception $e) {
            Log::error("AI Object detection failed: " . $e->getMessage());
            return [
                'success' => false,
                'model' => 'Fallback-Mock',
                'message' => 'AI Service tidak tersedia. Menggunakan data mock.',
                'objects' => [['box' => ['xmin' => 50, 'ymin' => 30, 'xmax' => 200, 'ymax' => 180], 'label' => 'animal', 'score' => 0.92]],
                'object_count' => 1,
            ];
        }
    }

    public function healthCheck(): array
    {
        try {
            return [
                'success' => true,
                'ai_service' => $this->hfService->healthCheck(),
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'ai_service' => ['status' => 'error', 'message' => $e->getMessage()],
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * ✅ PERBAIKAN: TIDAK lagi menggunakan created_at pada animals/species
     */
    protected function collectMonitoringData(string $start, string $end): array
    {
        // 1. Animals & Species: TIDAK punya kolom created_at, ambil semua data
        $animals = Animal::with('species')->get();
        $species = Species::withCount('animals')->get();

        // 2. Data yang PUNYA kolom waktu (sesuai skema database wildlife_tracking)
        $readings = GpsReading::whereBetween('recorded_at', [$start, $end])->get();
        $incidents = IncidentReport::whereBetween('timestamp', [$start, $end])->get();
        $weather = WeatherData::whereBetween('observed_at', [$start, $end])->get();
        $devices = TrackingDevice::all();

        $activeAnimals = Animal::where('is_active', true)->count();
        $lowBatteryDevices = TrackingDevice::where('battery_level', '<', 20)->count();
        $criticalIncidents = IncidentReport::whereIn('incident_type', ['konflik', 'perambahan'])
            ->whereBetween('timestamp', [$start, $end])
            ->count();

        $avgTemp = $weather->avg('temperature_c');
        $totalRain = $weather->sum('rainfall_mm');
        $avgHumidity = $weather->avg('humidity_percent');

        return [
            'statistics' => [
                'total_animals' => $animals->count(),
                'active_animals' => $activeAnimals,
                'total_species' => $species->count(),
                'total_readings' => $readings->count(),
                'total_incidents' => $incidents->count(),
                'critical_incidents' => $criticalIncidents,
                'total_devices' => $devices->count(),
                'low_battery_devices' => $lowBatteryDevices,
                'avg_temperature' => round($avgTemp ?? 0, 1),
                'avg_humidity' => round($avgHumidity ?? 0, 1),
                'total_rainfall' => round($totalRain ?? 0, 1),
            ],
            'species_breakdown' => $species->map(fn($s) => [
                'name' => $s->common_name,
                'scientific_name' => $s->scientific_name,
                'count' => $s->animals_count,
                'status' => $s->conservation_status,
            ])->toArray(),
            'incidents_by_type' => $incidents->groupBy('incident_type')->map(fn($group) => $group->count())->toArray(),
            'recent_incidents' => $incidents->sortByDesc('timestamp')->take(5)->values(),
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

        return "Selama periode {$start} hingga {$end}, sistem memantau {$stats['active_animals']} satwa aktif dari {$stats['total_species']} spesies di Taman Nasional Gunung Leuser. " .
               "Total {$stats['total_readings']} pembacaan GPS tercatat dengan {$stats['total_incidents']} insiden dilaporkan, di mana {$stats['critical_incidents']} insiden bersifat kritis. " .
               "Distribusi spesies: " . implode(', ', $speciesParts) . ". " .
               "Jenis insiden: " . implode(', ', $incidentParts) . ". " .
               "Cuaca rata-rata: suhu {$stats['avg_temperature']}°C, kelembapan {$stats['avg_humidity']}%, curah hujan {$stats['total_rainfall']}mm.";
    }

    protected function buildTemplateSummary(array $data, string $start, string $end): string
    {
        $stats = $data['statistics'];
        return "RINGKASAN PERIODE {$start} - {$end}: Memantau {$stats['active_animals']} satwa aktif dari {$stats['total_species']} spesies. " .
               "Tercatat {$stats['total_readings']} pembacaan GPS dan {$stats['total_incidents']} insiden ({$stats['critical_incidents']} kritis). " .
               "Suhu rata-rata {$stats['avg_temperature']}°C, curah hujan {$stats['total_rainfall']}mm.";
    }

    protected function buildRecommendations(array $data): array
    {
        $stats = $data['statistics'];
        $recommendations = [];

        if ($stats['low_battery_devices'] > 0) {
            $recommendations[] = ['priority' => 'high', 'icon' => '🔋', 'title' => 'Perangkat Baterai Rendah', 'message' => "{$stats['low_battery_devices']} perangkat memiliki baterai <20%. Segera ganti baterai."];
        }
        if ($stats['critical_incidents'] > 0) {
            $recommendations[] = ['priority' => 'critical', 'icon' => '🚨', 'title' => 'Insiden Kritis', 'message' => "Ditemukan {$stats['critical_incidents']} insiden kritis. Perlu investigasi lapangan segera."];
        }
        if ($stats['avg_temperature'] > 30) {
            $recommendations[] = ['priority' => 'medium', 'icon' => '🌡️', 'title' => 'Suhu Tinggi', 'message' => "Suhu rata-rata tinggi ({$stats['avg_temperature']}°C). Pantau risiko dehidrasi satwa."];
        }
        if (empty($recommendations)) {
            $recommendations[] = ['priority' => 'low', 'icon' => '✅', 'title' => 'Kondisi Normal', 'message' => "Semua parameter monitoring dalam kondisi normal."];
        }

        return $recommendations;
    }
}