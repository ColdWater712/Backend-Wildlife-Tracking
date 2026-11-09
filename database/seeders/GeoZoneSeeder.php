<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoZoneSeeder extends Seeder {
    public function run(): void {
        DB::table('geo_zones')->insert([
            ['zone_id' => 1, 'zone_name' => 'Zona Konservasi Utara', 'zone_type' => 'koridor_ekologis', 'center_lat' => 3.671430, 'center_lon' => 97.878119, 'radius_km' => 2.313, 'created_by' => 1, 'created_at' => '2026-10-19 10:00:00'],
            ['zone_id' => 2, 'zone_name' => 'Zona Perkebunan Tengah', 'zone_type' => 'perkebunan', 'center_lat' => 3.747409, 'center_lon' => 97.969478, 'radius_km' => 1.271, 'created_by' => 2, 'created_at' => '2026-10-19 10:00:00'],
            ['zone_id' => 3, 'zone_name' => 'Zona Permukiman Selatan', 'zone_type' => 'permukiman', 'center_lat' => 3.674023, 'center_lon' => 97.433463, 'radius_km' => 1.363, 'created_by' => 1, 'created_at' => '2026-10-19 10:00:00'],
            ['zone_id' => 4, 'zone_name' => 'Zona Jalan Raya Timur', 'zone_type' => 'jalan_raya', 'center_lat' => 3.918911, 'center_lon' => 97.535144, 'radius_km' => 1.514, 'created_by' => 3, 'created_at' => '2026-10-19 10:00:00'],
            ['zone_id' => 5, 'zone_name' => 'Zona Hutan Lindung Barat', 'zone_type' => 'koridor_ekologis', 'center_lat' => 3.621963, 'center_lon' => 97.818243, 'radius_km' => 2.471, 'created_by' => 2, 'created_at' => '2026-10-19 10:00:00'],
        ]);
    }
}