<?php

namespace Tests\Feature;

use Tests\TestCase;

class RbacTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function test_field_officer_cannot_manage_master_data_or_users(): void
    {
        $headers = $this->loginAs('lapangan');

        $this->postJson('/api/species', ['common_name' => 'X', 'scientific_name' => 'X y', 'conservation_status' => 'Vulnerable'], $headers)->assertForbidden();
        $this->getJson('/api/users', $headers)->assertForbidden();
        $this->getJson('/api/roles', $headers)->assertForbidden();
        $this->getJson('/api/export/animals/csv', $headers)->assertForbidden();
    }

    public function test_field_officer_can_report_incident(): void
    {
        $this->postJson('/api/incidents', [
            'incident_type' => 'konflik',
            'description' => 'Gajah masuk kebun',
            'timestamp' => now()->subHour()->toDateTimeString(),
            'latitude' => 3.64,
            'longitude' => 98.04,
            'location' => 'Kebun warga',
            'status' => 'pending',
        ], $this->loginAs('lapangan'))
            ->assertCreated()
            ->assertJsonPath('data.zone.zone_name', 'Zona Perkebunan Tengah');
    }

    public function test_policy_maker_is_read_only(): void
    {
        $headers = $this->loginAs('kebijakan');

        $this->getJson('/api/animals', $headers)->assertOk();
        $this->getJson('/api/aggregate-reports', $headers)->assertOk();
        $this->getJson('/api/export/incidents/csv', $headers)->assertOk();
        $this->postJson('/api/incidents', [], $headers)->assertForbidden();
        $this->postJson('/api/ai-predictions/' . \App\Models\AiPrediction::value('prediction_id') . '/verify', [], $headers)->assertForbidden();
    }

    public function test_researcher_manages_master_data(): void
    {
        $this->postJson('/api/species', [
            'common_name' => 'Beruang Madu', 'scientific_name' => 'Helarctos malayanus', 'conservation_status' => 'Vulnerable',
        ], $this->loginAs('peneliti'))->assertCreated();
    }

    public function test_role_permission_assignment(): void
    {
        $headers = $this->loginAs('admin');

        $view = $this->idOf('Permission', 'name_permission', 'view_data');
        $create = $this->idOf('Permission', 'name_permission', 'create_data');
        $adminRole = $this->idOf('Role', 'name_role', 'administrator_sistem');

        $role = $this->postJson('/api/roles', ['name_role' => 'relawan', 'description' => 'Relawan', 'permission_ids' => [$view]], $headers)
            ->assertCreated()->json('data');
        $this->assertSame('relawan', $role['role_name']);

        $this->putJson("/api/roles/{$role['role_id']}/permissions", ['permission_ids' => [$view, $create]], $headers)
            ->assertOk()->assertJsonCount(2, 'data.permissions');
        $this->deleteJson("/api/roles/{$role['role_id']}/permissions/{$create}", [], $headers)->assertOk()->assertJsonCount(1, 'data.permissions');
        $this->deleteJson("/api/roles/{$adminRole}", [], $headers)->assertStatus(409);
    }

    public function test_user_crud_uses_role_id_relation(): void
    {
        $headers = $this->loginAs('admin');

        $user = $this->postJson('/api/users', [
            'username' => 'ranger01', 'full_name' => 'Ranger Satu', 'email' => 'ranger@wildlife.id',
            'password' => 'password123', 'role' => 'konservasionis_lapangan', 'address' => 'Kutacane',
        ], $headers)->assertCreated()
            ->assertJsonPath('data.name_role', 'konservasionis_lapangan')
            ->assertJsonPath('data.address', 'Kutacane')
            ->json('data');

        $this->patchJson("/api/users/{$user['user_id']}/status", ['status' => 'nonaktif'], $headers)->assertOk();
        $this->deleteJson("/api/users/{$user['user_id']}", [], $headers)->assertOk();
        $this->deleteJson('/api/users/' . $this->idOf('User', 'username', 'admin'), [], $headers)->assertStatus(422);
    }
}
