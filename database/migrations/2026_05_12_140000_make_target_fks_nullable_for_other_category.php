<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Support kategori "Other" sesuai notul Rapat 12 Mei 2026.
 *
 * Perubahan:
 * 1. weekly_targets.monthly_target_id → nullable
 *    Alasan: Weekly Target bisa "Other" (tidak terikat monthly target manapun)
 *
 * 2. daily_task_entries.monthly_target_id → nullable
 *    Alasan: Daily task "Other" (ad-hoc dari CEO/CTO) tidak punya parent monthly target
 *
 * Note: daily_task_entries.weekly_target_id sudah nullable sejak migration
 * 2026_05_11_130000, jadi tidak perlu diubah.
 *
 * Strategy SQLite: rebuild table (Laravel handles via change()).
 * Strategy MySQL: ALTER COLUMN dengan DBAL atau raw SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL: drop foreign key dulu, modify column, lalu re-add FK
            // Lebih aman pakai raw SQL untuk handle existing FK constraints
            DB::statement('ALTER TABLE weekly_targets MODIFY monthly_target_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE daily_task_entries MODIFY monthly_target_id BIGINT UNSIGNED NULL');
        } else {
            // SQLite: pakai Laravel Schema (will rebuild table internally)
            Schema::table('weekly_targets', function ($table) {
                $table->unsignedBigInteger('monthly_target_id')->nullable()->change();
            });

            Schema::table('daily_task_entries', function ($table) {
                $table->unsignedBigInteger('monthly_target_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak rollback ke NOT NULL — bisa data loss kalau sudah ada record dengan NULL
    }
};
