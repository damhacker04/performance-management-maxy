<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Drop kolom percent_done dari daily_task_entries.
 *
 * Alasan (Rapat 12 Mei 2026 + reasoning PM/SA):
 * - Persen progress numerik tidak meaningful; subjektif dan tidak bisa diaudit.
 * - Diganti dengan narasi wajib di kolom `notes` untuk semua status.
 * - Untuk task multi-week (Operasional/GA: procurement, HR hiring), progress
 *   akan dimodelkan ulang via `due_date` (lihat P1 roadmap), bukan persentase.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('daily_task_entries', 'percent_done')) {
            Schema::table('daily_task_entries', function (Blueprint $table) {
                $table->dropColumn('percent_done');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('daily_task_entries', 'percent_done')) {
            Schema::table('daily_task_entries', function (Blueprint $table) {
                $table->unsignedTinyInteger('percent_done')->default(0)->after('status');
            });
        }
    }
};
