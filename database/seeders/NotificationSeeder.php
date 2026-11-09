<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('notifications')->insert([
            ['notif_id' => 1, 'user_id' => 1, 'notification_type' => 'risk_zone', 'message' => 'SATWA-281 memasuki zona perkebunan', 'animal_id' => 2, 'zone_id' => 2, 'device_id' => 2, 'is_read' => 0, 'created_at' => '2026-10-19 09:45:00'],
            ['notif_id' => 2, 'user_id' => 2, 'notification_type' => 'device_alert', 'message' => 'Baterai perangkat SATWA-387 lemah', 'animal_id' => 3, 'zone_id' => 3, 'device_id' => 3, 'is_read' => 1, 'created_at' => '2026-10-19 11:20:00'],
            ['notif_id' => 3, 'user_id' => 3, 'notification_type' => 'system', 'message' => 'Laporan harian siap diunduh', 'animal_id' => null, 'zone_id' => null, 'device_id' => null, 'is_read' => 1, 'created_at' => '2026-10-19 08:00:00'],
            ['notif_id' => 4, 'user_id' => 1, 'notification_type' => 'risk_zone', 'message' => 'SATWA-968 terdeteksi di zona lindung', 'animal_id' => 5, 'zone_id' => 5, 'device_id' => 5, 'is_read' => 0, 'created_at' => '2026-10-15 09:00:00'],
            ['notif_id' => 5, 'user_id' => 2, 'notification_type' => 'device_alert', 'message' => 'Perangkat SATWA-387 kehilangan sinyal', 'animal_id' => 3, 'zone_id' => 3, 'device_id' => 3, 'is_read' => 0, 'created_at' => '2026-10-19 11:25:00'],
        ]);
    }
}