<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GeoZone;
use App\Models\GpsReading;
use Illuminate\Database\Seeder;

class AnimalGeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $animals = Animal::all();
        $zones = GeoZone::all();

        foreach ($animals as $i => $animal) {
            $zone = $zones[$i % $zones->count()];
            $reading = GpsReading::whereHas('device', fn($q) => $q->where('animal_id', $animal->animal_id))->first();

            AnimalGeoZone::updateOrCreate(
                ['animal_id' => $animal->animal_id, 'zone_id' => $zone->zone_id, 'entered_at' => now()->subDays($i)],
                [
                    'animal_id' => $animal->animal_id,
                    'zone_id' => $zone->zone_id,
                    'entry_reading_id' => $reading?->reading_id,
                    'entered_at' => now()->subDays($i),
                    'exited_at' => $i % 2 === 0 ? now()->subDays($i)->addHours(4) : null,
                ]
            );
        }
    }
}