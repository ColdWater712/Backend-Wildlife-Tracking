<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiPredictionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ai_predictions')->insert([
            ['prediction_id' => 1, 'media_id' => 1, 'predicted_species_id' => 1, 'confidence' => 0.9829, 'model_version' => 'YOLOv8-v1', 'processing_status' => 'completed', 'processed_at' => '2026-10-19 10:35:00', 'verification_status' => 'confirmed', 'verified_species_id' => 1, 'verified_by' => 2, 'verified_at' => '2026-10-19 11:00:00'],
            ['prediction_id' => 2, 'media_id' => 2, 'predicted_species_id' => 2, 'confidence' => 0.9456, 'model_version' => 'YOLOv8-v1', 'processing_status' => 'completed', 'processed_at' => '2026-10-19 09:50:00', 'verification_status' => 'confirmed', 'verified_species_id' => 2, 'verified_by' => 1, 'verified_at' => '2026-10-19 10:15:00'],
            ['prediction_id' => 3, 'media_id' => 3, 'predicted_species_id' => 3, 'confidence' => 0.8734, 'model_version' => 'YOLOv8-v1', 'processing_status' => 'completed', 'processed_at' => '2026-10-19 11:25:00', 'verification_status' => 'pending', 'verified_species_id' => null, 'verified_by' => null, 'verified_at' => null],
            ['prediction_id' => 4, 'media_id' => 4, 'predicted_species_id' => 4, 'confidence' => 0.9123, 'model_version' => 'YOLOv8-v1', 'processing_status' => 'completed', 'processed_at' => '2026-10-18 15:35:00', 'verification_status' => 'confirmed', 'verified_species_id' => 4, 'verified_by' => 2, 'verified_at' => '2026-10-18 16:00:00'],
            ['prediction_id' => 5, 'media_id' => 5, 'predicted_species_id' => 5, 'confidence' => 0.7891, 'model_version' => 'YOLOv8-v1', 'processing_status' => 'completed', 'processed_at' => '2026-10-15 09:10:00', 'verification_status' => 'rejected', 'verified_species_id' => 5, 'verified_by' => 1, 'verified_at' => '2026-10-15 10:30:00'],
        ]);
    }
}