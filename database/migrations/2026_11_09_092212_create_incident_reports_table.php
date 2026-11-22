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
            $table->foreignId('user_id')->nullable()->comment('pelapor')
                ->constrained('users', 'user_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('animal_id')->nullable()
                ->constrained('animals', 'animal_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('species_id')->nullable()
                ->constrained('species', 'species_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('zone_id')->nullable()
                ->constrained('geo_zones', 'zone_id')->nullOnDelete()->cascadeOnUpdate();
            $table->string('incident_type', 100)->comment('konflik, perambahan, perburuan, observasi, device_alert');
            $table->text('description');
            $table->timestamp('timestamp')->useCurrent();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('location', 255)->nullable()->comment('keterangan lokasi bebas');
            $table->string('photo_url', 500)->nullable();
            $table->string('status', 50)->default('pending')->comment('pending, investigating, resolved, dismissed');
            $table->enum('sync_status', ['pending_sync', 'synced', 'failed'])->default('pending_sync');
            $table->enum('source', ['manual', 'ai_camera', 'ai_audio'])->default('manual');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
