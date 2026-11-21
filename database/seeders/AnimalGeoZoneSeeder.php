<?php

namespace Database\Seeders;

use App\Models\AnimalGeoZone; // ✅ PERBAIKAN: Ganti dari AnimalGeoZoneVisit
use App\Models\Animal;
use App\Models\GeoZone;
use Illuminate\Database\Seeder;

class AnimalGeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $animals = Animal::all();
        $geoZones = GeoZone::all();

        if ($animals->isEmpty() || $geoZones->isEmpty()) {
            return;
        }

        $visits = [
            [
                'animal_id'  => $animals->first()->animal_id,
                'zone_id'    => $geoZones->first()->zone_id,
                'entered_at' => now()->subDays(5),
                'exited_at'  => now()->subDays(4),
            ],
            [
                'animal_id'  => $animals->skip(1)->first()?->animal_id ?? $animals->first()->animal_id,
                'zone_id'    => $geoZones->skip(1)->first()?->zone_id ?? $geoZones->first()->zone_id,
                'entered_at' => now()->subDays(2),
                'exited_at'  => null,
            ],
        ];

        foreach ($visits as $visit) {
            // ✅ PERBAIKAN: Gunakan AnimalGeoZone::create
            AnimalGeoZone::create($visit);
        }
    }
}