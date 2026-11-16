<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Species;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@wildlife.id',
            'password' => bcrypt('password123'),
            'role' => 'administrator_sistem'
        ]);
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        Species::create([
            'common_name' => 'Test Species',
            'scientific_name' => 'Testus speciesus',
            'conservation_status' => 'Safe'
        ]);
    }

    public function test_login_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'bukan-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email'])
                 ->assertJsonPath('message', 'Format email tidak valid.'); 
    }

    public function test_login_fails_with_empty_fields()
    {
        $response = $this->postJson('/api/login', [
            'email' => '',
            'password' => ''
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_store_animal_fails_with_invalid_gender()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/animals', [
                'species_id' => 1,
                'name' => 'SATWA-TEST',
                'gender' => 'other', 
                'birth_date' => '2020-01-01'
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['gender'])
                 ->assertJsonPath('message', 'Jenis kelamin harus: male, female, atau unknown.');
    }

    public function test_store_animal_fails_with_future_birth_date()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/animals', [
                'species_id' => 1,
                'name' => 'SATWA-TEST',
                'gender' => 'male',
                'birth_date' => '2030-01-01' 
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['birth_date']);
    }

    public function test_store_incident_fails_with_non_image_url()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/incidents', [
                'incident_type' => 'observasi',
                'location' => 'Zona A',
                'description' => 'Deskripsi yang cukup panjang untuk lolos validasi min 10 karakter',
                'photo_url' => 'https://example.com/dokumen.pdf' 
            ]);

        
        $response->assertStatus(422)
             ->assertJsonValidationErrors(['photo_url'])
             ->assertJsonPath('message', 'URL foto harus berakhiran dengan ekstensi gambar (.jpg, .png, .gif, .webp, .bmp, .svg).');
    }

    public function test_store_incident_succeeds_with_valid_image_url()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/incidents', [
                'incident_type' => 'observasi',
                'location' => 'Zona A',
                'description' => 'Deskripsi yang cukup panjang untuk lolos validasi min 10 karakter',
                'photo_url' => 'https://example.com/foto.JPG?size=large' 
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);
    }
}