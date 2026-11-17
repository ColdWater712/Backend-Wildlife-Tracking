<?php

namespace Database\Seeders;

use App\Models\AiMediaUpload;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiMediaUploadSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'administrator_sistem')->first();
        $report = IncidentReport::first();

        AiMediaUpload::updateOrCreate(
            ['file_path' => 'ai-uploads/sample_orangutan.jpg'],
            [
                'uploaded_by' => $admin?->user_id,
                'report_id' => $report?->report_id,
                'media_type' => 'image',
                'file_path' => 'ai-uploads/sample_orangutan.jpg',
            ]
        );
    }
}