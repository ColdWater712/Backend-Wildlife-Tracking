<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'administrator_sistem')->first();

        $zones = [
            ['name' => 'Zona Konservasi Utara', 'type' => 'koridor_ekologis', 'lat' => 3.671430, 'lon' => 97.878119, 'r' => 2.313],
            ['name' => 'Zona Perkebunan Tengah', 'type' => 'perkebunan', 'lat' => 3.747409, 'lon' => 97.969478, 'r' => 1.271],
            ['name' => 'Zona Permukiman Selatan', 'type' => 'permukiman', 'lat' => 3.674023, 'lon' => 97.433463, 'r' => 1.363],
            ['name' => 'Zona Jalan Raya Timur', 'type' => 'jalan_raya', 'lat' => 3.918911, 'lon' => 97.535144, 'r' => 1.514],
            ['name' => 'Zona Hutan Lindung Barat', 'type' => 'koridor_ekologis', 'lat' => 3.621963, 'lon' => 97.818243, 'r' => 2.471],
        ];

        foreach ($zones as $z) {
            GeoZone::updateOrCreate(
                ['zone_name' => $z['name']],
                [
                    'zone_name' => $z['name'],
                    'zone_type' => $z['type'],
                    'center_lat' => $z['lat'],
                    'center_lon' => $z['lon'],
                    'radius_km' => $z['r'],
                    'created_by' => $admin?->user_id,
                ]
            );
        }
    }
}