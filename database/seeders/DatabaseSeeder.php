<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * CATATAN: Jangan jalankan seeder ini jika database sudah di-import dari SQL dump.
     * File ini dibuat untuk kelengkapan struktur proyek Laravel.
     */
    public function run(): void
    {
        $this->call([
            // 1. Core & Autentikasi (RBAC)
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,

            // 2. Domain Utama (Satwa & Pelacakan)
            SpeciesSeeder::class,
            AnimalSeeder::class,
            TrackingDeviceSeeder::class,
            GpsReadingSeeder::class,
            GeoZoneSeeder::class,
            AnimalGeoZoneSeeder::class,

            // 3. Fitur Pendukung
            IncidentReportSeeder::class,
            NotificationSeeder::class,
            AggregateReportSeeder::class,
            WeatherDataSeeder::class,
            ActivityLogSeeder::class,

            // 4. Fitur AI
            AiMediaUploadSeeder::class,
            AiPredictionSeeder::class,
        ]);
    }
}