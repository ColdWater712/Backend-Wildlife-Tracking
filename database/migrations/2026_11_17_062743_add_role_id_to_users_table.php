<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom role_id (nullable dulu)
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable()->after('role');
            });
        }

        // Migrasi data: mapping enum role ke role_id
        $roleMapping = [
            'konservasionis_lapangan' => 1,
            'peneliti_ekologi' => 2,
            'administrator_sistem' => 3,
            'pengambil_kebijakan' => 4,
        ];

        foreach ($roleMapping as $roleName => $roleId) {
            DB::table('users')
                ->where('role', $roleName)
                ->whereNull('role_id')
                ->update(['role_id' => $roleId]);
        }

        // Tambah foreign key
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
            });
        } catch (\Exception $e) {
            // Abaikan jika foreign key belum ada
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')
                  ->references('role_id')
                  ->on('roles')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
            });
        } catch (\Exception $e) {
            // Abaikan
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });
    }
};