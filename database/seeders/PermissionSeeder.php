<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['permission_id' => 1, 'permission_name' => 'view_data', 'description' => 'Melihat data satwa, pelacakan, dan konservasi', 'created_at' => '2026-10-19 10:00:00'],
            ['permission_id' => 2, 'permission_name' => 'create_data', 'description' => 'Menambahkan data baru', 'created_at' => '2026-10-19 10:00:00'],
            ['permission_id' => 3, 'permission_name' => 'update_data', 'description' => 'Mengubah data yang sudah ada', 'created_at' => '2026-10-19 10:00:00'],
            ['permission_id' => 4, 'permission_name' => 'delete_data', 'description' => 'Menghapus data', 'created_at' => '2026-10-19 10:00:00'],
            ['permission_id' => 5, 'permission_name' => 'manage_users', 'description' => 'Mengelola akun pengguna', 'created_at' => '2026-10-19 10:00:00'],
            ['permission_id' => 6, 'permission_name' => 'manage_roles', 'description' => 'Mengelola role dan permission', 'created_at' => '2026-10-19 10:00:00'],
            ['permission_id' => 7, 'permission_name' => 'view_reports', 'description' => 'Melihat laporan dan ringkasan', 'created_at' => '2026-10-19 10:00:00'],
        ]);
    }
}