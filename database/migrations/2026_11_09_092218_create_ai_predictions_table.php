<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id('prediction_id');
            $table->unsignedBigInteger('media_id');
            $table->unsignedBigInteger('predicted_species_id')->nullable();
            $table->decimal('confidence', 5, 4)->nullable()->comment('0.0000 - 1.0000');
            $table->string('model_version', 50);
            $table->enum('processing_status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->timestamp('processed_at')->nullable();
            $table->enum('verification_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->unsignedBigInteger('verified_species_id')->nullable()->comment('spesies benar menurut petugas');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            $table->unique(['media_id', 'model_version']);
            $table->foreign('media_id')->references('media_id')->on('ai_media_uploads')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('predicted_species_id')->references('species_id')->on('species')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('verified_species_id')->references('species_id')->on('species')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('verified_by')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_predictions');
    }
};