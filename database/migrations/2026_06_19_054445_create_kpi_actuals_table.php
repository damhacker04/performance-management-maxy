<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_actuals', function (Blueprint $table) {
            $table->id();

            // Referensi ke KPI Level 3 (target individu staf)
            $table->unsignedBigInteger('kpi_target_id');
            $table->foreign('kpi_target_id')
                  ->references('id')
                  ->on('kpi_targets')
                  ->onDelete('cascade');

            // Staf yang dinilai
            $table->unsignedBigInteger('staff_id');
            $table->foreign('staff_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Cache departemen untuk query cepat
            $table->string('department')->nullable();

            // Periode
            $table->tinyInteger('month');  // 1–12
            $table->smallInteger('year');

            // Nilai aktual realisasi (diinput C-Level/HR)
            $table->decimal('actual_value', 10, 2);

            // Sumber: manual input atau auto-detected dari task content
            $table->enum('source', ['manual', 'auto_detected'])->default('manual');

            // Catatan opsional dari C-Level/HR
            $table->text('notes')->nullable();

            // Siapa yang input
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->timestamps();

            // Satu kpi_actual per staf per KPI per bulan
            $table->unique(['kpi_target_id', 'staff_id', 'month', 'year'], 'kpi_actual_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_actuals');
    }
};
