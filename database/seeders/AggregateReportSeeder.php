<?php

namespace Database\Seeders;

use App\Models\AggregateReport;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AggregateReportSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('role_name', 'administrator_sistem')->first();
        $admin = User::where('role_id', $adminRole?->role_id)->first();

        $reports = [
            [
                'report_type'  => 'daily', 
                'period_start' => '2026-10-19', 
                'period_end'   => '2026-10-19', 
                'file_path'    => 'reports/daily_20261019.pdf'
            ],
            [
                'report_type'  => 'weekly', 
                'period_start' => '2026-10-13', 
                'period_end'   => '2026-10-19', 
                'file_path'    => 'reports/weekly_20261019.pdf'
            ],
            [
                'report_type'  => 'monthly', 
                'period_start' => '2026-10-01', 
                'period_end'   => '2026-10-31', 
                'file_path'    => 'reports/monthly_202610.pdf'
            ],
        ];

        foreach ($reports as $r) {
            AggregateReport::updateOrCreate(
                ['report_type' => $r['report_type'], 'period_start' => $r['period_start']],
                [
                    'generated_by' => $admin?->user_id,
                    'period_end'   => $r['period_end'],
                    'file_path'    => $r['file_path'],
                    'generated_at' => now(),
                ]
            );
        }
    }
}