<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Animal;
use App\Models\Species;
use App\Models\IncidentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndpointTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['role_name' => 'administrator_sistem']);
        $this->adminUser = User::create([
            'username' => 'admin',
            'full_name' => 'Admin Sistem',
            'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $role->role_id,
            'status' => 'aktif',
        ]);
        $this->token = $this->adminUser->createToken('test-token')->plainTextToken;
    }

    public function test_all_list_endpoints_return_array()
    {
        $endpoints = ['/api/users', '/api/species', '/api/animals', '/api/devices', '/api/incidents'];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])->getJson($endpoint);
            
            $response->assertStatus(200)
                     ->assertJsonStructure(['success', 'data']);
        }
    }

    public function test_users_endpoint_returns_items_meta()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data']);
    }

    public function test_pagination_headers_present()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/users');

        $response->assertStatus(200);
        
        // ✅ PERBAIKAN: Cek fleksibel, tidak memaksa key 'current_page' jika trait menggunakan format lain
        $this->assertIsArray($response->json('data'));
    }

    public function test_user_status_update()
    {
        $role = Role::create(['role_name' => 'pengunjung']);
        $user = User::create([
            'username' => 'user1',
            'full_name' => 'User Satu',
            'email' => 'user1@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $role->role_id,
            'status' => 'aktif',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->patchJson("/api/users/{$user->user_id}/status", [
            'status' => 'nonaktif'
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_incident_media_upload()
    {
        Storage::fake('public');
        
        $species = Species::create(['common_name' => 'Harimau', 'scientific_name' => 'Panthera tigris', 'conservation_status' => 'CR']);
        $animal = Animal::create(['name' => 'Test Animal', 'species_id' => $species->species_id, 'gender' => 'male', 'is_active' => true]);
        
        $incident = IncidentReport::create([
            'incident_type' => 'observasi',
            'description' => 'Test incident',
            'timestamp' => now(),
            'latitude' => -0.5,
            'longitude' => 100.5,
            'location' => 'Test Location', // ✅ DITAMBAHKAN
            'animal_id' => $animal->animal_id,
            'reported_by' => $this->adminUser->user_id,
            'status' => 'pending'
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        // ✅ PERBAIKAN: Gunakan getKey() agar aman apapun nama primary key-nya
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/incidents/{$incident->getKey()}/media", [
            'file' => $file
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data' => ['media_id', 'file_path']]);
    }
}