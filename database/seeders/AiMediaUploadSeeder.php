<?php

namespace Database\Seeders;

use App\Models\AiMediaUpload;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AiMediaUploadSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('role_name', 'administrator_sistem')->first();
        $admin = User::where('role_id', $adminRole?->role_id)->first();

        $fieldRole = Role::where('role_name', 'konservasionis_lapangan')->first();
        $fieldUser = User::where('role_id', $fieldRole?->role_id)->first();

        $uploads = [
            [
                'uploaded_by' => $admin?->user_id,
                'report_id'   => null,
                'media_type'  => 'image',
                'file_path'   => 'ai-uploads/sample_tiger_001.jpg',
            ],
            [
                'uploaded_by' => $fieldUser?->user_id,
                'report_id'   => null,
                'media_type'  => 'image',
                'file_path'   => 'ai-uploads/sample_elephant_002.jpg',
            ],
            [
                'uploaded_by' => $admin?->user_id,
                'report_id'   => null,
                'media_type'  => 'image',
                'file_path'   => 'ai-uploads/sample_orangutan_003.jpg',
            ],
        ];

        foreach ($uploads as $upload) {
            AiMediaUpload::create($upload);
        }
    }
}