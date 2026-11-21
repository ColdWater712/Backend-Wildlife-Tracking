<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function exportGpsCsv(Request $request)
    {
        $query = GpsReading::with(['device.animal.species']);
        if ($request->filled('device_id')) $query->where('device_id', $request->device_id);
        if ($request->filled('start_date')) $query->where('recorded_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('recorded_at', '<=', $request->end_date);

        $readings = $query->orderBy('recorded_at', 'desc')->limit(10000)->get();

        $csv = "reading_id,device_id,animal_name,species,latitude,longitude,recorded_at,altitude_meters,accuracy_meters,checksum\n";
        foreach ($readings as $r) {
            $csv .= sprintf("%d,%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $r->reading_id, $r->device_id,
                $r->device?->animal?->name ?? '',
                $r->device?->animal?->species?->common_name ?? '',
                $r->latitude, $r->longitude, $r->recorded_at,
                $r->altitude_meters ?? '', $r->accuracy_meters ?? '', $r->checksum ?? ''
            );
        }

        return response($csv)->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="gps_' . date('Y-m-d') . '.csv"');
    }

    public function exportGpsGeoJson(Request $request)
    {
        $query = GpsReading::with(['device.animal.species']);
        if ($request->filled('device_id')) $query->where('device_id', $request->device_id);
        if ($request->filled('animal_id')) $query->whereHas('device', fn($q) => $q->where('animal_id', $request->animal_id));

        $readings = $query->orderBy('recorded_at', 'desc')->limit(1000)->get();

        $features = $readings->map(fn($r) => [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [(float)$r->longitude, (float)$r->latitude]],
            'properties' => [
                'reading_id' => $r->reading_id,
                'device_id' => $r->device_id,
                'animal_name' => $r->device?->animal?->name,
                'species' => $r->device?->animal?->species?->common_name,
                'recorded_at' => $r->recorded_at,
                'altitude' => $r->altitude_meters,
            ],
        ]);

        return response()->json(['type' => 'FeatureCollection', 'features' => $features])
            ->header('Content-Disposition', 'attachment; filename="gps_' . date('Y-m-d') . '.geojson"');
    }

    public function exportAnimalsCsv(Request $request)
    {
        $query = Animal::with(['species', 'trackingDevice']);
        if ($request->filled('species_id')) $query->where('species_id', $request->species_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->boolean('is_active'));

        $animals = $query->get();

        $csv = "animal_id,name,species,birth_date,gender,is_active,device_model,device_condition\n";
        foreach ($animals as $a) {
            $csv .= sprintf("%d,%s,%s,%s,%s,%d,%s,%s\n",
                $a->animal_id, $a->name,
                $a->species?->common_name ?? '',
                $a->birth_date?->format('Y-m-d') ?? '',
                $a->gender, $a->is_active,
                $a->trackingDevice?->model ?? '',
                $a->trackingDevice?->device_condition ?? ''
            );
        }

        return response($csv)->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="animals_' . date('Y-m-d') . '.csv"');
    }

    public function exportIncidentsCsv(Request $request)
    {
        $query = IncidentReport::with('reporter');
        if ($request->filled('incident_type')) $query->where('incident_type', $request->incident_type);
        if ($request->filled('sync_status')) $query->where('sync_status', $request->sync_status);

        $incidents = $query->orderBy('timestamp', 'desc')->get();

        $csv = "report_id,reporter,incident_type,animal_code,location,description,timestamp,sync_status\n";
        foreach ($incidents as $i) {
            $csv .= sprintf("%d,%s,%s,%s,%s,%s,%s,%s\n",
                $i->report_id, $i->reporter?->name ?? '',
                $i->incident_type, $i->animal_code ?? '',
                str_replace(',', ';', $i->location),
                str_replace(',', ';', $i->description),
                $i->timestamp, $i->sync_status
            );
        }

        return response($csv)->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="incidents_' . date('Y-m-d') . '.csv"');
    }

    public function exportDevicesCsv(Request $request)
    {
        $query = TrackingDevice::with(['animal.species']);
        if ($request->filled('device_condition')) $query->where('device_condition', $request->device_condition);
        if ($request->filled('is_active')) $query->where('is_active', $request->boolean('is_active'));

        $devices = $query->get();

        $csv = "device_id,animal_name,species,model,installation_date,battery_level,signal_strength,device_condition,is_active\n";
        foreach ($devices as $d) {
            $csv .= sprintf("%d,%s,%s,%s,%s,%d,%d,%s,%d\n",
                $d->device_id,
                $d->animal?->name ?? '',
                $d->animal?->species?->common_name ?? '',
                $d->model,
                $d->installation_date?->format('Y-m-d') ?? '',
                $d->battery_level ?? 0,
                $d->signal_strength ?? 0,
                $d->device_condition,
                $d->is_active
            );
        }

        return response($csv)->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="devices_' . date('Y-m-d') . '.csv"');
    }
}