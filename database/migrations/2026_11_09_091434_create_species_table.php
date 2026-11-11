<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id('species_id');
            $table->string('common_name', 100);
            $table->string('scientific_name', 150)->unique();
            $table->string('conservation_status', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};