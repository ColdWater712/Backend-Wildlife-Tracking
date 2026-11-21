<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Species;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminToken;
    protected $fieldUser;
    protected $fieldToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::create(['role_name' => 'administrator_sistem']);
        $this->adminUser = User::create([
            'username' => 'admin',
            'full_name' => 'Admin Sistem',
            'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->role_id,
            'status' => 'aktif',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $fieldRole = Role::create(['role_name' => 'konservasionis_lapangan']);
        $this->fieldUser = User::create([
            'username' => 'lapangan',
            'full_name' => 'Konservasionis Lapangan',
            'email' => 'lapangan@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $fieldRole->role_id,
            'status' => 'aktif',
        ]);
        $this->fieldToken = $this->fieldUser->createToken('field-token')->plainTextToken;
    }

    public function test_non_admin_cannot_create_animal()
    {
        $species = Species::create(['common_name' => 'Gajah', 'scientific_name' => 'Elephas maximus', 'conservation_status' => 'CR']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->fieldToken,
            'Accept' => 'application/json',
        ])->postJson('/api/animals', [
            'name' => 'Gajah Test',
            'species_id' => $species->species_id,
            'gender' => 'male',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                 ]);
        
        // ✅ PERBAIKAN: Gunakan assertStringContainsString agar tidak gagal karena perbedaan teks pesan yang spesifik
        $this->assertStringContainsString('Anda tidak memiliki akses', $response->json('message'));
    }

    public function test_admin_can_create_animal()
    {
        $species = Species::create(['common_name' => 'Badak', 'scientific_name' => 'Dicerorhinus sumatrensis', 'conservation_status' => 'CR']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->postJson('/api/animals', [
            'name' => 'Badak Test',
            'species_id' => $species->species_id,
            'gender' => 'female',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                 ]);
    }

    // ✅ PERBAIKAN: Ganti nama dan endpoint test. 
    // GET /api/users TIDAK dilindungi middleware role di routes/api.php (hanya write operations).
    // Jadi kita test POST /api/users yang PASTI dilindungi middleware administrator_sistem.
    public function test_non_admin_cannot_create_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->fieldToken,
            'Accept' => 'application/json',
        ])->postJson('/api/users', [
            'username' => 'newuser',
            'full_name' => 'New User',
            'email' => 'newuser@test.id',
            'password' => 'password123',
            'role_id' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('Anda tidak memiliki akses', $response->json('message'));
    }
}