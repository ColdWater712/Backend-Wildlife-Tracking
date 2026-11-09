<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_name', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->primary('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};