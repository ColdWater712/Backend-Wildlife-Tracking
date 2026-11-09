<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeatherDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('weather_data')->insert([
            ['weather_id' => 1, 'reading_id' => 1, 'latitude' => 3.658620, 'longitude' => 97.731964, 'observed_at' => '2026-10-19 10:00:00', 'temperature_c' => 28.50, 'rainfall_mm' => 0.00, 'humidity_percent' => 75.00, 'pressure_hpa' => 1012.00, 'source' => 'Open-Meteo'],
            ['weather_id' => 2, 'reading_id' => 2, 'latitude' => 3.967782, 'longitude' => 97.381211, 'observed_at' => '2026-10-19 09:30:00', 'temperature_c' => 27.80, 'rainfall_mm' => 2.50, 'humidity_percent' => 82.00, 'pressure_hpa' => 1011.50, 'source' => 'Open-Meteo'],
            ['weather_id' => 3, 'reading_id' => 3, 'latitude' => 3.730805, 'longitude' => 97.613118, 'observed_at' => '2026-10-19 11:15:00', 'temperature_c' => 29.20, 'rainfall_mm' => 0.00, 'humidity_percent' => 70.00, 'pressure_hpa' => 1013.00, 'source' => 'Open-Meteo'],
            ['weather_id' => 4, 'reading_id' => 4, 'latitude' => 3.756809, 'longitude' => 97.450071, 'observed_at' => '2026-10-18 15:20:00', 'temperature_c' => 26.90, 'rainfall_mm' => 5.80, 'humidity_percent' => 88.00, 'pressure_hpa' => 1010.80, 'source' => 'Open-Meteo'],
            ['weather_id' => 5, 'reading_id' => 5, 'latitude' => 3.899724, 'longitude' => 97.045250, 'observed_at' => '2026-10-15 08:45:00', 'temperature_c' => 25.50, 'rainfall_mm' => 12.30, 'humidity_percent' => 92.00, 'pressure_hpa' => 1009.50, 'source' => 'Open-Meteo'],
        ]);
    }
}