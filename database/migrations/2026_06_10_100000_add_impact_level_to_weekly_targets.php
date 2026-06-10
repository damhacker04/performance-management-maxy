<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_targets', function (Blueprint $table) {
            // Tingkat dampak bisnis yang di-set oleh Leader saat membuat target.
            // Nilai ini diwariskan ke Daily Task terkait untuk penilaian AI.
            $table->enum('impact_level', ['high', 'medium', 'low'])
                  ->default('medium')
                  ->after('category')
                  ->comment('Dampak bisnis target ini: high/medium/low. Ditentukan oleh Leader.');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_targets', function (Blueprint $table) {
            $table->dropColumn('impact_level');
        });
    }
};
