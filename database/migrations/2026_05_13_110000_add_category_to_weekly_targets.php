<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P2 — Tambah kolom category ('planned' | 'other') di weekly_targets.
 *
 * Alasan: saat ini penentuan apakah weekly target adalah "Other" hanya dilakukan
 * dengan mengecek NULL pada monthly_target_id. Ini ambigu — NULL bisa terjadi
 * karena berbagai sebab. Kolom `category` yang eksplisit memudahkan:
 *   - Filtering "planned vs unplanned" di dashboard leader
 *   - Query analytics yang lebih bersih
 *   - Extensibility di masa depan (misal: category 'urgent', 'compliance', dll.)
 *
 * Backfill logic:
 *   - monthly_target_id IS NULL  → category = 'other'
 *   - monthly_target_id NOT NULL → category = 'planned'
 *
 * Referensi rapat 12 Mei 2026:
 *   "Management bisa melihat: pekerjaan planned dan pekerjaan unplanned/additional."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_targets', function (Blueprint $table) {
            $table->enum('category', ['planned', 'other'])
                ->default('planned')
                ->after('monthly_target_id')
                ->comment('planned = terkait monthly target; other = ad-hoc/tidak ada di planning');
        });

        // Backfill: weekly target yang monthly_target_id-nya NULL → 'other'
        DB::table('weekly_targets')
            ->whereNull('monthly_target_id')
            ->update(['category' => 'other']);

        // Yang sudah punya monthly_target_id → 'planned' (sudah default, tapi eksplisit)
        DB::table('weekly_targets')
            ->whereNotNull('monthly_target_id')
            ->update(['category' => 'planned']);
    }

    public function down(): void
    {
        Schema::table('weekly_targets', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
