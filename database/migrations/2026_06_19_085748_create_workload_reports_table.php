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
        Schema::create('workload_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->integer('month');
            $table->integer('year');
            $table->integer('score')->nullable();
            $table->string('summary_flag')->nullable();
            $table->json('report_data')->nullable(); // Store the full JSON from Groq
            $table->timestamps();

            // Unique constraint so we only have one report per staff per month
            $table->unique(['staff_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workload_reports');
    }
};
