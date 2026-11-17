<?php

namespace Database\Seeders;

use App\Models\GpsReading;
use App\Models\TrackingDevice;
use Illuminate\Database\Seeder;

class GpsReadingSeeder extends Seeder
{
    public function run(): void
    {
        $devices = TrackingDevice::all();

        foreach ($devices as $device) {
            $lat = round(3.5 + (mt_rand() / mt_getrandmax()) * 0.2, 6);
            $lon = round(97.9 + (mt_rand() / mt_getrandmax()) * 0.3, 6);

            $payload = json_encode(['device_id' => $device->device_id, 'latitude' => $lat, 'longitude' => $lon, 'recorded_at' => now()]);

            GpsReading::updateOrCreate(
                ['device_id' => $device->device_id, 'recorded_at' => now()->format('Y-m-d H:i:s')],
                [
                    'device_id' => $device->device_id,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'recorded_at' => now(),
                    'altitude_meters' => rand(100, 500),
                    'accuracy_meters' => rand(2, 20),
                    'checksum' => hash('sha256', $payload),
                ]
            );
        }
    }
}