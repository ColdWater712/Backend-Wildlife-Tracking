<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'konservasionis_lapangan' => ['view_data', 'create_data', 'update_data'],
            'peneliti_ekologi' => ['view_data', 'create_data', 'update_data', 'view_reports'],
            'administrator_sistem' => ['view_data', 'create_data', 'update_data', 'delete_data', 'manage_users', 'manage_roles', 'view_reports'],
            'pengambil_kebijakan' => ['view_data', 'view_reports'],
        ];

        foreach ($matrix as $roleName => $permNames) {
            $role = Role::where('role_name', $roleName)->first();
            if (!$role) continue;

            $permIds = Permission::whereIn('permission_name', $permNames)->pluck('permission_id')->toArray();
            $role->permissions()->sync($permIds);
        }
    }
}