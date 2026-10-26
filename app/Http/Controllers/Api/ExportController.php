<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GPSReading;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function csv()
    {
        $readings = GPSReading::with(['device.animal.species'])
            ->orderBy('recorded_at', 'desc')
            ->get();

        $csv = "reading_id,animal_name,species,latitude,longitude,altitude,recorded_at\n";

        foreach ($readings as $reading) {
            $animalName = $reading->device->animal->name ?? 'Unknown';
            $species = $reading->device->animal->species->common_name ?? 'Unknown';
            $csv .= "{$reading->reading_id},{$animalName},{$species},{$reading->latitude},{$reading->longitude},{$reading->altitude_meters},{$reading->recorded_at}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="gps_readings.csv"');
    }

    public function geojson()
    {
        $readings = GPSReading::with(['device.animal.species'])
            ->orderBy('recorded_at', 'desc')
            ->get();

        $features = [];

        foreach ($readings as $reading) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $reading->longitude,
                        (float) $reading->latitude,
                        (float) $reading->altitude_meters
                    ]
                ],
                'properties' => [
                    'reading_id' => $reading->reading_id,
                    'animal_name' => $reading->device->animal->name ?? 'Unknown',
                    'species' => $reading->device->animal->species->common_name ?? 'Unknown',
                    'recorded_at' => $reading->recorded_at->toISOString(),
                    'altitude_meters' => $reading->altitude_meters,
                    'accuracy_meters' => $reading->accuracy_meters,
                ]
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        return response()->json($geojson);
    }
}