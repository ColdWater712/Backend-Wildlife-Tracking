<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_readings', function (Blueprint $table) {
            $table->unsignedBigInteger('reading_id')->autoIncrement();
            $table->unsignedInteger('device_id');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 10, 6);
            $table->timestamp('recorded_at');
            $table->decimal('altitude_meters', 8, 2)->nullable();
            $table->decimal('accuracy_meters', 6, 2)->nullable();
            $table->char('checksum', 64)->nullable()->comment('SHA-256 payload');
            
            $table->primary('reading_id');
            $table->unique(['device_id', 'recorded_at']);
            $table->foreign('device_id')->references('device_id')->on('tracking_devices')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_readings');
    }
};