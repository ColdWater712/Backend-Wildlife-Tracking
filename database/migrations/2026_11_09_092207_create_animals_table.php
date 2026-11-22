<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id('animal_id');
            $table->foreignId('species_id')
                ->constrained('species', 'species_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('name_animal', 100)->unique()->comment('kode/nama individu, mis. ORN-01');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
            $table->text('distinctive_marks')->nullable()->comment('ciri fisik pembeda untuk identifikasi AI');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
