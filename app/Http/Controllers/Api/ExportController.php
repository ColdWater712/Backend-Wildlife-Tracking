<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Animal;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use App\Models\Species;
use App\Models\WeatherData;
use App\Models\AiPrediction;
use App\Models\AggregateReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    use ApiResponse;

    public function exportGpsCsv(Request $request)
    {
        $query = GpsReading::with(['animal', 'device', 'weather']);

        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('device_id')) $query->where('device_id', $request->device_id);
        if ($request->filled('start_date')) $query->where('recorded_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('recorded_at', '<=', $request->end_date);

        $readings = $query->orderBy('recorded_at', 'desc')->limit(10000)->get();

        $csv = "reading_id,animal_id,animal_name,device_id,latitude,longitude,altitude,recorded_at,battery_level,signal_strength\n";
        foreach ($readings as $r) {
            $csv .= "{$r->reading_id},{$r->animal_id},\"{$r->animal->name}\",{$r->device_id},{$r->latitude},{$r->longitude},{$r->altitude},{$r->recorded_at},{$r->battery_level},{$r->signal_strength}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="gps_readings_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportGpsGeoJson(Request $request)
    {
        $query = GpsReading::with(['animal', 'device']);

        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('device_id')) $query->where('device_id', $request->device_id);

        $readings = $query->orderBy('recorded_at', 'desc')->limit(5000)->get();

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $readings->map(fn($r) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $r->longitude, (float) $r->latitude],
                ],
                'properties' => [
                    'reading_id' => $r->reading_id,
                    'animal_name' => $r->animal->name ?? null,
                    'device_id' => $r->device_id,
                    'recorded_at' => $r->recorded_at,
                    'altitude' => $r->altitude,
                ],
            ])->toArray(),
        ];

        return response()->json($geojson)
            ->header('Content-Disposition', 'attachment; filename="gps_track_' . now()->format('Ymd_His') . '.geojson"');
    }

    public function exportAnimalsCsv(Request $request)
    {
        $query = Animal::with(['species', 'device']);

        if ($request->filled('species_id')) $query->where('species_id', $request->species_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('gender')) $query->where('gender', $request->gender);

        $animals = $query->get();

        $csv = "animal_id,name,species,scientific_name,gender,birth_date,is_active,device_id,device_serial\n";
        foreach ($animals as $a) {
            $csv .= "{$a->animal_id},\"{$a->name}\",\"{$a->species->common_name}\",\"{$a->species->scientific_name}\",{$a->gender},{$a->birth_date},{$a->is_active},{$a->device_id},\"{$a->device->serial_number}\"\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="animals_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportIncidentsCsv(Request $request)
    {
        $query = IncidentReport::with(['reporter', 'animal', 'species']);

        if ($request->filled('incident_type')) $query->where('incident_type', $request->incident_type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('start_date')) $query->where('timestamp', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('timestamp', '<=', $request->end_date);

        $incidents = $query->orderBy('timestamp', 'desc')->get();

        $csv = "incident_id,type,status,timestamp,latitude,longitude,description,reporter,animal,species\n";
        foreach ($incidents as $i) {
            $csv .= "{$i->incident_id},\"{$i->incident_type}\",\"{$i->status}\",{$i->timestamp},{$i->latitude},{$i->longitude},\"{$i->description}\",\"{$i->reporter->username}\",\"{$i->animal->name}\",\"{$i->species->common_name}\"\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="incidents_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportDevicesCsv(Request $request)
    {
        $query = TrackingDevice::with(['animal', 'assignedBy']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('battery_low')) $query->where('battery_level', '<', 20);

        $devices = $query->get();

        $csv = "device_id,serial_number,model,status,battery_level,assigned_animal,assigned_at,last_sync\n";
        foreach ($devices as $d) {
            $csv .= "{$d->device_id},\"{$d->serial_number}\",\"{$d->model}\",\"{$d->status}\",{$d->battery_level},\"{$d->animal->name}\",{$d->assigned_at},{$d->last_sync_at}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="devices_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportSpeciesCsv(Request $request)
    {
        $species = Species::withCount('animals')->get();

        $csv = "species_id,common_name,scientific_name,conservation_status,animal_count\n";
        foreach ($species as $s) {
            $csv .= "{$s->species_id},\"{$s->common_name}\",\"{$s->scientific_name}\",\"{$s->conservation_status}\",{$s->animals_count}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="species_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportWeatherCsv(Request $request)
    {
        $query = WeatherData::orderBy('observed_at', 'desc');

        if ($request->filled('start_date')) $query->where('observed_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('observed_at', '<=', $request->end_date);

        $weather = $query->limit(5000)->get();

        $csv = "weather_id,observed_at,temperature_c,humidity_percent,rainfall_mm,wind_speed,pressure\n";
        foreach ($weather as $w) {
            $csv .= "{$w->weather_id},{$w->observed_at},{$w->temperature_c},{$w->humidity_percent},{$w->rainfall_mm},{$w->wind_speed},{$w->pressure}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="weather_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportAiPredictionsCsv(Request $request)
    {
        $query = AiPrediction::with(['media', 'predictedSpecies', 'verifiedSpecies', 'verifier']);

        if ($request->filled('processing_status')) $query->where('processing_status', $request->processing_status);
        if ($request->filled('verification_status')) $query->where('verification_status', $request->verification_status);

        $predictions = $query->orderBy('processed_at', 'desc')->limit(5000)->get();

        $csv = "prediction_id,media_id,predicted_species,confidence,model_version,status,verification_status,verified_by,processed_at\n";
        foreach ($predictions as $p) {
            $csv .= "{$p->prediction_id},{$p->media_id},\"{$p->predictedSpecies->common_name}\",{$p->confidence},\"{$p->model_version}\",\"{$p->processing_status}\",\"{$p->verification_status}\",\"{$p->verifier->username}\",{$p->processed_at}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="ai_predictions_' . now()->format('Ymd_His') . '.csv"');
    }

    public function exportAggregateReport($id)
    {
        $report = AggregateReport::with(['generatedBy'])->findOrFail($id);

        $csv = "Report ID,{$report->report_id}\n";
        $csv .= "Title,{$report->title}\n";
        $csv .= "Period,{$report->period_start} to {$report->period_end}\n";
        $csv .= "Generated By,{$report->generatedBy->username}\n";
        $csv .= "Generated At,{$report->generated_at}\n\n";
        $csv .= "Content\n{$report->content}\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="aggregate_report_{$id}.csv"');
    }
}