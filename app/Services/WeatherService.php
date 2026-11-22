<?php

namespace App\Services;

use App\Models\GpsReading;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Mengambil data cuaca historis per titik GPS dari Open-Meteo (gratis, tanpa API key). */
class WeatherService
{
    public function fetchForReading(GpsReading $reading): WeatherData
    {
        $date = $reading->recorded_at->copy()->utc();
        $isRecent = $date->gt(now()->subDays(5));
        $url = $isRecent
            ? 'https://api.open-meteo.com/v1/forecast'
            : 'https://archive-api.open-meteo.com/v1/archive';

        $response = Http::timeout(15)->get($url, [
            'latitude' => $reading->latitude,
            'longitude' => $reading->longitude,
            'hourly' => 'temperature_2m,relative_humidity_2m,precipitation,surface_pressure',
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'timezone' => 'UTC',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Open-Meteo merespons ' . $response->status());
        }

        $hourly = $response->json('hourly', []);
        $index = (int) $date->format('G');

        return WeatherData::updateOrCreate(
            ['reading_id' => $reading->reading_id],
            [
                'latitude' => $reading->latitude,
                'longitude' => $reading->longitude,
                'observed_at' => $date->copy()->startOfHour(),
                'temperature_c' => $hourly['temperature_2m'][$index] ?? null,
                'humidity_percent' => $hourly['relative_humidity_2m'][$index] ?? null,
                'rainfall_mm' => $hourly['precipitation'][$index] ?? null,
                'pressure_hpa' => $hourly['surface_pressure'][$index] ?? null,
                'source' => 'Open-Meteo',
            ],
        );
    }
}
