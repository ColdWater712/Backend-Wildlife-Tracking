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
        $users = [
            ['name' => 'Admin Sistem', 'email' => 'admin@wildlife.id', 'role' => 'administrator_sistem'],
            ['name' => 'Peneliti Ekologi', 'email' => 'peneliti@wildlife.id', 'role' => 'peneliti_ekologi'],
            ['name' => 'Konservasionis Lapangan', 'email' => 'lapangan@wildlife.id', 'role' => 'konservasionis_lapangan'],
            ['name' => 'Pengambil Kebijakan', 'email' => 'kebijakan@wildlife.id', 'role' => 'pengambil_kebijakan'],
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