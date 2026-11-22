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
            $table->foreignId('media_id')
                ->constrained('ai_media_uploads', 'media_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('analysis_type', ['image', 'audio'])->default('image');

            // Identifikasi spesies
            $table->foreignId('predicted_species_id')->nullable()
                ->constrained('species', 'species_id')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('confidence', 5, 4)->nullable()->comment('0.0000 - 1.0000');
            $table->string('detected_label', 100)->nullable()->comment('label mentah dari model');
            $table->unsignedSmallInteger('individual_count')->nullable();

            // Identifikasi individu satwa (berdasarkan lokasi kamera, GPS, riwayat zona)
            $table->foreignId('predicted_animal_id')->nullable()
                ->constrained('animals', 'animal_id')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('animal_match_confidence', 5, 4)->nullable();
            $table->string('animal_match_method', 50)->nullable();

            // Kondisi fisik & perilaku
            $table->string('physical_condition', 30)->nullable()->comment('healthy, injured, sick, emaciated, unknown');
            $table->text('condition_notes')->nullable();
            $table->string('behavior', 50)->nullable()->comment('foraging, resting, moving, running, drinking, social, parenting, mating, alert, aggressive, unknown');
            $table->text('behavior_notes')->nullable();

            // Analisis suara
            $table->string('sound_category', 30)->nullable()->comment('animal_call, gunshot, chainsaw, tree_falling, fire, engine, vehicle, human_voice, dog_bark, environment, unknown');
            $table->string('sound_label', 100)->nullable();

            // Ancaman & ringkasan
            $table->enum('threat_level', ['none', 'low', 'medium', 'high', 'critical'])->default('none');
            $table->json('detected_objects')->nullable();
            $table->text('ai_summary')->nullable();
            $table->json('raw_response')->nullable();

            $table->string('model_version', 100);
            $table->enum('processing_status', ['queued', 'processing', 'completed', 'failed', 'unrecognized'])->default('queued');
            $table->timestamp('processed_at')->nullable();
            $table->enum('verification_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreignId('verified_species_id')->nullable()->comment('spesies benar menurut petugas')
                ->constrained('species', 'species_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('verified_animal_id')->nullable()->comment('individu benar menurut petugas')
                ->constrained('animals', 'animal_id')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('verified_by')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('verified_at')->nullable();

            $table->unique(['media_id', 'model_version']);
            $table->index(['threat_level', 'processed_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign('prediction_id')->references('prediction_id')->on('ai_predictions')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['prediction_id']);
        });
        Schema::dropIfExists('ai_predictions');
    }
};
