<?php

namespace Database\Seeders;

use App\Models\GpsReading;
use App\Models\WeatherData;
use Illuminate\Database\Seeder;

class WeatherDataSeeder extends Seeder
{
    public function run(): void
    {
        $readings = GpsReading::all();

        foreach ($readings as $r) {
            WeatherData::updateOrCreate(
                ['reading_id' => $r->reading_id],
                [
                    'reading_id' => $r->reading_id,
                    'latitude' => $r->latitude,
                    'longitude' => $r->longitude,
                    'observed_at' => $r->recorded_at,
                    'temperature_c' => round(25 + (mt_rand() / mt_getrandmax()) * 5, 2),
                    'rainfall_mm' => round((mt_rand() / mt_getrandmax()) * 15, 2),
                    'humidity_percent' => round(70 + (mt_rand() / mt_getrandmax()) * 22, 2),
                    'pressure_hpa' => round(1009 + (mt_rand() / mt_getrandmax()) * 5, 2),
                    'source' => 'Open-Meteo',
                ]
            );
        }
    }
}