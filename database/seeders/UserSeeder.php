<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil mapping role_name ke role_id dari database
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

        $users = [
            [
                'name' => 'Admin Sistem',
                'email' => 'admin@wildlife.id',
                'password' => Hash::make('password123'),
                'role_id' => $roles['administrator_sistem'], // Gunakan role_id
                'created_at' => now(),
            ],
            [
                'name' => 'Peneliti Ekologi',
                'email' => 'peneliti@wildlife.id',
                'password' => Hash::make('password123'),
                'role_id' => $roles['peneliti_ekologi'], // Perbaiki typo 'role-ID' menjadi 'role_id'
                'created_at' => now(),
            ],
            [
                'name' => 'Konservasionis Lapangan',
                'email' => 'lapangan@wildlife.id',
                'password' => Hash::make('password123'),
                'role_id' => $roles['konservasionis_lapangan'],
                'created_at' => now(),
            ],
            [
                'name' => 'Pengambil Kebijakan',
                'email' => 'kebijakan@wildlife.id',
                'password' => Hash::make('password123'),
                'role_id' => $roles['pengambil_kebijakan'],
                'created_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}