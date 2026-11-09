<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrackingDeviceSeeder extends Seeder {
    public function run(): void {
        DB::table('tracking_devices')->insert([
            ['device_id' => 1, 'animal_id' => 1, 'model' => 'Lotek Iridium TrackLite', 'installation_date' => '2026-01-21', 'battery_life_months' => 12, 'is_active' => 1, 'device_condition' => 'normal', 'battery_level' => 87, 'signal_strength' => 4, 'last_recorded_at' => '2026-10-19 10:00:00'],
            ['device_id' => 2, 'animal_id' => 2, 'model' => 'Lotek GPS-Plus', 'installation_date' => '2026-08-22', 'battery_life_months' => 12, 'is_active' => 1, 'device_condition' => 'normal', 'battery_level' => 53, 'signal_strength' => 3, 'last_recorded_at' => '2026-10-19 09:30:00'],
            ['device_id' => 3, 'animal_id' => 3, 'model' => 'Televilt Telemetry', 'installation_date' => '2026-02-12', 'battery_life_months' => 12, 'is_active' => 1, 'device_condition' => 'normal', 'battery_level' => 98, 'signal_strength' => 4, 'last_recorded_at' => '2026-10-19 11:15:00'],
            ['device_id' => 4, 'animal_id' => 4, 'model' => 'Lotek GPS-Plus', 'installation_date' => '2026-04-07', 'battery_life_months' => 12, 'is_active' => 1, 'device_condition' => 'damaged', 'battery_level' => 25, 'signal_strength' => 1, 'last_recorded_at' => '2026-10-18 15:20:00'],
            ['device_id' => 5, 'animal_id' => 5, 'model' => 'Lotek GPS-Plus', 'installation_date' => '2026-07-18', 'battery_life_months' => 12, 'is_active' => 1, 'device_condition' => 'lost', 'battery_level' => 0, 'signal_strength' => 0, 'last_recorded_at' => '2026-10-15 08:45:00'],
        ]);
    }
}