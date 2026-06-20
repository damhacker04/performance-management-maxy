<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * PERFORMANCE INDEXES — Scalability Migration
 * Dibuat: 2026-06-19
 *
 * Tujuan:
 * Menambahkan composite index pada kolom-kolom yang paling sering digunakan
 * dalam WHERE clause dan ORDER BY, agar query tetap cepat saat data
 * mencapai puluhan ribu hingga ratusan ribu baris (1–3 tahun ke depan).
 *
 * Referensi query yang dilindungi oleh index ini:
 *  - Dashboard: pending review per departemen
 *  - Workload Report: summary per bulan/tahun
 *  - Notifikasi: unread per user hari ini
 *  - KPI: per departemen per periode
 * ─────────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. daily_task_entries ────────────────────────────────────────────
        // Tabel paling kritikal — bisa mencapai 70.000+ baris dalam 2 tahun.
        Schema::table('daily_task_entries', function (Blueprint $table) {

            // Query: "ambil semua laporan milik user X pada tanggal Y"
            // Dipakai di: DailyTaskEntryController::index(), dashboard staff
            $table->index(['user_id', 'task_date'], 'dte_user_date');

            // Query: "ambil semua laporan pending di departemen X"
            // Dipakai di: dashboard leader (card "Menunggu Review")
            $table->index(['verification_status', 'task_date'], 'dte_vstatus_date');

            // Query: "laporan milik user X yang statusnya pending/revision"
            // Dipakai di: notifikasi, auto-reject scheduler
            $table->index(['user_id', 'verification_status'], 'dte_user_vstatus');

            // Query: "laporan untuk monthly_target X" (eager loading check)
            // Dipakai di: MonthlyTargetController::show()
            $table->index(['monthly_target_id', 'task_date'], 'dte_montarget_date');

            // Query: "laporan untuk weekly_target X"
            $table->index(['weekly_target_id', 'task_date'], 'dte_weektarget_date');
        });

        // ─── 2. monthly_targets ───────────────────────────────────────────────
        // Query: "target departemen X bulan Y tahun Z"
        // Dipakai di: dashboard, workload report summary
        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->index(['department', 'month', 'year'], 'mt_dept_period');
            $table->index(['user_id', 'month', 'year'], 'mt_user_period');
        });

        // ─── 3. weekly_targets ────────────────────────────────────────────────
        // Query: "weekly target milik monthly target X"
        Schema::table('weekly_targets', function (Blueprint $table) {
            $table->index(['monthly_target_id'], 'wt_monthly_target');
            $table->index(['assigned_to', 'monthly_target_id'], 'wt_assigned_monthly');
        });

        // ─── 4. notifications (app_notifications) ────────────────────────────
        // Sudah ada index ['user_id', 'read_at'] dari migration awal.
        // Tambah index untuk query "notifikasi hari ini yang belum dibaca"
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'notif_user_created');
        });

        // ─── 5. kpi_targets ───────────────────────────────────────────────────
        // Query: "KPI untuk departemen X periode Y"
        // Dipakai di: KpiController::index(), workload report AI context
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->index(['department', 'month', 'year'], 'kpi_dept_period');
            $table->index(['level', 'department'], 'kpi_level_dept');
            $table->index(['parent_id'], 'kpi_parent');
        });

        // ─── 6. kpi_actuals ───────────────────────────────────────────────────
        // Query: "realisasi KPI staf X bulan Y"
        Schema::table('kpi_actuals', function (Blueprint $table) {
            $table->index(['staff_id', 'month', 'year'], 'ka_staff_period');
            $table->index(['department', 'month', 'year'], 'ka_dept_period');
        });

        // ─── 7. workload_reports ──────────────────────────────────────────────
        // Sudah ada unique(['staff_id', 'month', 'year']) dari migration awal.
        // Unique constraint otomatis membuat index — tidak perlu tambah lagi.
        // Tambah index untuk query "semua laporan bulan Y departemen X"
        Schema::table('workload_reports', function (Blueprint $table) {
            $table->index(['month', 'year'], 'wr_period');
        });

        // ─── 8. users ─────────────────────────────────────────────────────────
        // Query: "semua staf aktif di departemen X"
        // Dipakai di: hampir semua controller (leader view, batch generate)
        Schema::table('users', function (Blueprint $table) {
            $table->index(['department', 'is_active'], 'users_dept_active');
            $table->index(['role', 'is_active'], 'users_role_active');
        });
    }

    public function down(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            $table->dropIndex('dte_user_date');
            $table->dropIndex('dte_vstatus_date');
            $table->dropIndex('dte_user_vstatus');
            $table->dropIndex('dte_montarget_date');
            $table->dropIndex('dte_weektarget_date');
        });

        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->dropIndex('mt_dept_period');
            $table->dropIndex('mt_user_period');
        });

        Schema::table('weekly_targets', function (Blueprint $table) {
            $table->dropIndex('wt_monthly_target');
            $table->dropIndex('wt_assigned_monthly');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notif_user_created');
        });

        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropIndex('kpi_dept_period');
            $table->dropIndex('kpi_level_dept');
            $table->dropIndex('kpi_parent');
        });

        Schema::table('kpi_actuals', function (Blueprint $table) {
            $table->dropIndex('ka_staff_period');
            $table->dropIndex('ka_dept_period');
        });

        Schema::table('workload_reports', function (Blueprint $table) {
            $table->dropIndex('wr_period');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_dept_active');
            $table->dropIndex('users_role_active');
        });
    }
};
