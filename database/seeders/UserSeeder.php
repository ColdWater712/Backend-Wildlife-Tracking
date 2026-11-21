<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // ✅ Ditambahkan agar DB::table bisa dipakai
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil mapping role_name ke role_id dari database
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

        $users = [
            ['full_name' => 'Admin Sistem', 'email' => 'admin@wildlife.id', 'role' => 'administrator_sistem'],
            ['full_name' => 'Peneliti Ekologi', 'email' => 'peneliti@wildlife.id', 'role' => 'peneliti_ekologi'],
            ['full_name' => 'Konservasionis Lapangan', 'email' => 'lapangan@wildlife.id', 'role' => 'konservasionis_lapangan'],
            ['full_name' => 'Pengambil Kebijakan', 'email' => 'kebijakan@wildlife.id', 'role' => 'pengambil_kebijakan'],
        ];

        foreach ($users as $u) {
            // Cari role berdasarkan role_name
            $role = Role::where('role_name', $u['role'])->first();
            
            // Generate username otomatis dari email (misal: admin@wildlife.id -> admin)
            // Ini wajib karena kolom 'name' sudah kita ganti menjadi 'username' di migrasi
            $username = Str::before($u['email'], '@');

            // ✅ updateOrCreate sesuai permintaan Anda, dengan penyesuaian nama kolom agar tidak error
            User::updateOrCreate(
                ['email' => $u['email']], // Kondisi pencarian
                [
                    'username'  => $username,             // ✅ Disesuaikan dengan kolom di database
                    'full_name' => $u['full_name'],       // ✅ Disesuaikan dengan kolom di database (bukan 'name')
                    'password'  => Hash::make('password123'),
                    'role_id'   => $role?->role_id,       // ✅ Menggunakan role_id (Foreign Key)
                    'status'    => 'aktif',               // ✅ Wajib ada agar user bisa login
                ]
            );
        }
    }
}