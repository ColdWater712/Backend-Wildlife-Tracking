<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notif_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('notification_type', ['risk_zone', 'device_alert', 'system']);
            $table->string('message', 500);
            $table->foreignId('animal_id')->nullable()
                ->constrained('animals', 'animal_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('zone_id')->nullable()
                ->constrained('geo_zones', 'zone_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_id')->nullable()
                ->constrained('tracking_devices', 'device_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('report_id')->nullable()
                ->constrained('incident_reports', 'report_id')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('prediction_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
