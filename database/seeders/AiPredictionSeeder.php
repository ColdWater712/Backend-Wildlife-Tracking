<?php

namespace Database\Seeders;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiPredictionSeeder extends Seeder
{
    public function run(): void
    {
        $media = AiMediaUpload::first();
        $species = Species::first();
        $admin = User::where('role', 'administrator_sistem')->first();

        if (!$media || !$species) return;

        AiPrediction::updateOrCreate(
            ['media_id' => $media->media_id, 'model_version' => 'YOLOv8-v1'],
            [
                'media_id' => $media->media_id,
                'predicted_species_id' => $species->species_id,
                'confidence' => 0.9829,
                'model_version' => 'YOLOv8-v1',
                'processing_status' => 'completed',
                'processed_at' => now(),
                'verification_status' => 'confirmed',
                'verified_species_id' => $species->species_id,
                'verified_by' => $admin?->user_id,
                'verified_at' => now(),
            ]
        );
    }
}