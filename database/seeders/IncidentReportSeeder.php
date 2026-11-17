<?php

namespace Database\Seeders;

use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidentReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('role', ['konservasionis_lapangan', 'peneliti_ekologi', 'administrator_sistem'])->get();

        $incidents = [
            ['type' => 'observasi', 'code' => 'ORN-01', 'loc' => 'Zona Konservasi Utara', 'desc' => 'Satwa terlihat sehat, bergerak normal'],
            ['type' => 'konflik', 'code' => 'HRM-01', 'loc' => 'Zona Perkebunan Tengah', 'desc' => 'Satwa masuk area perkebunan warga'],
            ['type' => 'device_alert', 'code' => 'BDK-01', 'loc' => 'Zona Permukiman Selatan', 'desc' => 'Perangkat GPS sinyal lemah'],
            ['type' => 'observasi', 'code' => 'GJS-01', 'loc' => 'Zona Jalan Raya Timur', 'desc' => 'Satwa melintasi jalan raya'],
            ['type' => 'perambahan', 'code' => 'RSA-01', 'loc' => 'Zona Hutan Lindung Barat', 'desc' => 'Ditemukan aktivitas ilegal di zona lindung'],
        ];

        foreach ($incidents as $i => $inc) {
            $user = $users[$i % $users->count()];
            IncidentReport::updateOrCreate(
                ['animal_code' => $inc['code'], 'location' => $inc['loc']],
                [
                    'user_id' => $user->user_id,
                    'incident_type' => $inc['type'],
                    'animal_code' => $inc['code'],
                    'location' => $inc['loc'],
                    'description' => $inc['desc'],
                    'timestamp' => now()->subDays($i),
                    'sync_status' => $i % 3 === 0 ? 'synced' : ($i % 3 === 1 ? 'pending_sync' : 'failed'),
                ]
            );
        }
    }
}