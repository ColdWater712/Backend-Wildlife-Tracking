<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'administrator_sistem')->first();

        $logs = [
            ['action' => 'LOGIN', 'details' => 'User login ke sistem'],
            ['action' => 'CREATE', 'details' => 'Membuat laporan insiden'],
            ['action' => 'UPDATE', 'details' => 'Memverifikasi prediksi AI'],
        ];

        foreach ($logs as $i => $l) {
            ActivityLog::updateOrCreate(
                ['user_id' => $admin?->user_id, 'action' => $l['action'], 'details' => $l['details']],
                [
                    'user_id' => $admin?->user_id,
                    'action' => $l['action'],
                    'details' => $l['details'],
                    'timestamp' => now()->subDays($i),
                ]
            );
        }
    }
}