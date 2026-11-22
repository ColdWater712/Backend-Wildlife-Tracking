<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id('weather_id');
            $table->foreignId('reading_id')->nullable()
                ->constrained('gps_readings', 'reading_id')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 10, 6);
            $table->timestamp('observed_at');
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->decimal('rainfall_mm', 7, 2)->nullable();
            $table->decimal('humidity_percent', 5, 2)->nullable();
            $table->decimal('pressure_hpa', 7, 2)->nullable();
            $table->string('source', 50)->default('Open-Meteo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_data');
    }
};
