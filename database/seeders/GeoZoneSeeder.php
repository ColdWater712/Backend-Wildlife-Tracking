<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->value('user_id');

        foreach ([
            ['Zona Konservasi Utara', 'koridor_ekologis', 3.671430, 98.150000, 4.000],
            ['Zona Perkebunan Tengah', 'perkebunan', 3.640000, 98.040000, 2.500],
            ['Zona Permukiman Selatan', 'permukiman', 3.560000, 97.960000, 2.000],
            ['Zona Jalan Raya Timur', 'jalan_raya', 3.918911, 97.535144, 1.514],
            ['Zona Hutan Lindung Barat', 'koridor_ekologis', 3.520000, 97.910000, 3.000],
        ] as [$name, $type, $lat, $lon, $radius]) {
            GeoZone::updateOrCreate(['zone_name' => $name], [
                'zone_type' => $type, 'center_lat' => $lat, 'center_lon' => $lon,
                'radius_km' => $radius, 'created_by' => $admin,
            ]);
        }
    }
}
