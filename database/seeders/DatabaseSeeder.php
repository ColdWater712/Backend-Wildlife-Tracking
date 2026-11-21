<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            SpeciesSeeder::class,
            AnimalSeeder::class,
            TrackingDeviceSeeder::class,
            GpsReadingSeeder::class,
            WeatherDataSeeder::class,
            GeoZoneSeeder::class,
            AnimalGeoZoneSeeder::class,
            IncidentReportSeeder::class,
            NotificationSeeder::class,
            AggregateReportSeeder::class,
            ActivityLogSeeder::class,
            AiMediaUploadSeeder::class,
            AiPredictionSeeder::class,
        ]);
    }
}