<?php

namespace Database\Seeders;

use App\Models\AiPrediction;
use App\Models\AiMediaUpload;
use App\Models\Species;
use Illuminate\Database\Seeder;

class AiPredictionSeeder extends Seeder
{
    public function run(): void
    {
        $mediaUploads = AiMediaUpload::all();
        $species = Species::all();

        if ($mediaUploads->isEmpty() || $species->isEmpty()) {
            return;
        }

        $predictions = [
            [
                'media_id'             => $mediaUploads[0]->media_id,
                'predicted_species_id' => $species->first()->species_id,
                'confidence'           => 0.92,
                'model_version'        => 'gemini-1.5-flash',
                'processing_status'    => 'completed',
                'verification_status'  => 'pending',
                'processed_at'         => now(),
            ],
            [
                'media_id'             => $mediaUploads[1]->media_id,
                'predicted_species_id' => $species->skip(1)->first()?->species_id ?? $species->first()->species_id,
                'confidence'           => 0.87,
                'model_version'        => 'gemini-1.5-flash',
                'processing_status'    => 'completed',
                'verification_status'  => 'pending',
                'processed_at'         => now(),
            ],
        ];

        foreach ($predictions as $prediction) {
            AiPrediction::create($prediction);
        }
    }
}