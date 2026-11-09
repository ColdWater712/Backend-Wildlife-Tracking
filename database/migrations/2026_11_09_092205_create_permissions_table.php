<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->unsignedInteger('permission_id')->autoIncrement();
            $table->string('permission_name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->primary('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};