<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('notif_id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->enum('notification_type', ['risk_zone', 'device_alert', 'system']);
            $table->string('message', 500);
            $table->unsignedInteger('animal_id')->nullable();
            $table->unsignedInteger('zone_id')->nullable();
            $table->unsignedInteger('device_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            $table->primary('notif_id');
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('animal_id')->references('animal_id')->on('animals')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('zone_id')->references('zone_id')->on('geo_zones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('device_id')->references('device_id')->on('tracking_devices')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};