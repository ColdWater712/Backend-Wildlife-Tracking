<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\GeoZone;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidentReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('user_id', 'username');
        $animals = Animal::get()->keyBy('name_animal');
        $zones = GeoZone::get()->keyBy('zone_name');

        foreach ([
            ['admin', 'observasi', 'ORN-01', 'Zona Konservasi Utara', 'Satwa terlihat sehat, bergerak normal', 0, 'synced'],
            ['peneliti', 'konflik', 'HRM-01', 'Zona Perkebunan Tengah', 'Satwa masuk area perkebunan warga', 1, 'pending_sync'],
            ['lapangan', 'device_alert', 'BDK-01', 'Zona Permukiman Selatan', 'Perangkat GPS sinyal lemah', 2, 'failed'],
            ['admin', 'observasi', 'GJS-01', 'Zona Jalan Raya Timur', 'Satwa melintasi jalan raya', 3, 'synced'],
            ['peneliti', 'perambahan', 'RSA-01', 'Zona Hutan Lindung Barat', 'Ditemukan aktivitas ilegal di zona lindung', 4, 'pending_sync'],
        ] as [$user, $type, $animalCode, $zoneName, $description, $daysAgo, $sync]) {
            $animal = $animals[$animalCode];
            $zone = $zones[$zoneName];

            IncidentReport::firstOrCreate(['description' => $description], [
                'user_id' => $users[$user],
                'animal_id' => $animal->animal_id,
                'species_id' => $animal->species_id,
                'zone_id' => $zone->zone_id,
                'incident_type' => $type,
                'timestamp' => now()->subDays($daysAgo),
                'latitude' => $zone->center_lat,
                'longitude' => $zone->center_lon,
                'location' => $zone->zone_name,
                'status' => 'pending',
                'sync_status' => $sync,
                'source' => 'manual',
            ]);
        }
    }
}
