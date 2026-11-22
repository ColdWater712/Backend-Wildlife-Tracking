<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            SpeciesSeeder::class,
            GeoZoneSeeder::class,
            AnimalSeeder::class,
            TrackingDeviceSeeder::class,
            GpsReadingSeeder::class,
            IncidentReportSeeder::class,
            NotificationSeeder::class,
            AiSampleSeeder::class,
        ]);
    }
}
