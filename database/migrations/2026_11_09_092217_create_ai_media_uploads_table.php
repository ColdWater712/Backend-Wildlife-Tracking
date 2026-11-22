<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_media_uploads', function (Blueprint $table) {
            $table->id('media_id');
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('report_id')->nullable()->comment('opsional: laporan kejadian terkait')
                ->constrained('incident_reports', 'report_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_id')->nullable()->comment('kamera / perekam audio sumber media')
                ->constrained('tracking_devices', 'device_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('zone_id')->nullable()
                ->constrained('geo_zones', 'zone_id')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('media_type', ['image', 'video', 'audio']);
            $table->string('file_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('byte');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_media_uploads');
    }
};
