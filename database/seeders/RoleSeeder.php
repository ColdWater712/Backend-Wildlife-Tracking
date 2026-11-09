<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'konservasionis_lapangan', 'description' => 'Petugas konservasi di lapangan', 'created_at' => now()],
            ['role_name' => 'peneliti_ekologi', 'description' => 'Peneliti yang menganalisis data ekologi dan satwa', 'created_at' => now()],
            ['role_name' => 'administrator_sistem', 'description' => 'Administrator yang mengelola sistem dan pengguna', 'created_at' => now()],
            ['role_name' => 'pengambil_kebijakan', 'description' => 'Pengambil kebijakan yang memantau laporan dan ringkasan', 'created_at' => now()],
        ];

        DB::table('roles')->insert($roles);
    }
}