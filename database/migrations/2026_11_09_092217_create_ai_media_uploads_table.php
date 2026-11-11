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
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('report_id')->nullable()->comment('opsional: laporan kejadian terkait');
            $table->enum('media_type', ['image', 'video']);
            $table->string('file_path', 500);
            $table->timestamp('uploaded_at')->useCurrent();
            
            $table->foreign('uploaded_by')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('report_id')->references('report_id')->on('incident_reports')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_media_uploads');
    }
};