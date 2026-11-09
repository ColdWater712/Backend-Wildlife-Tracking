<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GpsReadingSeeder extends Seeder {
    public function run(): void {
        DB::table('gps_readings')->insert([
            ['reading_id' => 1, 'device_id' => 1, 'latitude' => 3.658620, 'longitude' => 97.731964, 'recorded_at' => '2026-10-19 10:00:00', 'altitude_meters' => 489.16, 'accuracy_meters' => 17.06, 'checksum' => 'f468e4c385bf213285a191088c66bbe68652d3caa6f18f49dd80f77e36f6ba57'],
            ['reading_id' => 2, 'device_id' => 2, 'latitude' => 3.967782, 'longitude' => 97.381211, 'recorded_at' => '2026-10-19 09:30:00', 'altitude_meters' => 379.69, 'accuracy_meters' => 8.65, 'checksum' => '4a33e9b80e758d0ee44c4cbbf53ea104f2485dda61ba0747b173c573f537b0f9'],
            ['reading_id' => 3, 'device_id' => 3, 'latitude' => 3.730805, 'longitude' => 97.613118, 'recorded_at' => '2026-10-19 11:15:00', 'altitude_meters' => 517.81, 'accuracy_meters' => 16.03, 'checksum' => 'fdad1e5a093cfaa2f8d4f1db9be8ed9646c7e426f8d303753dc0ff995d027b9a'],
            ['reading_id' => 4, 'device_id' => 4, 'latitude' => 3.756809, 'longitude' => 97.450071, 'recorded_at' => '2026-10-18 15:20:00', 'altitude_meters' => 205.69, 'accuracy_meters' => 17.35, 'checksum' => '3f25373bcb50467e3b29408a5d03eb790ab900e892a06bea001b69cef7936db7'],
            ['reading_id' => 5, 'device_id' => 5, 'latitude' => 3.899724, 'longitude' => 97.045250, 'recorded_at' => '2026-10-15 08:45:00', 'altitude_meters' => 125.96, 'accuracy_meters' => 13.73, 'checksum' => '633b3eb374f92b1e3b49f2bbdd30a30601bc8bce1353f2e7f46d46af8c9232f8'],
        ]);
    }
}