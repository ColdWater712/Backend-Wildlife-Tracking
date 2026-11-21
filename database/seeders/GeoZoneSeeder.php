<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('username', 'admin')->first() ?? User::first();
        $userId = $user ? $user->user_id : null;

        $geoZones = [
            [
                'zone_name'       => 'Zona Inti TNGL', 
                'zone_type'       => 'koridor_ekologis',
                'polygon_geojson' => json_encode([
                    ['lat' => 3.5, 'lng' => 97.5],
                    ['lat' => 3.5, 'lng' => 98.5],
                    ['lat' => 4.5, 'lng' => 98.5],
                    ['lat' => 4.5, 'lng' => 97.5]
                ]),
                'created_by'      => $userId,
            ],
            [
                'zone_name'       => 'Zona Penyangga TNGL',
                'zone_type'       => 'perkebunan',
                'polygon_geojson' => json_encode([
                    ['lat' => 3.0, 'lng' => 97.0],
                    ['lat' => 3.0, 'lng' => 99.0],
                    ['lat' => 5.0, 'lng' => 99.0],
                    ['lat' => 5.0, 'lng' => 97.0]
                ]),
                'created_by'      => $userId,
            ],
        ];

        foreach ($geoZones as $zone) {
            GeoZone::create($zone);
        }
    }
}