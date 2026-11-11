<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('pelapor');
            $table->string('incident_type', 50)->comment('konflik, perambahan, observasi');
            $table->string('animal_code', 50)->nullable();
            $table->string('location', 255);
            $table->text('description');
            $table->timestamp('timestamp')->useCurrent();
            $table->string('photo_url', 500)->nullable();
            $table->enum('sync_status', ['pending_sync', 'synced', 'failed'])->default('pending_sync');
            
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};