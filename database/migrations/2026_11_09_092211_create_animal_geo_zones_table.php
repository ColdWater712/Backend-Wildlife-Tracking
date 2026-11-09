<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_geo_zones', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_id')->autoIncrement();
            $table->unsignedInteger('animal_id');
            $table->unsignedInteger('zone_id');
            $table->unsignedBigInteger('entry_reading_id')->nullable();
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            
            $table->primary('visit_id');
            $table->foreign('animal_id')->references('animal_id')->on('animals')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('zone_id')->references('zone_id')->on('geo_zones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('entry_reading_id')->references('reading_id')->on('gps_readings')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_geo_zones');
    }
};