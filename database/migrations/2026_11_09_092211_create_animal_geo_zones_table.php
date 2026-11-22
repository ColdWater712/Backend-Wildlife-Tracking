<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_geo_zones', function (Blueprint $table) {
            $table->id('visit_id');
            $table->foreignId('animal_id')
                ->constrained('animals', 'animal_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('zone_id')
                ->constrained('geo_zones', 'zone_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('entry_reading_id')->nullable()
                ->constrained('gps_readings', 'reading_id')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            $table->index(['animal_id', 'zone_id', 'exited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_geo_zones');
    }
};
