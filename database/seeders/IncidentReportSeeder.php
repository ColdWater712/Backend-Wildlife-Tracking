<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncidentReportSeeder extends Seeder {
    public function run(): void {
        DB::table('incident_reports')->insert([
            ['report_id' => 1, 'user_id' => 1, 'incident_type' => 'observasi', 'animal_code' => 'SATWA-742', 'location' => 'Zona Konservasi Utara', 'description' => 'Satwa terlihat sehat, bergerak normal', 'timestamp' => '2026-10-19 10:30:00', 'photo_url' => 'uploads/img_001.jpg', 'sync_status' => 'synced'],
            ['report_id' => 2, 'user_id' => 2, 'incident_type' => 'konflik', 'animal_code' => 'SATWA-281', 'location' => 'Zona Perkebunan Tengah', 'description' => 'Satwa masuk area perkebunan warga', 'timestamp' => '2026-10-19 09:45:00', 'photo_url' => 'uploads/img_002.jpg', 'sync_status' => 'synced'],
            ['report_id' => 3, 'user_id' => 3, 'incident_type' => 'device_alert', 'animal_code' => 'SATWA-803', 'location' => 'Zona Permukiman Selatan', 'description' => 'Perangkat GPS sinyal lemah', 'timestamp' => '2026-10-19 11:20:00', 'photo_url' => NULL, 'sync_status' => 'pending_sync'],
            ['report_id' => 4, 'user_id' => 1, 'incident_type' => 'observasi', 'animal_code' => 'SATWA-387', 'location' => 'Zona Jalan Raya Timur', 'description' => 'Satwa melintasi jalan raya', 'timestamp' => '2026-10-18 15:30:00', 'photo_url' => 'uploads/img_003.jpg', 'sync_status' => 'synced'],
            ['report_id' => 5, 'user_id' => 2, 'incident_type' => 'perambahan', 'animal_code' => 'SATWA-968', 'location' => 'Zona Hutan Lindung Barat', 'description' => 'Ditemukan aktivitas ilegal di zona lindung', 'timestamp' => '2026-10-15 09:00:00', 'photo_url' => 'uploads/img_004.jpg', 'sync_status' => 'failed'],
        ]);
    }
}