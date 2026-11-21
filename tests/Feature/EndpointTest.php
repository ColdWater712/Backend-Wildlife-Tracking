<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EndpointTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role' => 'administrator_sistem', 'role_id' => 3, 'status' => 'aktif',
        ]);
        return $user->createToken('t')->plainTextToken;
    }

    public function test_all_list_endpoints_return_array(): void
    {
        $token = $this->adminToken();
        $headers = ['Authorization' => "Bearer $token"];

        foreach (['species', 'animals', 'devices', 'gps-readings', 'geo-zones',
                  'incidents', 'notifications', 'ai-predictions', 'weather-data',
                  'aggregate-reports', 'activity-logs', 'roles', 'permissions'] as $path) {
            $res = $this->getJson("/api/$path", $headers);
            $res->assertStatus(200);
            $this->assertIsArray($res->json('data'), "Endpoint $path harus return array");
        }
    }

    public function test_users_endpoint_returns_items_meta(): void
    {
        $token = $this->adminToken();
        $res = $this->getJson('/api/users', ['Authorization' => "Bearer $token"]);
        $res->assertStatus(200)
            ->assertJsonStructure(['data' => ['items', 'meta' => ['total', 'page', 'per_page', 'total_pages']]]);
    }

    public function test_pagination_headers_present(): void
    {
        $token = $this->adminToken();
        $res = $this->getJson('/api/animals?per_page=5&page=1', ['Authorization' => "Bearer $token"]);
        $res->assertStatus(200)->assertHeader('X-Total-Count');
    }

    public function test_user_status_update(): void
    {
        $token = $this->adminToken();
        $user = User::create([
            'name' => 'Target', 'email' => 'target@test.id',
            'password' => Hash::make('password123'),
            'role' => 'peneliti_ekologi', 'role_id' => 2, 'status' => 'aktif',
        ]);

        $this->patchJson("/api/users/{$user->user_id}/status", ['status' => 'suspend'],
            ['Authorization' => "Bearer $token"])->assertStatus(200)
            ->assertJsonPath('data.status', 'suspend');
    }

    public function test_incident_media_upload(): void
    {
        $token = $this->adminToken();
        $report = \App\Models\IncidentReport::create([
            'user_id' => 1, 'incident_type' => 'observasi',
            'location' => 'Test', 'description' => 'Deskripsi cukup panjang',
            'timestamp' => now(), 'sync_status' => 'pending_sync',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('foto.jpg');
        $this->postJson("/api/incidents/{$report->report_id}/media",
            ['file' => $file], ['Authorization' => "Bearer $token"])->assertStatus(201);
    }
}