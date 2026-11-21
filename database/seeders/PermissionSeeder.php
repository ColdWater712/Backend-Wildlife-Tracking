<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['permission_name' => 'view_data', 'description' => 'Melihat data satwa, pelacakan, dan konservasi'],
            ['permission_name' => 'create_data', 'description' => 'Menambahkan data baru'],
            ['permission_name' => 'update_data', 'description' => 'Mengubah data yang sudah ada'],
            ['permission_name' => 'delete_data', 'description' => 'Menghapus data'],
            ['permission_name' => 'manage_users', 'description' => 'Mengelola akun pengguna'],
            ['permission_name' => 'manage_roles', 'description' => 'Mengelola role dan permission'],
            ['permission_name' => 'view_reports', 'description' => 'Melihat laporan dan ringkasan'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['permission_name' => $perm['permission_name']], $perm);
        }
    }
}