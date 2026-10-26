<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission (penting!)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // === BUAT PERMISSIONS ===
        Permission::create(['name' => 'view species']);
        Permission::create(['name' => 'create species']);
        Permission::create(['name' => 'edit species']);
        Permission::create(['name' => 'delete species']);

        Permission::create(['name' => 'view tracking devices']);
        Permission::create(['name' => 'manage tracking devices']);

        Permission::create(['name' => 'view visitors']);
        Permission::create(['name' => 'manage visitors']);

        Permission::create(['name' => 'view tickets']);
        Permission::create(['name' => 'manage tickets']);

        Permission::create(['name' => 'view reports']);
        Permission::create(['name' => 'manage users']);

        // === BUAT ROLES ===
        $admin = Role::create(['name' => 'admin']);
        $staff = Role::create(['name' => 'staff']);
        $visitor = Role::create(['name' => 'visitor']);

        // === ASSIGN PERMISSIONS KE ROLES ===
        
        // Admin: semua permission
        $admin->givePermissionTo(Permission::all());

        // Staff: bisa lihat & kelola species, tracking devices, visitors, tickets
        $staff->givePermissionTo([
            'view species',
            'create species',
            'edit species',
            'view tracking devices',
            'manage tracking devices',
            'view visitors',
            'manage visitors',
            'view tickets',
            'manage tickets',
            'view reports',
        ]);

        // Visitor: hanya bisa lihat
        $visitor->givePermissionTo([
            'view species',
            'view tracking devices',
            'view visitors',
            'view tickets',
            'view reports',
        ]);

        // === BUAT USER CONTOH ===
        $adminUser = User::create([
            'name' => 'Admin Wildlife',
            'email' => 'admin@wildlife.com',
            'password' => bcrypt('password123'),
        ]);
        $adminUser->assignRole('admin');

        $staffUser = User::create([
            'name' => 'Staff Wildlife',
            'email' => 'staff@wildlife.com',
            'password' => bcrypt('password123'),
        ]);
        $staffUser->assignRole('staff');

        $visitorUser = User::create([
            'name' => 'Visitor Biasa',
            'email' => 'visitor@wildlife.com',
            'password' => bcrypt('password123'),
        ]);
        $visitorUser->assignRole('visitor');

        $this->command->info('✅ Roles, permissions, dan users berhasil dibuat!');
    }
}