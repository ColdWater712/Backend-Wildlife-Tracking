<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete()->cascadeOnUpdate();
            $table->string('action', 100);
            $table->timestamp('timestamp')->useCurrent();
            $table->text('details')->nullable();
            $table->index(['user_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
