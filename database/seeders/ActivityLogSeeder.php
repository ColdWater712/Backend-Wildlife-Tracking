<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('activity_log')->insert([
            ['log_id' => 1, 'user_id' => 1, 'action' => 'LOGIN', 'timestamp' => '2026-10-19 08:00:00', 'details' => 'User login ke sistem'],
            ['log_id' => 2, 'user_id' => 2, 'action' => 'VIEW_GPS', 'timestamp' => '2026-10-19 09:30:00', 'details' => 'Melihat data GPS SATWA-281'],
            ['log_id' => 3, 'user_id' => 3, 'action' => 'CREATE_REPORT', 'timestamp' => '2026-10-19 10:30:00', 'details' => 'Membuat laporan insiden'],
            ['log_id' => 4, 'user_id' => 1, 'action' => 'VERIFY_PREDICTION', 'timestamp' => '2026-10-19 11:00:00', 'details' => 'Memverifikasi prediksi AI'],
            ['log_id' => 5, 'user_id' => 2, 'action' => 'GENERATE_REPORT', 'timestamp' => '2026-10-19 12:00:00', 'details' => 'Generate laporan mingguan'],
        ]);
    }
}