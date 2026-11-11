<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password', 255)->comment('hash bcrypt/argon2');
            $table->enum('role', ['konservasionis_lapangan', 'peneliti_ekologi', 'administrator_sistem', 'pengambil_kebijakan']);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};