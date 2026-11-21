<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_login_success_returns_token_and_username(): void
    {
        $this->seedRoles();
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role' => 'administrator_sistem',
            'role_id' => 3,
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'Admin Test')
            ->assertJsonPath('data.user.full_name', 'Admin Test');

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_wrong_password(): void
    {
        $this->seedRoles();
        User::create([
            'name' => 'Admin', 'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role' => 'administrator_sistem', 'role_id' => 3, 'status' => 'aktif',
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@test.id', 'password' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_logout_revokes_token(): void
    {
        $this->seedRoles();
        $user = User::create([
            'name' => 'Admin', 'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role' => 'administrator_sistem', 'role_id' => 3, 'status' => 'aktif',
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token"])
            ->assertStatus(200);

        $this->assertEmpty($user->tokens()->count());
    }
}