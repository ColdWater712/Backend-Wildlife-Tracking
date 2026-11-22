<?php

namespace Tests\Feature;

use App\Models\AiPrediction;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\Notification;
use App\Models\TrackingDevice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\WavFactory;
use Tests\TestCase;

class AiPipelineTest extends TestCase
{
    private array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        Storage::fake('local');
        config(['services.groq.key' => 'test-key', 'services.ai_audio.url' => null]);
        $this->h = $this->loginAs('lapangan');
    }

    private function fakeGroqChat(array $json): void
    {
        Http::fake([
            'api.groq.com/openai/v1/audio/transcriptions' => Http::response(['text' => '', 'segments' => []]),
            'api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode($json)]]],
            ]),
        ]);
    }

    public function test_camera_capture_identifies_species_individual_condition_and_behavior(): void
    {
        $camera = TrackingDevice::where('serial_number', 'CAM-0002')->first(); // Zona Perkebunan Tengah
        $tigerCollar = TrackingDevice::where('serial_number', 'GPS-0002')->first();
        GpsReading::create([
            'device_id' => $tigerCollar->device_id, 'latitude' => 3.6415, 'longitude' => 98.0395,
            'recorded_at' => now()->subMinutes(30), 'checksum' => str_repeat('a', 64),
        ]);

        $this->fakeGroqChat([
            'animal_detected' => true,
            'species_common_name' => 'Harimau Sumatra',
            'species_scientific_name' => 'Panthera tigris sumatrae',
            'confidence' => 0.91,
            'individual_count' => 1,
            'physical_condition' => 'injured',
            'condition_notes' => 'Kaki depan kiri pincang, ada luka terbuka',
            'behavior' => 'moving',
            'behavior_notes' => 'Berjalan perlahan menyusuri tepi kebun',
            'distinctive_features' => 'telinga kanan sobek di ujung, belang rapat',
            'human_activity' => ['humans_present' => false, 'weapons' => false, 'traps_or_snares' => false],
            'objects' => [['label' => 'tiger', 'score' => 0.93, 'box' => ['xmin' => 10, 'ymin' => 20, 'xmax' => 60, 'ymax' => 90]]],
            'summary' => 'Harimau dewasa terluka berjalan di tepi kebun.',
        ]);

        $response = $this->post("/api/devices/{$camera->device_id}/captures", [
            'file' => UploadedFile::fake()->image('trap.jpg', 640, 480),
            'captured_at' => now()->toDateTimeString(),
        ], $this->h)->assertCreated();

        $response->assertJsonPath('data.prediction.processing_status', 'completed')
            ->assertJsonPath('data.prediction.predictedSpecies.common_name', 'Harimau Sumatra')
            ->assertJsonPath('data.prediction.predictedAnimal.name', 'HRM-01')
            ->assertJsonPath('data.prediction.physical_condition', 'injured')
            ->assertJsonPath('data.prediction.behavior', 'moving')
            ->assertJsonPath('data.prediction.threat_level', 'medium')
            ->assertJsonPath('data.media.zone_id', $camera->zone_id);

        $this->assertContains($response->json('data.prediction.animal_match_method'), ['gps_proximity', 'zone_presence', 'distinctive_marks']);
        $this->assertTrue(IncidentReport::where('incident_type', 'kesehatan_satwa')->where('source', 'ai_camera')->exists());
        $this->assertTrue(Notification::where('notification_type', 'system')->where('message', 'like', '%HRM-01%Terluka%')->exists());

        $this->getJson('/api/animals/' . $response->json('data.prediction.predicted_animal_id') . '/ai-observations', $this->h)
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_poaching_evidence_in_image_raises_critical_threat(): void
    {
        $this->fakeGroqChat([
            'animal_detected' => false,
            'human_activity' => ['humans_present' => true, 'weapons' => true, 'traps_or_snares' => true, 'notes' => 'Dua orang membawa senapan'],
            'summary' => 'Dua orang bersenjata terlihat memasang jerat.',
        ]);

        $this->post('/api/ai/upload', [
            'file' => UploadedFile::fake()->image('people.jpg'),
            'latitude' => 3.67, 'longitude' => 98.15,
        ], $this->h)->assertCreated()
            ->assertJsonPath('data.prediction.threat_level', 'critical')
            ->assertJsonPath('data.media.zone.zone_name', 'Zona Konservasi Utara');

        $this->assertTrue(IncidentReport::where('incident_type', 'perburuan')->where('source', 'ai_camera')->exists());
        $this->assertTrue(Notification::where('notification_type', 'risk_zone')->where('message', 'like', '[CRITICAL]%')->exists());
    }

    public function test_audio_recorder_detects_chainsaw_and_creates_incident(): void
    {
        $recorder = TrackingDevice::where('serial_number', 'AUD-0001')->first();
        $this->fakeGroqChat([
            'sound_category' => 'chainsaw',
            'sound_label' => 'gergaji mesin',
            'confidence' => 0.88,
            'species_common_name' => null,
            'summary' => 'Suara gergaji mesin berkelanjutan, indikasi penebangan liar.',
        ]);

        $wav = UploadedFile::fake()->createWithContent('rec.wav', WavFactory::sustainedTone(3.0, 1200));

        $this->post("/api/devices/{$recorder->device_id}/captures", ['file' => $wav], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.media.media_type', 'audio')
            ->assertJsonPath('data.prediction.analysis_type', 'audio')
            ->assertJsonPath('data.prediction.sound_category', 'chainsaw')
            ->assertJsonPath('data.prediction.threat_level', 'high');

        $incident = IncidentReport::where('source', 'ai_audio')->firstOrFail();
        $this->assertSame('perambahan', $incident->incident_type);
        $this->assertSame($recorder->zone_id, $incident->zone_id);
        $this->assertTrue(Notification::where('notification_type', 'risk_zone')->where('message', 'like', '%gergaji mesin%')->exists());

        $this->getJson('/api/ai/threats', $this->h)->assertOk()->assertJsonPath('data.0.sound_category', 'chainsaw');
    }

    public function test_animal_call_maps_to_species(): void
    {
        $recorder = TrackingDevice::where('serial_number', 'AUD-0001')->first();
        $this->fakeGroqChat(['sound_category' => 'animal_call', 'sound_label' => 'auman harimau', 'confidence' => 0.7, 'species_common_name' => 'Harimau Sumatra']);

        $this->post("/api/devices/{$recorder->device_id}/captures", [
            'file' => UploadedFile::fake()->createWithContent('call.wav', WavFactory::sustainedTone(1.0, 300)),
        ], $this->h)->assertCreated()
            ->assertJsonPath('data.prediction.threat_level', 'none')
            ->assertJsonPath('data.prediction.predictedSpecies.scientific_name', 'Panthera tigris sumatrae');
    }

    public function test_audio_falls_back_to_heuristics_without_ai_engine(): void
    {
        config(['services.groq.key' => '']);
        $recorder = TrackingDevice::where('serial_number', 'AUD-0002')->first();

        $this->post("/api/devices/{$recorder->device_id}/captures", [
            'file' => UploadedFile::fake()->createWithContent('shot.wav', WavFactory::impulse(2.0)),
        ], $this->h)->assertCreated()
            ->assertJsonPath('data.prediction.model_version', 'heuristic-audio-v1')
            ->assertJsonPath('data.prediction.sound_category', 'gunshot')
            ->assertJsonPath('data.prediction.threat_level', 'critical');

        $this->assertTrue(IncidentReport::where('incident_type', 'perburuan')->exists());
    }

    public function test_upload_without_ai_engine_fails_gracefully_and_can_be_retried(): void
    {
        config(['services.groq.key' => '']);

        $prediction = $this->post('/api/ai/upload', ['file' => UploadedFile::fake()->image('a.jpg')], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'failed')
            ->json('data.prediction');

        config(['services.groq.key' => 'test-key']);
        $this->fakeGroqChat(['animal_detected' => true, 'species_common_name' => 'Orangutan Sumatra', 'confidence' => 0.8,
            'physical_condition' => 'healthy', 'behavior' => 'foraging']);

        $this->postJson("/api/ai-predictions/{$prediction['prediction_id']}/retry", [], $this->h)
            ->assertOk()
            ->assertJsonPath('data.prediction_id', $prediction['prediction_id'])
            ->assertJsonPath('data.processing_status', 'completed');

        $this->assertSame(1, AiPrediction::where('media_id', $prediction['media_id'])->count());
    }

    public function test_verify_prediction_and_media_file(): void
    {
        $this->fakeGroqChat(['animal_detected' => true, 'species_common_name' => 'Rusa Sambar', 'confidence' => 0.6, 'behavior' => 'grazing']);

        $data = $this->post('/api/ai/upload', ['file' => UploadedFile::fake()->image('deer.png')], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.behavior', 'foraging')
            ->json('data');

        $this->postJson("/api/ai-predictions/{$data['prediction']['prediction_id']}/verify", [
            'verification_status' => 'confirmed', 'verified_species_id' => $this->idOf('Species', 'scientific_name', 'Rusa unicolor'),
        ], $this->h)->assertOk()
            ->assertJsonPath('data.verification_status', 'confirmed')
            ->assertJsonPath('data.verifier.username', 'lapangan');

        $this->get("/api/media-uploads/{$data['media']['media_id']}/file", $this->h)
            ->assertOk()->assertHeader('Content-Type', 'image/png');

        $this->postJson("/api/ai-predictions/{$data['prediction']['prediction_id']}/retry", [], $this->h)->assertStatus(409);
    }

    public function test_summary_report_shape(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Ringkasan uji.']]]])]);

        $this->getJson('/api/ai/summary-report?start_date=' . now()->subDays(7)->toDateString(), $this->h)
            ->assertOk()
            ->assertJsonPath('data.used_ai', true)
            ->assertJsonPath('data.summary', 'Ringkasan uji.')
            ->assertJsonStructure(['data' => ['success', 'period' => ['start', 'end'], 'statistics', 'species_breakdown',
                'incidents_by_type', 'recommendations', 'ai_insights', 'generated_at']]);
    }
}
