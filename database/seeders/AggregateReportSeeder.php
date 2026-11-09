<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AggregateReportSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('aggregate_reports')->insert([
            ['report_id' => 1, 'generated_by' => 1, 'report_type' => 'daily', 'period_start' => '2026-10-19', 'period_end' => '2026-10-19', 'generated_at' => '2026-10-19 18:00:00', 'file_path' => 'reports/daily_20261019.pdf'],
            ['report_id' => 2, 'generated_by' => 2, 'report_type' => 'weekly', 'period_start' => '2026-10-13', 'period_end' => '2026-10-19', 'generated_at' => '2026-10-19 18:30:00', 'file_path' => 'reports/weekly_20261019.pdf'],
            ['report_id' => 3, 'generated_by' => 1, 'report_type' => 'monthly', 'period_start' => '2026-10-01', 'period_end' => '2026-10-31', 'generated_at' => '2026-10-19 19:00:00', 'file_path' => 'reports/monthly_202610.pdf'],
            ['report_id' => 4, 'generated_by' => 3, 'report_type' => 'daily', 'period_start' => '2026-10-18', 'period_end' => '2026-10-18', 'generated_at' => '2026-10-18 18:00:00', 'file_path' => 'reports/daily_20261018.pdf'],
            ['report_id' => 5, 'generated_by' => 2, 'report_type' => 'weekly', 'period_start' => '2026-10-06', 'period_end' => '2026-10-12', 'generated_at' => '2026-10-12 18:30:00', 'file_path' => 'reports/weekly_20261012.pdf'],
        ]);
    }
}