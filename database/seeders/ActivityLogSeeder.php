<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('role_name', 'administrator_sistem')->first();
        $admin = User::where('role_id', $adminRole?->role_id)->first();

        $fieldRole = Role::where('role_name', 'konservasionis_lapangan')->first();
        $fieldUser = User::where('role_id', $fieldRole?->role_id)->first();

        $logs = [
            [
                'user_id'   => $admin?->user_id,
                'action'    => 'USER_LOGIN',
                'details'   => 'Admin login ke sistem',
                'timestamp' => now()->subHours(5),
            ],
            [
                'user_id'   => $fieldUser?->user_id,
                'action'    => 'CREATE_INCIDENT',
                'details'   => 'Membuat laporan insiden konflik satwa',
                'timestamp' => now()->subHours(3),
            ],
            [
                'user_id'   => $admin?->user_id,
                'action'    => 'UPDATE_SPECIES',
                'details'   => 'Memperbarui data spesies Orangutan Sumatra',
                'timestamp' => now()->subHours(1),
            ],
            [
                'user_id'   => $admin?->user_id,
                'action'    => 'GENERATE_REPORT',
                'details'   => 'Generate laporan bulanan Oktober 2026',
                'timestamp' => now(),
            ],
        ];

        foreach ($logs as $log) {
            ActivityLog::create($log);
        }
    }
}