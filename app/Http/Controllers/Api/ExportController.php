<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\AggregateReport;
use App\Models\AiPrediction;
use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GeoZone;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\Species;
use App\Models\TrackingDevice;
use App\Models\User;
use App\Models\WeatherData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor data ke CSV (UTF-8 BOM, aman dibuka di Excel) dan GeoJSON.
 * Filter umum: start_date, end_date, plus filter spesifik per entitas.
 */
class ExportController extends ApiController
{
    private const MAX_ROWS = 50000;

    public function gpsCsv(Request $request)
    {
        $rows = $this->gpsQuery($request)->limit(self::MAX_ROWS)->cursor()->map(fn (GpsReading $r) => [
            $r->reading_id, $r->device_id, $r->device?->animal_id, $r->device?->animal?->name_animal,
            $r->latitude, $r->longitude, $r->altitude_meters, $r->accuracy_meters,
            $r->recorded_at?->toDateTimeString(), $r->checksum,
        ]);

        return $this->csv('gps_readings', ['reading_id', 'device_id', 'animal_id', 'name_animal', 'latitude', 'longitude',
            'altitude_meters', 'accuracy_meters', 'recorded_at', 'checksum'], $rows);
    }

    public function gpsGeoJson(Request $request)
    {
        $features = $this->gpsQuery($request)->limit(self::MAX_ROWS)->get()->map(fn (GpsReading $r) => [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [(float) $r->longitude, (float) $r->latitude]],
            'properties' => [
                'reading_id' => $r->reading_id,
                'device_id' => $r->device_id,
                'animal_id' => $r->device?->animal_id,
                'name_animal' => $r->device?->animal?->name_animal,
                'recorded_at' => $r->recorded_at?->toIso8601String(),
                'altitude_meters' => $r->altitude_meters,
            ],
        ]);

        return $this->geojson('gps_track', $features->all());
    }

    public function animalsCsv(Request $request)
    {
        $query = Animal::query()->with(['species', 'trackingDevice']);
        $this->filter($query, $request, ['species_id', 'gender']);
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $this->csv('animals', ['animal_id', 'name_animal', 'species_id', 'common_name', 'scientific_name', 'gender',
            'birth_date', 'is_active', 'device_id', 'device_model', 'distinctive_marks'],
            $query->cursor()->map(fn (Animal $a) => [
                $a->animal_id, $a->name_animal, $a->species_id, $a->species?->common_name, $a->species?->scientific_name,
                $a->gender, $a->birth_date?->toDateString(), $a->is_active ? 1 : 0,
                $a->trackingDevice?->device_id, $a->trackingDevice?->model, $a->distinctive_marks,
            ]));
    }

    public function speciesCsv()
    {
        return $this->csv('species', ['species_id', 'common_name', 'scientific_name', 'conservation_status', 'animal_count'],
            Species::query()->withCount('animals')->cursor()->map(fn (Species $s) => [
                $s->species_id, $s->common_name, $s->scientific_name, $s->conservation_status, $s->animals_count,
            ]));
    }

    public function devicesCsv(Request $request)
    {
        $query = TrackingDevice::query()->with(['animal', 'zone']);
        $this->filter($query, $request, ['device_type', 'device_condition', 'zone_id']);
        if ($request->boolean('battery_low')) {
            $query->where('battery_level', '<', 20);
        }

        return $this->csv('devices', ['device_id', 'device_type', 'model', 'serial_number', 'animal_id', 'name_animal',
            'zone_id', 'zone_name', 'latitude', 'longitude', 'installation_date', 'is_active', 'device_condition',
            'battery_level', 'signal_strength', 'last_recorded_at'],
            $query->cursor()->map(fn (TrackingDevice $d) => [
                $d->device_id, $d->device_type, $d->model, $d->serial_number, $d->animal_id, $d->animal?->name_animal,
                $d->zone_id, $d->zone?->zone_name, $d->latitude, $d->longitude, $d->installation_date?->toDateString(),
                $d->is_active ? 1 : 0, $d->device_condition, $d->battery_level, $d->signal_strength,
                $d->last_recorded_at?->toDateTimeString(),
            ]));
    }

    public function incidentsCsv(Request $request)
    {
        $query = IncidentReport::query()->with(['reporter', 'animal', 'species', 'zone']);
        $this->filter($query, $request, ['incident_type', 'status', 'sync_status', 'source', 'zone_id', 'animal_id']);
        $this->dateRange($query, $request, 'timestamp');

        return $this->csv('incidents', ['report_id', 'incident_type', 'status', 'sync_status', 'source', 'timestamp',
            'latitude', 'longitude', 'location', 'zone_name', 'user_id', 'reporter', 'animal_id', 'name_animal',
            'species', 'description'],
            $query->orderByDesc('timestamp')->cursor()->map(fn (IncidentReport $i) => [
                $i->report_id, $i->incident_type, $i->status, $i->sync_status, $i->source, $i->timestamp?->toDateTimeString(),
                $i->latitude, $i->longitude, $i->location, $i->zone?->zone_name, $i->user_id, $i->reporter?->username,
                $i->animal_id, $i->animal?->name_animal, $i->species?->common_name, $i->description,
            ]));
    }

    public function weatherCsv(Request $request)
    {
        $query = WeatherData::query();
        $this->dateRange($query, $request, 'observed_at');

        return $this->csv('weather', ['weather_id', 'reading_id', 'latitude', 'longitude', 'observed_at', 'temperature_c',
            'rainfall_mm', 'humidity_percent', 'pressure_hpa', 'source'],
            $query->orderByDesc('observed_at')->limit(self::MAX_ROWS)->cursor()->map(fn (WeatherData $w) => [
                $w->weather_id, $w->reading_id, $w->latitude, $w->longitude, $w->observed_at?->toDateTimeString(),
                $w->temperature_c, $w->rainfall_mm, $w->humidity_percent, $w->pressure_hpa, $w->source,
            ]));
    }

    public function aiPredictionsCsv(Request $request)
    {
        $query = AiPrediction::query()->with(['media.zone', 'predictedSpecies', 'predictedAnimal', 'verifiedSpecies', 'verifier']);
        $this->filter($query, $request, ['processing_status', 'verification_status', 'analysis_type', 'threat_level']);
        $this->dateRange($query, $request, 'processed_at');

        return $this->csv('ai_predictions', ['prediction_id', 'media_id', 'analysis_type', 'media_type', 'device_id', 'zone_name',
            'captured_at', 'predicted_species', 'confidence', 'predicted_animal', 'animal_match_confidence', 'animal_match_method',
            'physical_condition', 'behavior', 'sound_category', 'sound_label', 'threat_level', 'processing_status',
            'verification_status', 'verified_species', 'verified_by', 'model_version', 'processed_at', 'ai_summary'],
            $query->orderByDesc('processed_at')->limit(self::MAX_ROWS)->cursor()->map(fn (AiPrediction $p) => [
                $p->prediction_id, $p->media_id, $p->analysis_type, $p->media?->media_type, $p->media?->device_id,
                $p->media?->zone?->zone_name, $p->media?->captured_at?->toDateTimeString(), $p->predictedSpecies?->common_name,
                $p->confidence, $p->predictedAnimal?->name_animal, $p->animal_match_confidence, $p->animal_match_method,
                $p->physical_condition, $p->behavior, $p->sound_category, $p->sound_label, $p->threat_level,
                $p->processing_status, $p->verification_status, $p->verifiedSpecies?->common_name, $p->verifier?->username,
                $p->model_version, $p->processed_at?->toDateTimeString(), $p->ai_summary,
            ]));
    }

    public function zonesCsv()
    {
        return $this->csv('geo_zones', ['zone_id', 'zone_name', 'zone_type', 'center_lat', 'center_lon', 'radius_km', 'has_polygon', 'created_at'],
            GeoZone::query()->cursor()->map(fn (GeoZone $z) => [
                $z->zone_id, $z->zone_name, $z->zone_type, $z->center_lat, $z->center_lon, $z->radius_km,
                $z->polygon_geojson ? 1 : 0, $z->created_at?->toDateTimeString(),
            ]));
    }

    public function zonesGeoJson()
    {
        $features = GeoZone::query()->get()->map(function (GeoZone $z) {
            $geometry = $z->polygon_geojson
                ? (($z->polygon_geojson['type'] ?? null) === 'Feature' ? $z->polygon_geojson['geometry'] : $z->polygon_geojson)
                : ['type' => 'Point', 'coordinates' => [(float) $z->center_lon, (float) $z->center_lat]];

            return [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => [
                    'zone_id' => $z->zone_id,
                    'zone_name' => $z->zone_name,
                    'zone_type' => $z->zone_type,
                    'radius_km' => $z->radius_km,
                ],
            ];
        });

        return $this->geojson('geo_zones', $features->all());
    }

    public function zoneVisitsCsv(Request $request)
    {
        $query = AnimalGeoZone::query()->with(['animal', 'zone']);
        $this->filter($query, $request, ['animal_id', 'zone_id']);
        $this->dateRange($query, $request, 'entered_at');

        return $this->csv('zone_visits', ['visit_id', 'animal_id', 'name_animal', 'zone_id', 'zone_name', 'zone_type',
            'entry_reading_id', 'entered_at', 'exited_at', 'duration_minutes'],
            $query->orderByDesc('entered_at')->cursor()->map(fn (AnimalGeoZone $v) => [
                $v->visit_id, $v->animal_id, $v->animal?->name_animal, $v->zone_id, $v->zone?->zone_name, $v->zone?->zone_type,
                $v->entry_reading_id, $v->entered_at?->toDateTimeString(), $v->exited_at?->toDateTimeString(),
                $v->exited_at ? (int) $v->entered_at->diffInMinutes($v->exited_at) : null,
            ]));
    }

    public function usersCsv()
    {
        return $this->csv('users', ['user_id', 'role_id', 'username', 'name_role', 'full_name', 'email', 'address', 'phone', 'status', 'created_at'],
            User::query()->with('role')->cursor()->map(fn (User $u) => [
                $u->user_id, $u->role_id, $u->username, $u->role?->name_role, $u->full_name, $u->email,
                $u->address, $u->phone, $u->status, $u->created_at?->toDateTimeString(),
            ]));
    }

    public function activityLogsCsv(Request $request)
    {
        $query = ActivityLog::query()->with('user');
        $this->filter($query, $request, ['user_id', 'action']);
        $this->dateRange($query, $request, 'timestamp');

        return $this->csv('activity_logs', ['log_id', 'user_id', 'username', 'action', 'timestamp', 'details'],
            $query->orderByDesc('timestamp')->limit(self::MAX_ROWS)->cursor()->map(fn (ActivityLog $l) => [
                $l->log_id, $l->user_id, $l->user?->username, $l->action, $l->timestamp?->toDateTimeString(), $l->details,
            ]));
    }

    public function aggregateReport(int $id)
    {
        $report = AggregateReport::with('generator')->findOrFail($id);
        $data = $report->report_data ?? [];
        $rows = [
            ['report_id', $report->report_id],
            ['report_type', $report->report_type],
            ['period', $report->period_start?->toDateString() . ' s/d ' . $report->period_end?->toDateString()],
            ['generated_by', $report->generator?->username],
            ['generated_at', $report->generated_at?->toDateTimeString()],
        ];
        foreach ($data['statistics'] ?? [] as $key => $value) {
            $rows[] = [$key, $value];
        }

        return $this->csv("aggregate_report_{$id}", ['field', 'value'], $rows);
    }

    // ------------------------------------------------------------ helpers

    private function gpsQuery(Request $request)
    {
        $query = GpsReading::query()->with('device.animal');

        if ($request->filled('animal_id')) {
            $query->whereHas('device', fn ($q) => $q->where('animal_id', $request->integer('animal_id')));
        }
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->integer('device_id'));
        }
        $this->dateRange($query, $request, 'recorded_at');

        return $query->orderByDesc('recorded_at');
    }

    private function filter($query, Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
    }

    private function dateRange($query, Request $request, string $column): void
    {
        if ($request->filled('start_date')) {
            $query->where($column, '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where($column, '<=', $request->date('end_date')->endOfDay());
        }
    }

    private function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        $filename = $name . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_bool($v) ? (int) $v : (is_array($v) ? json_encode($v) : $v), $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function geojson(string $name, array $features)
    {
        return response()->json(['type' => 'FeatureCollection', 'features' => $features])
            ->header('Content-Disposition', 'attachment; filename="' . $name . '_' . now()->format('Ymd_His') . '.geojson"')
            ->header('Content-Type', 'application/geo+json');
    }
}
