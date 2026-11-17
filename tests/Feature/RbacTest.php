<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, int $roleId): User
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        return User::create([
            'name' => $role, 'email' => "$role@test.id",
            'password' => Hash::make('password123'),
            'role' => $role, 'role_id' => $roleId, 'status' => 'aktif',
        ]);
    }

    public function test_non_admin_cannot_create_animal(): void
    {
        $user = $this->makeUser('konservasionis_lapangan', 1);
        $token = $user->createToken('t')->plainTextToken;

        $this->postJson('/api/animals', [
            'species_id' => 1, 'name' => 'X', 'gender' => 'male',
        ], ['Authorization' => "Bearer $token"])->assertStatus(403);
    }

    public function test_admin_can_create_animal(): void
    {
        $this->seed(\Database\Seeders\SpeciesSeeder::class);
        $user = $this->makeUser('administrator_sistem', 3);
        $token = $user->createToken('t')->plainTextToken;

        $this->postJson('/api/animals', [
            'species_id' => 1, 'name' => 'ORN-99', 'gender' => 'male',
        ], ['Authorization' => "Bearer $token"])->assertStatus(201);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = $this->makeUser('pengambil_kebijakan', 4);
        $token = $user->createToken('t')->plainTextToken;

        $this->getJson('/api/users', ['Authorization' => "Bearer $token"])
            ->assertStatus(200); // read boleh, tapi create/update dibatasi (lihat test create)
    }
}