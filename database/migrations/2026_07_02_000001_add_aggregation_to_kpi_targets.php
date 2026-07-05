<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            // Jenis agregasi KPI:
            //   sum       = dept = Σ target/aktual staf (default, perilaku lama)
            //   average   = dept = rata-rata capaian staf
            //   shared    = target tim bersama (level dept, tanpa pecahan staf)
            //   milestone = progress 0–100% (level dept, tanpa angka absolut)
            $table->string('aggregation', 20)->default('sum')->after('kpi_level');
        });

        // Baris lama otomatis 'sum' via default — konsisten dgn perilaku sebelumnya.
    }

    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropColumn('aggregation');
        });
    }
};
