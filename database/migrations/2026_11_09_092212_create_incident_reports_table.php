<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id('report_id'); // Primary key
            $table->string('incident_type');
            $table->text('description');
            $table->timestamp('timestamp');
            
            // ✅ KOLOM KOORDINAT DAN LOKASI (WAJIB ADA)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('location')->nullable();
            
            // ✅ FOREIGN KEYS
            $table->foreignId('animal_id')->nullable()->constrained('animals', 'animal_id')->nullOnDelete();
            $table->foreignId('species_id')->nullable()->constrained('species', 'species_id')->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users', 'user_id')->cascadeOnDelete();
            
            $table->string('status')->default('pending');
            $table->string('sync_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};