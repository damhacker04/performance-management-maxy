<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gap_analysis_reports', function (Blueprint $table) {
            $table->id();

            // Polymorphic: bisa untuk WeeklyTarget atau MonthlyTarget
            $table->string('reportable_type')->comment('App\\Models\\WeeklyTarget atau App\\Models\\MonthlyTarget');
            $table->unsignedBigInteger('reportable_id')->comment('ID target yang gagal');
            $table->index(['reportable_type', 'reportable_id']);

            // Klasifikasi akar masalah oleh AI
            $table->enum('root_cause_type', ['internal', 'external', 'mixed'])
                  ->default('mixed')
                  ->comment('internal=salah staf, external=sistem/birokrasi, mixed=keduanya');

            // Narasi analisis & rekomendasi dari Gemini
            $table->text('narrative')->comment('Ringkasan investigasi AI tentang mengapa target gagal');
            $table->text('recommendation')->nullable()->comment('Rekomendasi strategis dari AI untuk manajemen');

            // Metadata proses
            $table->integer('tasks_analyzed')->default(0)->comment('Jumlah Daily Task yang dianalisis');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gap_analysis_reports');
    }
};
