<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_returns_token_and_username()
    {
        $role = Role::create(['role_name' => 'administrator_sistem']);
        
        $user = User::create([
            'username' => 'admin',
            'full_name' => 'Admin Sistem',
            'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $role->role_id,
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => ['username', 'full_name', 'email', 'role'],
                         'token',
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'user' => [
                             'username' => 'admin',
                             'full_name' => 'Admin Sistem',
                         ]
                     ]
                 ]);
    }

    public function test_login_fails_wrong_password()
    {
        $role = Role::create(['role_name' => 'administrator_sistem']);
        User::create([
            'username' => 'admin',
            'full_name' => 'Admin Sistem',
            'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $role->role_id,
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.id',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_revokes_token()
    {
        $role = Role::create(['role_name' => 'administrator_sistem']);
        $user = User::create([
            'username' => 'admin',
            'full_name' => 'Admin Sistem',
            'email' => 'admin@test.id',
            'password' => Hash::make('password123'),
            'role_id' => $role->role_id,
            'status' => 'aktif',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logout berhasil',
                 ]);
    }
}