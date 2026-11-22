<?php

namespace App\Services;

use App\Models\AiPrediction;
use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\Species;
use App\Models\TrackingDevice;
use App\Models\WeatherData;
use Carbon\Carbon;

/** Agregasi statistik monitoring untuk dashboard, laporan agregat, dan ringkasan AI. */
class MonitoringStatsService
{
    public function collect(string $start, string $end): array
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->endOfDay();

        $incidents = IncidentReport::query()->whereBetween('timestamp', [$from, $to])->get(['incident_type', 'status', 'source']);
        $weather = WeatherData::query()->whereBetween('observed_at', [$from, $to]);
        $predictions = AiPrediction::query()->whereBetween('processed_at', [$from, $to])
            ->get(['analysis_type', 'processing_status', 'verification_status', 'confidence', 'threat_level', 'physical_condition', 'behavior', 'sound_category', 'predicted_animal_id']);
        $completed = $predictions->where('processing_status', 'completed');

        $species = Species::query()->withCount('animals')->get();

        return [
            'statistics' => [
                'total_animals' => Animal::count(),
                'active_animals' => Animal::where('is_active', true)->count(),
                'total_species' => $species->count(),
                'total_readings' => GpsReading::whereBetween('recorded_at', [$from, $to])->count(),
                'total_incidents' => $incidents->count(),
                'critical_incidents' => $incidents->whereIn('incident_type', ['konflik', 'perambahan', 'perburuan', 'kebakaran_hutan'])->count(),
                'ai_generated_incidents' => $incidents->where('source', '!=', 'manual')->count(),
                'total_devices' => TrackingDevice::count(),
                'active_devices' => TrackingDevice::where('is_active', true)->count(),
                'gps_devices' => TrackingDevice::where('device_type', 'gps')->count(),
                'camera_devices' => TrackingDevice::where('device_type', 'camera')->count(),
                'audio_devices' => TrackingDevice::where('device_type', 'audio')->count(),
                'low_battery_devices' => TrackingDevice::where('battery_level', '<', DeviceHealthService::LOW_BATTERY)->count(),
                'zone_entries' => AnimalGeoZone::whereBetween('entered_at', [$from, $to])->count(),
                'total_predictions' => $predictions->count(),
                'total_verified_predictions' => $predictions->where('verification_status', 'confirmed')->count(),
                'average_confidence' => $completed->isNotEmpty() ? round((float) $completed->avg('confidence'), 4) : null,
                'identified_individuals' => $completed->whereNotNull('predicted_animal_id')->pluck('predicted_animal_id')->unique()->count(),
                'threat_detections' => $completed->filter(fn ($p) => AiPrediction::threatRank($p->threat_level) >= 2)->count(),
                'avg_temperature' => round((float) ((clone $weather)->avg('temperature_c') ?? 0), 1),
                'avg_humidity' => round((float) ((clone $weather)->avg('humidity_percent') ?? 0), 1),
                'total_rainfall' => round((float) ((clone $weather)->sum('rainfall_mm') ?? 0), 1),
            ],
            'species_breakdown' => $species->map(fn (Species $s) => [
                'species_id' => $s->species_id,
                'name' => $s->common_name,
                'common_name' => $s->common_name,
                'scientific_name' => $s->scientific_name,
                'count' => $s->animals_count,
                'status' => $s->conservation_status,
            ])->values()->all(),
            'incidents_by_type' => $incidents->groupBy('incident_type')->map->count()->all(),
            'ai_insights' => [
                'threats_by_level' => $completed->groupBy('threat_level')->map->count()->all(),
                'sounds_by_category' => $completed->where('analysis_type', 'audio')->groupBy('sound_category')->map->count()->all(),
                'physical_conditions' => $completed->whereNotNull('physical_condition')->groupBy('physical_condition')->map->count()->all(),
                'behaviors' => $completed->whereNotNull('behavior')->groupBy('behavior')->map->count()->all(),
            ],
        ];
    }
}
