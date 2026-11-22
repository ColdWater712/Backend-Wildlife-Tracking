<?php

namespace Database\Seeders;

use App\Models\GpsReading;
use App\Models\TrackingDevice;
use App\Models\WeatherData;
use App\Services\GeofenceService;
use Illuminate\Database\Seeder;

/** Membuat jejak GPS + cuaca, dan mengisi animal_geo_zones lewat geofence (bukan data acak). */
class GpsReadingSeeder extends Seeder
{
    public function run(GeofenceService $geofence): void
    {
        $tracks = [
            'GPS-0001' => [[3.640962, 98.170736], [3.655000, 98.160000], [3.670835, 98.165563]],
            'GPS-0002' => [[3.593543, 98.166935], [3.641206, 98.138207], [3.651023, 98.036691]],
            'GPS-0003' => [[3.606889, 98.117732], [3.661658, 98.093846], [3.697963, 98.025179]],
            'GPS-0004' => [[3.651817, 97.917157], [3.580000, 97.950000], [3.560500, 97.961000]],
            'GPS-0005' => [[3.593521, 97.912736], [3.510248, 97.905447], [3.502993, 97.914838]],
        ];

        foreach ($tracks as $serial => $points) {
            $device = TrackingDevice::where('serial_number', $serial)->first();
            foreach ($points as $i => [$lat, $lon]) {
                $recordedAt = now()->subHours((count($points) - $i) * 3)->startOfMinute();
                $reading = GpsReading::firstOrCreate(
                    ['device_id' => $device->device_id, 'recorded_at' => $recordedAt],
                    [
                        'latitude' => $lat, 'longitude' => $lon,
                        'altitude_meters' => random_int(100, 480), 'accuracy_meters' => random_int(2, 20),
                        'checksum' => hash('sha256', "{$device->device_id}|{$lat}|{$lon}|{$recordedAt}"),
                    ],
                );

                WeatherData::firstOrCreate(['reading_id' => $reading->reading_id], [
                    'latitude' => $lat, 'longitude' => $lon, 'observed_at' => $recordedAt,
                    'temperature_c' => random_int(2550, 2990) / 100, 'rainfall_mm' => random_int(50, 1500) / 100,
                    'humidity_percent' => random_int(7000, 9000) / 100, 'pressure_hpa' => random_int(100900, 101400) / 100,
                    'source' => 'Open-Meteo',
                ]);

                $reading->setRelation('device', $device);
                $geofence->handleReading($reading);
            }
        }
    }
}
