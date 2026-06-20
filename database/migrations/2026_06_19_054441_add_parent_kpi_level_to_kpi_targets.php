<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            // Hierarki KPI: L2 (dept) → L3 (staff individual)
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->tinyInteger('kpi_level')->default(2)->after('parent_id');
            // kpi_level: 2 = dept benchmark, 3 = staff individual target

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('kpi_targets')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'kpi_level']);
        });
    }
};
