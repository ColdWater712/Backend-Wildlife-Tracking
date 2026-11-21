<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ 1. Ambil user berdasarkan username (menghindari error kolom 'role' yang sudah dihapus)
        $user = User::where('username', 'admin')->first() ?? User::first();
        $userId = $user ? $user->user_id : null;

        // ⚠️ 2. PERHATIAN: Error sebelumnya mengatakan kolom 'name' tidak ada.
        // Kemungkinan besar di migrasi Anda, kolom ini bernama 'zone_name'.
        // Jika di migrasi Anda namanya 'name', silakan ubah 'zone_name' di bawah menjadi 'name'.
        
        $geoZones = [
            [
                'zone_name'  => 'Zona Inti TNGL', 
                'zone_type'  => 'core',
                'polygon'    => json_encode([
                    ['lat' => 3.5, 'lng' => 97.5],
                    ['lat' => 3.5, 'lng' => 98.5],
                    ['lat' => 4.5, 'lng' => 98.5],
                    ['lat' => 4.5, 'lng' => 97.5]
                ]),
                'is_active'  => true,
                'created_by' => $userId,
            ],
            [
                'zone_name'  => 'Zona Penyangga TNGL',
                'zone_type'  => 'buffer',
                'polygon'    => json_encode([
                    ['lat' => 3.0, 'lng' => 97.0],
                    ['lat' => 3.0, 'lng' => 99.0],
                    ['lat' => 5.0, 'lng' => 99.0],
                    ['lat' => 5.0, 'lng' => 97.0]
                ]),
                'is_active'  => true,
                'created_by' => $userId,
            ],
        ];

        foreach ($geoZones as $zone) {
            GeoZone::create($zone);
        }
    }
}