<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'konservasionis_lapangan', 'description' => 'Petugas konservasi di lapangan'],
            ['role_name' => 'peneliti_ekologi', 'description' => 'Peneliti yang menganalisis data ekologi dan satwa'],
            ['role_name' => 'administrator_sistem', 'description' => 'Administrator yang mengelola sistem dan pengguna'],
            ['role_name' => 'pengambil_kebijakan', 'description' => 'Pengambil kebijakan yang memantau laporan dan ringkasan'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['role_name' => $role['role_name']], $role);
        }
    }
}