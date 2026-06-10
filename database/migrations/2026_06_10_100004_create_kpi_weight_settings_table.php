<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_weight_settings', function (Blueprint $table) {
            $table->id();

            // Jika department_id null = berlaku global untuk semua departemen
            $table->unsignedBigInteger('department_id')->nullable()
                  ->comment('Null = berlaku global, diisi = khusus departemen ini');

            // Bobot 4 dimensi (total harus = 100%)
            $table->decimal('weight_achievement', 5, 2)->default(25.00)
                  ->comment('Bobot dimensi Pencapaian Target (%)');
            $table->decimal('weight_efficiency', 5, 2)->default(25.00)
                  ->comment('Bobot dimensi Efisiensi Waktu (%)');
            $table->decimal('weight_contribution', 5, 2)->default(25.00)
                  ->comment('Bobot dimensi Kontribusi Bisnis (%)');
            $table->decimal('weight_problem_solving', 5, 2)->default(25.00)
                  ->comment('Bobot dimensi Problem Solving (%)');

            // Siapa yang menyetting dan kapan berlaku
            $table->foreignId('set_by')->constrained('users')->onDelete('cascade')
                  ->comment('ID HR/Admin yang menyimpan pengaturan ini');
            $table->date('effective_from')->comment('Tanggal mulai berlaku pengaturan bobot ini');

            // Flag apakah ini setting yang aktif
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_weight_settings');
    }
};
