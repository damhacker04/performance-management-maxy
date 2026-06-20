<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_targets', function (Blueprint $table) {
            // Staf pemilik target (berbeda dari user_id yang = leader pembuat)
            // Nullable agar data lama (dummy/tim) tidak corrupt
            $table->foreignId('assigned_to')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('users')
                  ->onDelete('set null');

            // Acuan KPI departemen (loosely coupled, nullable)
            $table->foreignId('kpi_target_id')
                  ->nullable()
                  ->after('assigned_to')
                  ->constrained('kpi_targets')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['kpi_target_id']);
            $table->dropColumn(['assigned_to', 'kpi_target_id']);
        });
    }
};
