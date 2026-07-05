<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // KPI jenis shared/milestone diukur di level departemen (tanpa per-staf),
        // jadi actual-nya disimpan dengan staff_id = null.
        Schema::table('kpi_actuals', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kpi_actuals', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable(false)->change();
        });
    }
};
