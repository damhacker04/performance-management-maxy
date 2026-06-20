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
        $indexes = [
            'daily_task_entries' => [
                'dte_user_date' => ['user_id', 'task_date'],
                'dte_vstatus_date' => ['verification_status', 'task_date'],
                'dte_user_vstatus' => ['user_id', 'verification_status'],
                'dte_montarget_date' => ['monthly_target_id', 'task_date'],
                'dte_weektarget_date' => ['weekly_target_id', 'task_date'],
            ],
            'monthly_targets' => [
                'mt_dept_period' => ['department', 'month', 'year'],
                'mt_user_period' => ['user_id', 'month', 'year'],
            ],
            'weekly_targets' => [
                'wt_monthly_target' => ['monthly_target_id'],
                'wt_assigned_monthly' => ['assigned_to', 'monthly_target_id'],
            ],
            'notifications' => [
                'notif_user_created' => ['user_id', 'created_at'],
            ],
            'kpi_targets' => [
                'kpi_dept_period' => ['department', 'month', 'year'],
                'kpi_level_dept' => ['level', 'department'],
                'kpi_parent' => ['parent_id'],
            ],
            'kpi_actuals' => [
                'ka_staff_period' => ['staff_id', 'month', 'year'],
                'ka_dept_period' => ['department', 'month', 'year'],
            ],
            'workload_reports' => [
                'wr_period' => ['month', 'year'],
            ],
            'users' => [
                'users_dept_active' => ['department', 'is_active'],
                'users_role_active' => ['role', 'is_active'],
            ],
        ];

        foreach ($indexes as $tableName => $tableIndexes) {
            foreach ($tableIndexes as $indexName => $columns) {
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
                        $table->index($columns, $indexName);
                    });
                } catch (\Exception $e) {
                    // Abaikan jika index sudah ada (misal: gagal di tengah jalan saat deploy sebelumnya)
                }
            }
        }
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
