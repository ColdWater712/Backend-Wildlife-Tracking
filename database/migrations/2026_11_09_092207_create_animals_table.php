<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->unsignedInteger('animal_id')->autoIncrement();
            $table->unsignedInteger('species_id');
            $table->string('name', 100);
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
            $table->boolean('is_active')->default(true);
            
            $table->primary('animal_id');
            $table->foreign('species_id')->references('species_id')->on('species')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};