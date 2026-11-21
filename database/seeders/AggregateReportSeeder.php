<?php

namespace Database\Seeders;

use App\Models\AggregateReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class AggregateReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'administrator_sistem')->first();

        $reports = [
            ['type' => 'daily', 'start' => '2026-10-19', 'end' => '2026-10-19', 'path' => 'reports/daily_20261019.pdf'],
            ['type' => 'weekly', 'start' => '2026-10-13', 'end' => '2026-10-19', 'path' => 'reports/weekly_20261019.pdf'],
            ['type' => 'monthly', 'start' => '2026-10-01', 'end' => '2026-10-31', 'path' => 'reports/monthly_202610.pdf'],
        ];

        foreach ($reports as $r) {
            AggregateReport::updateOrCreate(
                ['report_type' => $r['type'], 'period_start' => $r['start']],
                [
                    'generated_by' => $admin?->user_id,
                    'report_type' => $r['type'],
                    'period_start' => $r['start'],
                    'period_end' => $r['end'],
                    'file_path' => $r['path'],
                ]
            );
        }
    }
}