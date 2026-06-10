<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leader_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_evaluation_id')
                  ->constrained('ai_evaluations')
                  ->onDelete('cascade');
            $table->foreignId('overridden_by')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('ID Leader yang mengubah nilai');

            // Skor sebelum dan sesudah override
            $table->decimal('original_score', 4, 2)->comment('Skor final AI sebelum diubah');
            $table->decimal('new_score', 4, 2)->comment('Skor baru yang ditetapkan Leader');

            // Alasan wajib diisi
            $table->text('reason')->comment('Alasan Leader mengubah nilai AI (wajib diisi)');

            $table->timestamp('overridden_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leader_overrides');
    }
};
