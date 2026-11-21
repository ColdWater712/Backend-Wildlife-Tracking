<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil mapping role_name ke role_id dari database
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

        $users = [
<<<<<<< HEAD
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
=======
            ['name' => 'Admin Sistem', 'email' => 'admin@wildlife.id', 'role' => 'administrator_sistem'],
            ['name' => 'Peneliti Ekologi', 'email' => 'peneliti@wildlife.id', 'role' => 'peneliti_ekologi'],
            ['name' => 'Konservasionis Lapangan', 'email' => 'lapangan@wildlife.id', 'role' => 'konservasionis_lapangan'],
            ['name' => 'Pengambil Kebijakan', 'email' => 'kebijakan@wildlife.id', 'role' => 'pengambil_kebijakan'],
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d
        ];

        foreach ($users as $u) {
            $role = Role::where('role_name', $u['role'])->first();
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'password' => Hash::make('password123'),
                    'role' => $u['role'],
                    'role_id' => $role?->role_id,
                ]
            );
        }
    }
}