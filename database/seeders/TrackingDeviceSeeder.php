<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\TrackingDevice;
use Illuminate\Database\Seeder;

class TrackingDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            ['code' => 'ORN-01', 'model' => 'Lotek Iridium TrackLite', 'install' => '2026-01-21', 'battery' => 87, 'signal' => 4, 'condition' => 'normal'],
            ['code' => 'HRM-01', 'model' => 'Lotek GPS-Plus', 'install' => '2026-08-22', 'battery' => 53, 'signal' => 3, 'condition' => 'normal'],
            ['code' => 'BDK-01', 'model' => 'Televilt Telemetry', 'install' => '2026-02-12', 'battery' => 98, 'signal' => 4, 'condition' => 'normal'],
            ['code' => 'GJS-01', 'model' => 'Lotek GPS-Plus', 'install' => '2026-04-07', 'battery' => 25, 'signal' => 1, 'condition' => 'damaged'],
            ['code' => 'RSA-01', 'model' => 'Lotek GPS-Plus', 'install' => '2026-07-18', 'battery' => 0, 'signal' => 0, 'condition' => 'lost'],
        ];

        foreach ($devices as $d) {
            $animal = Animal::where('name', $d['code'])->first();
            if (!$animal) continue;

            TrackingDevice::updateOrCreate(
                ['animal_id' => $animal->animal_id],
                [
                    'animal_id' => $animal->animal_id,
                    'model' => $d['model'],
                    'installation_date' => $d['install'],
                    'battery_life_months' => 12,
                    'is_active' => true,
                    'device_condition' => $d['condition'],
                    'battery_level' => $d['battery'],
                    'signal_strength' => $d['signal'],
                    'last_recorded_at' => now(),
                ]
            );
        }
    }
}