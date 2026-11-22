<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\GeoZone;
use App\Models\TrackingDevice;
use Illuminate\Database\Seeder;

class TrackingDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $animals = Animal::pluck('animal_id', 'name_animal');
        $zones = GeoZone::pluck('zone_id', 'zone_name');

        // Kalung GPS
        foreach ([
            ['GPS-0001', 'ORN-01', 'Lotek Iridium TrackLite', '2026-01-21', 87, 4, 'normal'],
            ['GPS-0002', 'HRM-01', 'Lotek GPS-Plus', '2026-08-22', 53, 3, 'normal'],
            ['GPS-0003', 'BDK-01', 'Televilt Telemetry', '2026-02-12', 98, 4, 'normal'],
            ['GPS-0004', 'GJS-01', 'Lotek GPS-Plus', '2026-04-07', 15, 1, 'damaged'],
            ['GPS-0005', 'RSA-01', 'Lotek GPS-Plus', '2026-07-18', 0, 0, 'lost'],
        ] as [$serial, $animal, $model, $date, $battery, $signal, $condition]) {
            TrackingDevice::updateOrCreate(['serial_number' => $serial], [
                'device_type' => 'gps', 'animal_id' => $animals[$animal], 'model' => $model,
                'installation_date' => $date, 'battery_life_months' => 12, 'is_active' => $condition !== 'lost',
                'device_condition' => $condition, 'battery_level' => $battery, 'signal_strength' => $signal,
                'last_recorded_at' => now()->subHour(),
            ]);
        }

        // Kamera jebak & perekam audio (dipasang di titik tetap)
        foreach ([
            ['CAM-0001', 'camera', 'Browning Recon Force Elite', 'Zona Konservasi Utara', 3.6700, 98.1600],
            ['CAM-0002', 'camera', 'Bushnell CORE DS-4K', 'Zona Perkebunan Tengah', 3.6410, 98.0390],
            ['AUD-0001', 'audio', 'Wildlife Acoustics Song Meter Mini 2', 'Zona Konservasi Utara', 3.6650, 98.1550],
            ['AUD-0002', 'audio', 'AudioMoth 1.2', 'Zona Hutan Lindung Barat', 3.5200, 97.9100],
        ] as [$serial, $type, $model, $zone, $lat, $lon]) {
            TrackingDevice::updateOrCreate(['serial_number' => $serial], [
                'device_type' => $type, 'model' => $model, 'zone_id' => $zones[$zone],
                'latitude' => $lat, 'longitude' => $lon, 'installation_date' => '2026-06-01',
                'battery_life_months' => 6, 'is_active' => true, 'device_condition' => 'normal',
                'battery_level' => 76, 'signal_strength' => 3, 'last_recorded_at' => now()->subHours(2),
            ]);
        }
    }
}
