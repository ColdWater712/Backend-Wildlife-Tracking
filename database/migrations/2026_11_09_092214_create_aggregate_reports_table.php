<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aggregate_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->enum('report_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('generated_at')->useCurrent();
            $table->string('file_path', 500)->nullable();
            
            $table->foreign('generated_by')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_reports');
    }
};