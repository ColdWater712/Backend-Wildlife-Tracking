<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('log_id')->autoIncrement();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->timestamp('timestamp')->useCurrent();
            $table->text('details')->nullable();
            
            $table->primary('log_id');
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};