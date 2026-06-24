<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan BUG-01: migrasi index performa sebelumnya membuat index pada kolom
 * 'level' yang TIDAK ADA (kolom asli bernama 'kpi_level'), sehingga index gagal
 * dibuat diam-diam (dibungkus try/catch). Di sini kita buat index yang benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            if (! $this->indexExists('kpi_targets', 'kpi_klevel_dept')) {
                $table->index(['kpi_level', 'department'], 'kpi_klevel_dept');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropIndex('kpi_klevel_dept');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn ($i) => ($i['name'] ?? null) === $index);
    }
};
