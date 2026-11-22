<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_devices', function (Blueprint $table) {
            $table->id('device_id');
            $table->enum('device_type', ['gps', 'camera', 'audio'])->default('gps')
                ->comment('gps = kalung pelacak, camera = kamera jebak, audio = perekam suara');
            $table->foreignId('animal_id')->nullable()->unique()
                ->constrained('animals', 'animal_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('zone_id')->nullable()
                ->constrained('geo_zones', 'zone_id')->nullOnDelete()->cascadeOnUpdate();
            $table->string('model', 100);
            $table->string('serial_number', 100)->nullable()->unique();
            $table->decimal('latitude', 9, 6)->nullable()->comment('lokasi pemasangan kamera/audio');
            $table->decimal('longitude', 10, 6)->nullable();
            $table->date('installation_date')->nullable();
            $table->unsignedSmallInteger('battery_life_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('device_condition', ['normal', 'damaged', 'lost'])->default('normal');
            $table->unsignedTinyInteger('battery_level')->nullable()->comment('persen 0-100');
            $table->unsignedTinyInteger('signal_strength')->nullable();
            $table->timestamp('last_recorded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_devices');
    }
};
