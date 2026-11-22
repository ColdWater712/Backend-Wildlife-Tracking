<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** Sumber kebenaran role & permission (permission tidak dikelola lewat API). */
class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'view_data' => 'Melihat data satwa, pelacakan, dan konservasi',
        'create_data' => 'Menambahkan data lapangan (insiden, GPS, kunjungan zona)',
        'update_data' => 'Mengubah data yang sudah ada',
        'delete_data' => 'Menghapus data',
        'manage_users' => 'Mengelola akun pengguna dan melihat audit log',
        'manage_roles' => 'Mengelola role dan permission',
        'view_reports' => 'Melihat dan membuat laporan agregat',
        'manage_master_data' => 'Mengelola spesies, satwa, perangkat, dan zona',
        'upload_media' => 'Mengunggah media untuk dianalisis AI',
        'verify_ai' => 'Memverifikasi dan mengulang prediksi AI',
        'export_data' => 'Mengekspor data (CSV/GeoJSON)',
    ];

    public const ROLES = [
        'konservasionis_lapangan' => [
            'description' => 'Petugas konservasi di lapangan',
            'permissions' => ['view_data', 'create_data', 'update_data', 'upload_media', 'verify_ai'],
        ],
        'peneliti_ekologi' => [
            'description' => 'Peneliti yang menganalisis data ekologi dan satwa',
            'permissions' => ['view_data', 'create_data', 'update_data', 'view_reports', 'manage_master_data',
                'upload_media', 'verify_ai', 'export_data'],
        ],
        'administrator_sistem' => [
            'description' => 'Administrator yang mengelola sistem dan pengguna',
            'permissions' => ['*'],
        ],
        'pengambil_kebijakan' => [
            'description' => 'Pengambil kebijakan yang memantau laporan dan ringkasan',
            'permissions' => ['view_data', 'view_reports', 'upload_media', 'export_data'],
        ],
    ];

    public function run(): void
    {
        $ids = [];
        foreach (self::PERMISSIONS as $name => $description) {
            $ids[$name] = Permission::updateOrCreate(['name_permission' => $name], ['description' => $description])->permission_id;
        }

        foreach (self::ROLES as $name => $config) {
            $role = Role::updateOrCreate(['name_role' => $name], ['description' => $config['description']]);
            $names = $config['permissions'] === ['*'] ? array_keys($ids) : $config['permissions'];
            $role->permissions()->sync(array_map(fn ($n) => $ids[$n], $names));
        }
    }
}
