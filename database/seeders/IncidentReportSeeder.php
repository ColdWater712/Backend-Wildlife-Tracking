<?php

namespace Database\Seeders;

use App\Models\IncidentReport;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class IncidentReportSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('role_name', 'administrator_sistem')->first();
        $fieldRole = Role::where('role_name', 'konservasionis_lapangan')->first();
        
        $users = User::whereIn('role_id', [
            $adminRole?->role_id, 
            $fieldRole?->role_id
        ])->get();

        if ($users->isEmpty()) {
            $users = User::all();
        }

        $incidents = [
            ['type' => 'observasi', 'loc' => 'Zona Konservasi Utara', 'desc' => 'Satwa terlihat sehat', 'lat' => 3.5, 'lon' => 97.5],
            ['type' => 'konflik', 'loc' => 'Zona Perkebunan Tengah', 'desc' => 'Satwa masuk area perkebunan', 'lat' => 3.2, 'lon' => 97.8],
            ['type' => 'device_alert', 'loc' => 'Zona Permukiman Selatan', 'desc' => 'GPS sinyal lemah', 'lat' => 3.1, 'lon' => 97.2],
            ['type' => 'observasi', 'loc' => 'Zona Jalan Raya Timur', 'desc' => 'Satwa melintasi jalan', 'lat' => 3.8, 'lon' => 98.1],
            ['type' => 'perambahan', 'loc' => 'Zona Hutan Lindung Barat', 'desc' => 'Aktivitas ilegal', 'lat' => 3.3, 'lon' => 97.1],
        ];

        foreach ($incidents as $i => $inc) {
            $user = $users[$i % $users->count()];
            
            IncidentReport::updateOrCreate(
                ['location' => $inc['loc'], 'timestamp' => now()->subDays($i)],
                [
                    'reported_by'   => $user->user_id,
                    'incident_type' => $inc['type'],
                    'location'      => $inc['loc'],
                    'description'   => $inc['desc'],
                    'latitude'      => $inc['lat'],
                    'longitude'     => $inc['lon'],
                    'timestamp'     => now()->subDays($i),
                    'sync_status'   => $i % 3 === 0 ? 'synced' : ($i % 3 === 1 ? 'pending' : 'failed'),
                    'status'        => 'pending',
                ]
            );
        }
    }
}