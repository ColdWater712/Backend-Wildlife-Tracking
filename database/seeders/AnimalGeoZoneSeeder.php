<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalGeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('animal_geo_zones')->insert([
            ['visit_id' => 1, 'animal_id' => 1, 'zone_id' => 1, 'entry_reading_id' => 1, 'entered_at' => '2026-10-19 08:00:00', 'exited_at' => '2026-10-19 12:00:00'],
            ['visit_id' => 2, 'animal_id' => 2, 'zone_id' => 2, 'entry_reading_id' => 2, 'entered_at' => '2026-10-19 09:00:00', 'exited_at' => '2026-10-19 14:00:00'],
            ['visit_id' => 3, 'animal_id' => 3, 'zone_id' => 3, 'entry_reading_id' => 3, 'entered_at' => '2026-10-19 10:00:00', 'exited_at' => null],
            ['visit_id' => 4, 'animal_id' => 4, 'zone_id' => 4, 'entry_reading_id' => 4, 'entered_at' => '2026-10-18 14:00:00', 'exited_at' => '2026-10-18 18:00:00'],
            ['visit_id' => 5, 'animal_id' => 5, 'zone_id' => 5, 'entry_reading_id' => 5, 'entered_at' => '2026-10-15 07:00:00', 'exited_at' => '2026-10-15 11:00:00'],
        ]);
    }
}