<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_task_entry_id')
                  ->constrained('daily_task_entries')
                  ->onDelete('cascade');

            // Skor per dimensi (0.00 - 10.00)
            $table->decimal('score_achievement', 4, 2)->default(0)->comment('Skor pencapaian target (0-10)');
            $table->decimal('score_efficiency', 4, 2)->default(0)->comment('Skor efisiensi waktu (0-10)');
            $table->decimal('score_contribution', 4, 2)->default(0)->comment('Skor kontribusi bisnis (0-10)');
            $table->decimal('score_problem_solving', 4, 2)->default(0)->comment('Skor problem solving (0-10)');

            // Skor akhir terbobot (dihitung dari 4 dimensi x bobot KPI aktif)
            $table->decimal('final_score', 4, 2)->default(0)->comment('Skor akhir berbobot');

            // Narasi umpan balik dari AI
            $table->text('ai_feedback')->nullable()->comment('Narasi evaluasi dari Gemini');

            // Status link bukti kerja
            $table->enum('link_status', ['public', 'restricted', 'no_link', 'invalid'])
                  ->default('no_link')
                  ->comment('Status link yang dilampirkan staf');

            // Apakah skor sudah di-override oleh Leader
            $table->boolean('is_overridden')->default(false);

            // Raw response dari Gemini (untuk debugging jika diperlukan)
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluations');
    }
};
