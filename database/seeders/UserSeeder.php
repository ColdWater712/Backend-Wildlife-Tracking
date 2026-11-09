<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Sistem',
                'email' => 'admin@wildlife.id',
                'password' => Hash::make('password123'), // Password di-hash dengan aman
                'role' => 'administrator_sistem',
                'created_at' => now(),
            ],
            [
                'name' => 'Peneliti Ekologi',
                'email' => 'peneliti@wildlife.id',
                'password' => Hash::make('password123'),
                'role' => 'peneliti_ekologi',
                'created_at' => now(),
            ],
            [
                'name' => 'Konservasionis Lapangan',
                'email' => 'lapangan@wildlife.id',
                'password' => Hash::make('password123'),
                'role' => 'konservasionis_lapangan',
                'created_at' => now(),
            ],
            [
                'name' => 'Pengambil Kebijakan',
                'email' => 'kebijakan@wildlife.id',
                'password' => Hash::make('password123'),
                'role' => 'pengambil_kebijakan',
                'created_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}