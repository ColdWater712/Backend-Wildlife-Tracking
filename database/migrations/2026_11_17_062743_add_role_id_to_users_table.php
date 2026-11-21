<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hanya tambahkan kolom role_id jika benar-benar belum ada
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')
                      ->nullable()
                      ->after('id') // Sesuaikan posisi kolom jika perlu
                      ->constrained('roles')
                      ->onDelete('set null');
            });
        }

        // 2. Hanya jalankan pembaruan data JIKA kolom 'role' (string) masih ada
        // Ini mencegah error "Column not found" pada database fresh/new install
        if (Schema::hasColumn('users', 'role')) {
            // Mapping role string ke role_id (sesuaikan ID dengan data di tabel roles Anda)
            // Contoh: 1 = konservasionis, 2 = peneliti, 3 = admin
            DB::table('users')->where('role', 'konservasionis_lapangan')->whereNull('role_id')->update(['role_id' => 1]);
            DB::table('users')->where('role', 'peneliti_ekologi')->whereNull('role_id')->update(['role_id' => 2]);
            DB::table('users')->where('role', 'administrator_sistem')->whereNull('role_id')->update(['role_id' => 3]);

            // Opsional: Hapus kolom 'role' lama setelah data berhasil dipindahkan
            // Schema::table('users', function (Blueprint $table) {
            //     $table->dropColumn('role');
            // });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hanya drop foreign key dan kolom jika ada
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }
    }
};