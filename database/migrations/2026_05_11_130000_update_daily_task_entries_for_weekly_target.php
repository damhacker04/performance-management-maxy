<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Catatan: data lama (test) akan kehilangan referensi weekly_target_id
        // karena tabel weekly_targets baru saja dibuat. Karena di Railway data
        // masih dummy, OK untuk dibiarkan NULL — staff bisa re-submit.

        Schema::table('daily_task_entries', function (Blueprint $table) {
            // Link ke weekly target (nullable agar tidak break data lama)
            if (!Schema::hasColumn('daily_task_entries', 'weekly_target_id')) {
                $table->foreignId('weekly_target_id')
                    ->nullable()
                    ->after('monthly_target_id')
                    ->constrained('weekly_targets')
                    ->nullOnDelete();
            }

            // Priority: critical / high / medium / low
            if (!Schema::hasColumn('daily_task_entries', 'priority')) {
                $table->enum('priority', ['critical', 'high', 'medium', 'low'])
                    ->default('medium')
                    ->after('task_description');
            }

            // Durasi aktual (yang diisi staff saat update). Estimasi tetap di duration_minutes.
            if (!Schema::hasColumn('daily_task_entries', 'actual_duration_minutes')) {
                $table->integer('actual_duration_minutes')
                    ->nullable()
                    ->after('duration_minutes');
            }

            // Progress 0-100
            if (!Schema::hasColumn('daily_task_entries', 'percent_done')) {
                $table->unsignedTinyInteger('percent_done')
                    ->default(0)
                    ->after('status');
            }
        });

        // Update enum status untuk menambah 'belum_mulai'.
        // SQLite tidak punya ENUM/MODIFY — di SQLite cukup biarkan kolom string,
        // validasi enum sudah dihandle di level Laravel (Rule::in STATUSES).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE daily_task_entries MODIFY COLUMN status
                ENUM('belum_mulai', 'dalam_proses', 'terhambat', 'selesai')
                NOT NULL DEFAULT 'belum_mulai'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE daily_task_entries MODIFY COLUMN status
                ENUM('selesai', 'dalam_proses', 'terhambat') NOT NULL");
        }

        Schema::table('daily_task_entries', function (Blueprint $table) {
            $table->dropForeign(['weekly_target_id']);
            $table->dropColumn(['weekly_target_id', 'priority', 'actual_duration_minutes', 'percent_done']);
        });
    }
};
