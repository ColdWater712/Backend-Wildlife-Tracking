<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function test_login_returns_shape_expected_by_frontend(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@wildlife.id', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'admin')
            ->assertJsonPath('data.user.full_name', 'Admin Sistem')
            ->assertJsonPath('data.user.role_id', $this->idOf('Role', 'name_role', 'administrator_sistem'))
            ->assertJsonPath('data.user.name_role', 'administrator_sistem')
            ->assertJsonPath('data.user.role.role_name', 'administrator_sistem')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonStructure(['data' => ['token', 'user' => ['user_id', 'email', 'status', 'permissions']]]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@wildlife.id', 'password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        \App\Models\User::where('username', 'lapangan')->update(['status' => 'nonaktif']);

        $this->postJson('/api/login', ['email' => 'lapangan@wildlife.id', 'password' => 'password123'])->assertForbidden();
    }

    public function test_me_and_logout(): void
    {
        $headers = $this->loginAs('peneliti');

        $this->getJson('/api/me', $headers)->assertOk()->assertJsonPath('data.role.role_name', 'peneliti_ekologi');
        $this->getJson('/api/v1/auth/me', $headers)->assertOk();
        $this->postJson('/api/logout', [], $headers)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', $headers)->assertUnauthorized();
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->postJson('/api/register', [])->assertNotFound();
    }

    public function test_change_password(): void
    {
        $headers = $this->loginAs('lapangan');

        $this->putJson('/api/me/password', [
            'old_password' => 'password123',
            'new_password' => 'rahasiaBaru1',
            'new_password_confirmation' => 'rahasiaBaru1',
        ], $headers)->assertOk();

        $this->postJson('/api/login', ['email' => 'lapangan@wildlife.id', 'password' => 'rahasiaBaru1'])->assertOk();
    }
}
