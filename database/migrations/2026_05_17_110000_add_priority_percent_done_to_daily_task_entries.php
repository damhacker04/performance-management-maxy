<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom priority dan percent_done ke daily_task_entries.
 *
 * Berdasarkan format output spreadsheet yang diminta Fanny (17 Mei 2026):
 * - priority : level urgensi task (low / medium / high / critical)
 * - percent_done : progress per-task 0–100 (berbeda dari percent_done monthly yang sudah dihapus)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_task_entries', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                      ->default('medium')
                      ->after('notes');
            }
            if (!Schema::hasColumn('daily_task_entries', 'percent_done')) {
                $table->unsignedTinyInteger('percent_done')
                      ->default(0)
                      ->after('priority')
                      ->comment('0–100, progress per task');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            $table->dropColumn(['priority', 'percent_done']);
        });
    }
};
