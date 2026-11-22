<?php

namespace Tests\Feature;

use App\Models\AnimalGeoZone;
use App\Models\Notification;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    private array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        $this->h = $this->loginAs('admin');
    }

    private function animal(string $code): int
    {
        return $this->idOf('Animal', 'name_animal', $code);
    }

    private function zone(string $name): int
    {
        return $this->idOf('GeoZone', 'zone_name', $name);
    }

    private function device(string $serial): int
    {
        return $this->idOf('TrackingDevice', 'serial_number', $serial);
    }

    private function orangutanSpecies(): int
    {
        return $this->idOf('Species', 'scientific_name', 'Pongo abelii');
    }

    public function test_list_endpoints_return_plain_arrays(): void
    {
        foreach (['species', 'animals', 'devices', 'gps-readings', 'geo-zones', 'incidents', 'notifications',
            'ai-predictions', 'ai-predictions/pending-verification', 'weather-data', 'animal-geo-zone-visits'] as $path) {
            $data = $this->getJson("/api/{$path}", $this->h)->assertOk()->json('data');
            $this->assertIsList($data, $path);
        }
    }

    public function test_pagination_via_headers(): void
    {
        $this->getJson('/api/species?page=1&per_page=2', $this->h)
            ->assertOk()->assertJsonCount(2, 'data')->assertHeader('X-Total-Count', 5);
    }

    public function test_animal_crud_with_frontend_payload(): void
    {
        // Frontend mengirim `name`; backend menyimpan ke `name_animal`.
        $animal = $this->postJson('/api/animals', [
            'species_id' => $this->orangutanSpecies(), 'name' => 'ORN-09', 'birth_date' => '2024-01-01', 'gender' => 'female', 'is_active' => true,
        ], $this->h)->assertCreated()
            ->assertJsonPath('data.name', 'ORN-09')
            ->assertJsonPath('data.name_animal', 'ORN-09')
            ->json('data');

        $this->putJson("/api/animals/{$animal['animal_id']}", ['species_id' => $this->orangutanSpecies(), 'name' => 'ORN-10', 'gender' => 'female', 'birth_date' => null, 'is_active' => false], $this->h)
            ->assertOk()->assertJsonPath('data.name', 'ORN-10')->assertJsonPath('data.is_active', false);

        $this->postJson('/api/animals', ['species_id' => $this->orangutanSpecies(), 'name' => 'ORN-10', 'gender' => 'male'], $this->h)->assertStatus(422);
        $this->postJson('/api/animals', ['species_id' => $this->orangutanSpecies(), 'name' => 'X', 'gender' => 'jantan'], $this->h)->assertStatus(422);
        $this->postJson('/api/animals', ['species_id' => $this->orangutanSpecies(), 'name' => 'Y', 'gender' => 'male', 'birth_date' => now()->addDay()->toDateString()], $this->h)->assertStatus(422);

        $this->deleteJson("/api/animals/{$animal['animal_id']}", [], $this->h)->assertOk();
        $this->getJson("/api/animals/{$animal['animal_id']}", $this->h)->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_species_delete_is_blocked_when_referenced(): void
    {
        $this->deleteJson('/api/species/' . $this->orangutanSpecies(), [], $this->h)->assertStatus(409);
    }

    public function test_device_types_and_assignment(): void
    {
        $gps = $this->postJson('/api/devices', ['model' => 'Vectronic GPS Plus', 'battery_level' => 90], $this->h)
            ->assertCreated()->assertJsonPath('data.device_type', 'gps')->json('data');

        $this->postJson('/api/devices', ['model' => 'Cam', 'device_type' => 'camera', 'animal_id' => $this->animal('ORN-02')], $this->h)->assertStatus(422);
        $this->postJson('/api/devices', ['model' => 'Cam', 'device_type' => 'camera', 'zone_id' => $this->zone('Zona Konservasi Utara'), 'latitude' => 3.67, 'longitude' => 98.15], $this->h)->assertCreated();

        $this->patchJson("/api/devices/{$gps['device_id']}/assign-animal", ['animal_id' => $this->animal('ORN-02')], $this->h)
            ->assertOk()->assertJsonPath('data.animal.name', 'ORN-02');
        $this->patchJson("/api/devices/{$gps['device_id']}/assign-animal", ['animal_id' => $this->animal('ORN-01')], $this->h)->assertStatus(409);
        $this->deleteJson("/api/devices/{$gps['device_id']}/assign-animal", [], $this->h)->assertOk()->assertJsonPath('data.animal_id', null);
        $this->deleteJson('/api/devices/' . $this->device('GPS-0001'), [], $this->h)->assertStatus(409);
    }

    public function test_geo_zone_accepts_polygon_string_and_rejects_invalid(): void
    {
        $polygon = json_encode(['type' => 'Polygon', 'coordinates' => [[[98.0, 3.0], [98.1, 3.0], [98.1, 3.1], [98.0, 3.1], [98.0, 3.0]]]]);

        $zone = $this->postJson('/api/geo-zones', ['zone_name' => 'Kebun Baru', 'zone_type' => 'perkebunan', 'polygon_geojson' => $polygon], $this->h)
            ->assertCreated()->assertJsonPath('data.polygon_geojson.type', 'Polygon')->json('data');

        $this->getJson('/api/geo-zones/locate?lat=3.05&lon=98.05', $this->h)->assertOk()->assertJsonPath('data.0.zone_id', $zone['zone_id']);
        $this->postJson('/api/geo-zones', ['zone_name' => 'Rusak', 'zone_type' => 'x', 'polygon_geojson' => '{bad'], $this->h)->assertStatus(422);
        $this->postJson('/api/geo-zones', ['zone_name' => 'Kosong', 'zone_type' => 'x'], $this->h)->assertStatus(422);
    }

    public function test_gps_ingest_triggers_geofence_and_is_append_only(): void
    {
        // Satwa ORN-01 (device 1) masuk Zona Permukiman Selatan.
        $orn = $this->animal('ORN-01');
        $reading = $this->postJson('/api/gps-readings', [
            'device_id' => $this->device('GPS-0001'), 'latitude' => 3.5605, 'longitude' => 97.9605, 'recorded_at' => now()->toDateTimeString(),
        ], $this->h)->assertCreated()->json('data');

        $this->assertTrue(AnimalGeoZone::where('animal_id', $orn)->where('zone_id', $this->zone('Zona Permukiman Selatan'))->whereNull('exited_at')->exists());
        $this->assertNotNull(AnimalGeoZone::where('animal_id', $orn)->where('zone_id', $this->zone('Zona Konservasi Utara'))->value('exited_at'));
        $this->assertTrue(Notification::where('notification_type', 'risk_zone')->where('message', 'like', '%ORN-01%Permukiman%')->exists());

        $this->postJson('/api/gps-readings', [
            'device_id' => $this->device('GPS-0001'), 'latitude' => 3.56, 'longitude' => 97.96, 'recorded_at' => $reading['recorded_at'],
        ], $this->h)->assertStatus(409);

        $this->putJson("/api/gps-readings/{$reading['reading_id']}", [], $this->h)->assertStatus(405);
        $this->deleteJson("/api/gps-readings/{$reading['reading_id']}", [], $this->h)->assertStatus(405);

        $this->postJson('/api/gps-readings/bulk', ['readings' => [
            ['device_id' => $this->device('GPS-0002'), 'latitude' => 3.6, 'longitude' => 98.1, 'recorded_at' => now()->subMinutes(10)->toDateTimeString()],
            ['device_id' => $this->device('GPS-0002'), 'latitude' => 3.6, 'longitude' => 98.1, 'recorded_at' => now()->subMinutes(10)->toDateTimeString()],
        ]], $this->h)->assertCreated()->assertJsonPath('data.created', 1)->assertJsonPath('data.skipped_duplicates', 1);
    }

    public function test_zone_visit_workflow(): void
    {
        $payload = ['animal_id' => $this->animal('HRM-01'), 'zone_id' => $this->zone('Zona Jalan Raya Timur')];
        $visit = $this->postJson('/api/animal-geo-zone-visits', [...$payload, 'entered_at' => now()->subHour()->toDateTimeString()], $this->h)
            ->assertCreated()->json('data');
        $this->postJson('/api/animal-geo-zone-visits', [...$payload, 'entered_at' => now()->toDateTimeString()], $this->h)->assertStatus(409);
        $this->patchJson("/api/animal-geo-zone-visits/{$visit['visit_id']}/exit", [], $this->h)->assertOk()->assertJsonPath('data.is_inside', false);
        $this->patchJson("/api/animal-geo-zone-visits/{$visit['visit_id']}/exit", [], $this->h)->assertStatus(409);
    }

    public function test_incident_update_and_sync_status(): void
    {
        $id = \App\Models\IncidentReport::whereHas('reporter', fn ($q) => $q->where('username', 'admin'))->value('report_id');
        $this->putJson("/api/incidents/{$id}", ['status' => 'resolved', 'description' => 'Selesai ditangani'], $this->h)
            ->assertOk()->assertJsonPath('data.status', 'resolved');
        $this->patchJson("/api/incidents/{$id}/sync-status", ['sync_status' => 'failed'], $this->h)->assertOk();

        $field = $this->loginAs('lapangan');
        $this->patchJson("/api/incidents/{$id}/sync-status", ['sync_status' => 'synced'], $field)->assertForbidden();
        $this->putJson("/api/incidents/{$id}", ['status' => 'x'], $field)->assertForbidden(); // bukan pelapor
    }

    public function test_notifications_are_scoped_to_owner(): void
    {
        $admin = $this->idOf('User', 'username', 'admin');
        $own = Notification::where('user_id', $admin)->first();
        $other = Notification::where('user_id', '!=', $admin)->first();

        $this->patchJson("/api/notifications/{$own->notif_id}/read", [], $this->h)->assertOk()->assertJsonPath('data.is_read', true);
        $this->patchJson("/api/notifications/{$other->notif_id}/read", [], $this->h)->assertNotFound();
        $this->getJson('/api/notifications/unread-count', $this->h)->assertOk()->assertJsonStructure(['data' => ['unread']]);
        $this->patchJson('/api/notifications/read-all', [], $this->h)->assertOk();
        $this->deleteJson('/api/notifications', [], $this->h)->assertOk();
    }

    public function test_aggregate_report_generate_and_download(): void
    {
        $report = $this->postJson('/api/aggregate-reports/generate', ['report_type' => 'weekly'], $this->h)
            ->assertCreated()->assertJsonStructure(['data' => ['report_data' => ['statistics']]])->json('data');

        $this->postJson('/api/aggregate-reports/generate', ['report_type' => 'weekly'], $this->h)->assertStatus(409);
        $this->get("/api/aggregate-reports/{$report['report_id']}/download", $this->h)->assertOk()->assertDownload();
        $this->deleteJson("/api/aggregate-reports/{$report['report_id']}", [], $this->h)->assertOk();
    }

    public function test_exports(): void
    {
        foreach (['gps/csv', 'animals/csv', 'species/csv', 'devices/csv', 'incidents/csv', 'weather/csv',
            'ai-predictions/csv', 'geo-zones/csv', 'zone-visits/csv', 'users/csv', 'activity-logs/csv'] as $path) {
            $content = $this->get("/api/export/{$path}", $this->h)->assertOk()->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content, $path);
        }

        $this->getJson('/api/export/gps/geojson', $this->h)->assertOk()->assertJsonPath('type', 'FeatureCollection');
        $this->getJson('/api/export/geo-zones/geojson', $this->h)->assertOk()->assertJsonCount(5, 'features');
    }

    public function test_write_actions_are_audited(): void
    {
        $id = $this->animal('ORN-01');
        $this->patchJson("/api/animals/{$id}/status", ['is_active' => false], $this->h)->assertOk();

        $this->getJson('/api/activity-logs?action=UPDATE', $this->h)->assertOk()
            ->assertJsonPath('data.0.details', fn ($d) => str_contains($d, "animals/{$id}/status"));
    }
}
