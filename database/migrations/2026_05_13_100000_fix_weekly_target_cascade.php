<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Fix cascade delete weekly_target_id.
 *
 * Sebelumnya: WeeklyTarget::booted() melakukan manual delete pada dailyTaskEntries.
 * Akibatnya: semua laporan harian staff hilang ketika leader menghapus weekly target.
 * Ini berbahaya untuk data historis KPI.
 *
 * Solusi:
 * - Hapus cascade behavior dari Model (dilakukan di WeeklyTarget.php).
 * - Di MySQL: drop FK lama lalu re-add dengan ON DELETE SET NULL.
 *   weekly_target_id di daily_task_entries sudah NULLABLE (migration 2026_05_11_130000),
 *   jadi perubahan ini aman dan tidak membutuhkan data migration.
 *
 * Referensi rapat 12 Mei 2026:
 *   "Sistem harus membantu evaluasi objektif, bukan sekadar checklist target."
 *   → Data task yang sudah disubmit tidak boleh hilang hanya karena target dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Cari nama foreign key yang ada
            $fkName = $this->getForeignKeyName();

            if ($fkName) {
                DB::statement("ALTER TABLE daily_task_entries DROP FOREIGN KEY `{$fkName}`");
            }

            // Re-add dengan ON DELETE SET NULL
            DB::statement("
                ALTER TABLE daily_task_entries
                ADD CONSTRAINT fk_dte_weekly_target
                FOREIGN KEY (weekly_target_id)
                REFERENCES weekly_targets(id)
                ON DELETE SET NULL
            ");
        }
        // SQLite: tidak support ALTER FK — behavior dihandle di model level (booted() dihapus).
        // SQLite secara default tidak enforce FK kecuali PRAGMA foreign_keys = ON.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE daily_task_entries DROP FOREIGN KEY fk_dte_weekly_target");

            // Restore ke CASCADE (behavior lama)
            DB::statement("
                ALTER TABLE daily_task_entries
                ADD CONSTRAINT fk_dte_weekly_target_cascade
                FOREIGN KEY (weekly_target_id)
                REFERENCES weekly_targets(id)
                ON DELETE CASCADE
            ");
        }
    }

    /**
     * Cari nama FK aktual di database (bisa berbeda-beda tergantung Laravel versi & driver).
     */
    private function getForeignKeyName(): ?string
    {
        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'daily_task_entries'
              AND COLUMN_NAME = 'weekly_target_id'
              AND REFERENCED_TABLE_NAME = 'weekly_targets'
            LIMIT 1
        ");

        return $result[0]->CONSTRAINT_NAME ?? null;
    }
};
