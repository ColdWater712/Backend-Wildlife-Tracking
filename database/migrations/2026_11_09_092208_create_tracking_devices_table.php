<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_devices', function (Blueprint $table) {
            $table->unsignedInteger('device_id')->autoIncrement();
            $table->unsignedInteger('animal_id')->nullable()->unique();
            $table->string('model', 100);
            $table->date('installation_date')->nullable();
            $table->unsignedSmallInteger('battery_life_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('device_condition', ['normal', 'damaged', 'lost'])->default('normal');
            $table->unsignedTinyInteger('battery_level')->nullable()->comment('persen 0-100');
            $table->unsignedTinyInteger('signal_strength')->nullable();
            $table->timestamp('last_recorded_at')->nullable();
            
            $table->primary('device_id');
            $table->foreign('animal_id')->references('animal_id')->on('animals')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_devices');
    }
};