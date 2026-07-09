<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag arah KPI: true = "lebih kecil lebih baik" (Dropout Rate, Bug Rate,
 * Turnaround Time, CPL, dst.). Rumus capaian % untuk KPI ini dibalik —
 * actual ≤ target berarti 100%, melebihi target berarti proporsional turun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->boolean('lower_is_better')->default(false)->after('aggregation');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropColumn('lower_is_better');
        });
    }
};
