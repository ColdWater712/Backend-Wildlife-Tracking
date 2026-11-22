<?php

namespace Database\Seeders;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\Animal;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Contoh hasil AI (tanpa memanggil API) untuk demo halaman AI di frontend. */
class AiSampleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->value('user_id');
        $camera = TrackingDevice::where('serial_number', 'CAM-0001')->first();
        $audio = TrackingDevice::where('serial_number', 'AUD-0002')->first();
        $orangutan = Animal::where('name_animal', 'ORN-01')->first();
        $incident = IncidentReport::where('incident_type', 'perambahan')->first();

        $image = AiMediaUpload::firstOrCreate(['file_path' => 'ai-media/image/sample_orangutan.jpg'], [
            'uploaded_by' => $admin, 'report_id' => IncidentReport::where('incident_type', 'observasi')->value('report_id'),
            'device_id' => $camera->device_id, 'zone_id' => $camera->zone_id, 'media_type' => 'image',
            'mime_type' => 'image/jpeg', 'latitude' => $camera->latitude, 'longitude' => $camera->longitude,
            'captured_at' => now()->subHours(5), 'uploaded_at' => now()->subHours(5),
        ]);

        AiPrediction::firstOrCreate(['media_id' => $image->media_id, 'model_version' => 'groq:sample'], [
            'analysis_type' => 'image', 'predicted_species_id' => $orangutan->species_id, 'confidence' => 0.9829,
            'detected_label' => 'Orangutan Sumatra', 'individual_count' => 1,
            'predicted_animal_id' => $orangutan->animal_id, 'animal_match_confidence' => 0.82, 'animal_match_method' => 'gps_proximity',
            'physical_condition' => 'healthy', 'condition_notes' => 'Tubuh proporsional, tidak terlihat luka.',
            'behavior' => 'foraging', 'behavior_notes' => 'Sedang memakan buah di dahan.',
            'threat_level' => 'none', 'ai_summary' => 'Satu orangutan jantan muda sedang makan buah, kondisi sehat.',
            'detected_objects' => [['label' => 'orangutan', 'score' => 0.98, 'box' => ['xmin' => 22, 'ymin' => 15, 'xmax' => 70, 'ymax' => 88]]],
            'processing_status' => 'completed', 'processed_at' => now()->subHours(5),
            'verification_status' => 'confirmed', 'verified_species_id' => $orangutan->species_id,
            'verified_animal_id' => $orangutan->animal_id, 'verified_by' => $admin, 'verified_at' => now()->subHours(4),
        ]);

        $sound = AiMediaUpload::firstOrCreate(['file_path' => 'ai-media/audio/sample_chainsaw.wav'], [
            'uploaded_by' => $admin, 'report_id' => $incident?->report_id, 'device_id' => $audio->device_id,
            'zone_id' => $audio->zone_id, 'media_type' => 'audio', 'mime_type' => 'audio/wav',
            'latitude' => $audio->latitude, 'longitude' => $audio->longitude,
            'captured_at' => now()->subDays(4), 'uploaded_at' => now()->subDays(4),
        ]);

        AiPrediction::firstOrCreate(['media_id' => $sound->media_id, 'model_version' => 'groq:sample-audio'], [
            'analysis_type' => 'audio', 'confidence' => 0.87, 'detected_label' => 'gergaji mesin',
            'sound_category' => 'chainsaw', 'sound_label' => 'gergaji mesin', 'threat_level' => 'high',
            'ai_summary' => 'Terdeteksi suara gergaji mesin berkelanjutan selama ±40 detik, indikasi penebangan liar.',
            'processing_status' => 'completed', 'processed_at' => now()->subDays(4),
            'verification_status' => 'pending',
        ]);
    }
}
