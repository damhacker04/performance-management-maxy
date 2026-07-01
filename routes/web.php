<?php

use App\Http\Controllers\Admin\AdminKpiController;
use App\Http\Controllers\Admin\AdminOverviewController;
use App\Http\Controllers\Admin\AdminTargetController;
use App\Http\Controllers\Admin\KpiSettingsController;
use App\Http\Controllers\Admin\TargetAssignmentController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AiEvaluationController;
use App\Http\Controllers\CeoOverviewController;
use App\Http\Controllers\CeoTargetController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BackdateRequestController;
use App\Http\Controllers\DailyTaskEntryController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\LeaderTargetController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffTargetController;
use App\Http\Controllers\WeeklyTargetController;
use App\Http\Controllers\WorkloadReportController;
use App\Models\WeeklyTarget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Google Auth Routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// Routes untuk Leader
Route::middleware(['auth'])->group(function () {

    // Dashboard — tampilkan beranda sesuai role
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Dashboard monitoring C-Level (pengganti page target lama untuk C-Level)
    Route::middleware('role:c_level')->group(function () {
        Route::get('/ceo/overview', [CeoOverviewController::class, 'index'])->name('ceo.overview');

        // Halaman Target khusus C-Level: target yang ditetapkan untuk leader + drill-down read-only.
        Route::get('/ceo/targets', [CeoTargetController::class, 'index'])->name('ceo.targets.index');
        Route::get('/ceo/targets/leader/{leader}', [CeoTargetController::class, 'showLeader'])->name('ceo.targets.leader');
    });

    // Monthly Target, Weekly Target, KPI — hanya Leader & C-Level
    Route::get('/debug/unassigned-targets', function () {
        if (! app()->environment('production') && ! app()->environment('local')) {
            // Just a precaution, but we want it available to debug
        }

        $targets = WeeklyTarget::with('monthlyTarget')
            ->whereDoesntHave('dailyTaskEntries')
            ->get(['id', 'title', 'monthly_target_id', 'assigned_to', 'user_id']);

        $html = '<h1>Debug: Target Mingguan Kosong</h1>';
        $html .= "<table border='1' cellpadding='8' style='border-collapse:collapse; width:100%;'>";
        $html .= '<tr><th>ID Mingguan</th><th>Judul Target Mingguan</th><th>Assigned To</th><th>ID Bulanan</th><th>Judul Target Bulanan Lama</th></tr>';

        foreach ($targets as $t) {
            $monthlyTitle = $t->monthlyTarget ? $t->monthlyTarget->title : 'N/A';
            $assigned = $t->assigned_to ?: 'NULL (Umum)';
            $html .= '<tr>';
            $html .= "<td>{$t->id}</td>";
            $html .= "<td>{$t->title}</td>";
            $html .= "<td>{$assigned}</td>";
            $html .= "<td>{$t->monthly_target_id}</td>";
            $html .= "<td>{$monthlyTitle}</td>";
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    });

    // Menu independent sesuai notul 12 Mei 2026: Monthly & Weekly dipisah
    Route::middleware(['role:leader,c_level'])->group(function () {
        Route::resource('monthly-targets', MonthlyTargetController::class)->except(['show']);

        // ── PERIOD HIERARCHY (URL bersih & konsisten) ────────────────────────────
        // Level 2: /monthly-targets/period/{year}/{month}/staff
        // Level 3: /monthly-targets/period/{year}/{month}/staff/{staff}
        // Level 4: /monthly-targets/period/{year}/{month}/staff/{staff}/{monthlyTarget}
        // Level 5: /monthly-targets/period/{year}/{month}/staff/{staff}/{monthlyTarget}/{weeklyTarget}
        Route::prefix('monthly-targets/period/{year}/{month}')->group(function () {
            Route::get('staff', [MonthlyTargetController::class, 'staffListForMonth'])
                ->name('period.staff-list');
            Route::get('staff/{staff}', [MonthlyTargetController::class, 'staffTargetsForPeriod'])
                ->name('period.staff-targets');
            Route::get('staff/{staff}/{monthlyTarget}', [MonthlyTargetController::class, 'showStaffInPeriod'])
                ->name('period.staff-weekly');
            Route::get('staff/{staff}/{monthlyTarget}/{weeklyTarget}', [WeeklyTargetController::class, 'showInPeriod'])
                ->name('period.weekly-show');
        });


        Route::get('staff/{staff}/monthly-targets', [MonthlyTargetController::class, 'staffMonthlyTargets'])
            ->name('staff.monthly-targets'); // deprecated \u2014 redirect ke period.staff-targets
        // (period.staff-list sudah mencakup ini, lihat prefix group di atas)

        // Weekly Target — standalone resource (BUKAN nested).
        // Bisa linked ke monthly target tertentu via query ?monthly_target_id=X.
        Route::resource('weekly-targets', WeeklyTargetController::class);

        // Leader Target — read-only view: target yang dibuat C-Level untuk dept leader.
        // Sesuai notul 12 Mei 2026: leader juga punya target dari C-Level & input daily task.
        Route::get('/leader-targets', [LeaderTargetController::class, 'index'])->name('leader-targets.index');
        Route::get('/leader-targets/{monthlyTarget}', [LeaderTargetController::class, 'show'])->name('leader-targets.show');

        // KPI — Leader/Staff view-only (daftar benchmark departemen)
        Route::get('/kpi', [KpiController::class, 'index'])->name('kpi');

        // Kelola KPI (CRUD target & actual) — hanya C-Level, Super Admin, Admin HR.
        // Otorisasi terpusat lewat Gate `manage-kpi` (lihat AppServiceProvider).
        Route::middleware('can:manage-kpi')->group(function () {
            Route::get('/kpi/create', [KpiController::class, 'create'])->name('kpi.create');
            Route::post('/kpi', [KpiController::class, 'store'])->name('kpi.store');
            Route::get('/kpi/{kpiTarget}/edit', [KpiController::class, 'edit'])->name('kpi.edit');
            Route::put('/kpi/{kpiTarget}', [KpiController::class, 'update'])->name('kpi.update');
            Route::delete('/kpi/{kpiTarget}', [KpiController::class, 'destroy'])->name('kpi.destroy');

            // KPI L3 (Staff Individual) & KPI Actual
            Route::get('/kpi/staff/create', [KpiController::class, 'createStaffKpi'])->name('kpi.staff.create');
            Route::post('/kpi/staff', [KpiController::class, 'storeStaffKpi'])->name('kpi.staff.store');
            Route::get('/kpi/actuals', [KpiController::class, 'indexActuals'])->name('kpi.actuals.index');
            Route::get('/kpi/actuals/create', [KpiController::class, 'createActual'])->name('kpi.actuals.create');
            Route::post('/kpi/actuals', [KpiController::class, 'storeActual'])->name('kpi.actuals.store');
            Route::get('/kpi/actuals/{kpiActual}/edit', [KpiController::class, 'editActual'])->name('kpi.actuals.edit');
            Route::patch('/kpi/actuals/{kpiActual}', [KpiController::class, 'updateActual'])->name('kpi.actuals.update');
        });

        // AI Workload & Performance Report — C-Level, Admin HR, Leader
        Route::get('/workload-report', [WorkloadReportController::class, 'index'])->name('workload-report.index');
        Route::get('/workload-report/{staff}/{month}/{year}', [WorkloadReportController::class, 'show'])->name('workload-report.show');
        Route::post('/workload-report/generate', [WorkloadReportController::class, 'generateReport'])->name('workload-report.generate');
        Route::post('/workload-report/generate-batch', [WorkloadReportController::class, 'generateBatch'])->name('workload-report.generateBatch');
    });

    // Daily Task — Staff, Leader, dan C-Level semua bisa input laporan harian
    // (Leader & C-Level juga punya aktivitas harian yang perlu dilaporkan)
    Route::resource('daily-tasks', DailyTaskEntryController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/daily-tasks/{dailyTask}/complete', [DailyTaskEntryController::class, 'complete'])
        ->name('daily-tasks.complete');
    Route::patch('/daily-tasks/{dailyTask}/approve', [DailyTaskEntryController::class, 'approve'])
        ->name('daily-tasks.approve');
    Route::patch('/daily-tasks/{dailyTask}/revision', [DailyTaskEntryController::class, 'sendToRevision'])
        ->name('daily-tasks.revision');
    Route::patch('/daily-tasks/{dailyTask}/reject', [DailyTaskEntryController::class, 'reject'])
        ->name('daily-tasks.reject');
    Route::post('/daily-tasks/upload-clipboard', [DailyTaskEntryController::class, 'uploadClipboard'])
        ->middleware('throttle:30,1')
        ->name('daily-tasks.upload-clipboard');

    // Target view khusus Staff (read-only: lihat target bulanan & mingguan dept-nya)
    Route::middleware(['role:staff'])->group(function () {
        Route::get('/my-targets', [StaffTargetController::class, 'index'])->name('staff-targets.index');
        Route::get('/my-targets/{monthlyTarget}', [StaffTargetController::class, 'show'])->name('staff-targets.show');
    });

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Export Laporan — hanya c_level dan user dengan is_management = true
    // Guard dilakukan di controller via canExport() agar lebih fleksibel.
    Route::get('/export', [ExportController::class, 'index'])->name('export.index');
    Route::get('/export/download-excel', [ExportController::class, 'downloadExcel'])->name('export.download-excel');
    Route::get('/export/print', [ExportController::class, 'printView'])->name('export.print');

    // ── Backdating Request ────────────────────────────────────────────────────
    // Staf: ajukan permintaan izin backdating
    Route::get('/backdate-requests/create', [BackdateRequestController::class, 'create'])
        ->name('backdate-requests.create');
    Route::post('/backdate-requests', [BackdateRequestController::class, 'store'])
        ->name('backdate-requests.store');

    // Leader/C-Level/Super Admin: review permintaan
    Route::middleware('role:leader,c_level,super_admin')->group(function () {
        Route::get('/backdate-requests', [BackdateRequestController::class, 'index'])
            ->name('backdate-requests.index');
        Route::patch('/backdate-requests/{backdateRequest}/approve', [BackdateRequestController::class, 'approve'])
            ->name('backdate-requests.approve');
        Route::patch('/backdate-requests/{backdateRequest}/reject', [BackdateRequestController::class, 'reject'])
            ->name('backdate-requests.reject');
    });

    // ── Fase 2: AI Evaluation (hanya aktif jika GROQ_API_KEY di-set di .env) ────────
    if (! empty(config('services.groq.api_key'))) {
        // [AJAX] Validasi link publik/restricted secara real-time (dipanggil frontend)
        Route::post('/ai/validate-link', [AiEvaluationController::class, 'validateLink'])
            ->middleware('throttle:30,1')
            ->name('ai.validate-link');

        // Override nilai AI oleh Leader (Modal)
        Route::middleware('role:leader,c_level,super_admin')->group(function () {
            Route::get('/ai/evaluations/{evaluation}/override', [AiEvaluationController::class, 'showOverrideForm'])
                ->name('ai.evaluations.override.form');
            Route::post('/ai/evaluations/{evaluation}/override', [AiEvaluationController::class, 'storeOverride'])
                ->name('ai.evaluations.override.store');
        });
    }
});

// ============================================================
// Panel Admin — hanya bisa diakses oleh Super Admin (HR)
// ============================================================
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {

    // Manajemen User (Karyawan)
    Route::resource('users', UserManagementController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])
        ->name('users.toggle-active');

    // Halaman monitoring khusus Admin HR (versi sendiri, lepas dari halaman CEO).
    // Logika query dibagi via inheritance dari controller CEO/KPI.
    Route::get('overview', [AdminOverviewController::class, 'index'])->name('overview');
    Route::get('targets', [AdminTargetController::class, 'index'])->name('targets.index');
    Route::get('targets/leader/{leader}', [AdminTargetController::class, 'showLeader'])->name('targets.leader');
    Route::get('kpi', [AdminKpiController::class, 'index'])->name('kpi');

    // Assign Target ke Staff
    Route::get('target-assignment', [TargetAssignmentController::class, 'index'])
        ->name('target-assignment.index');
    Route::post('target-assignment/assign-weekly', [TargetAssignmentController::class, 'assignWeekly'])
        ->name('target-assignment.assign-weekly');
    Route::post('target-assignment/unassign-weekly', [TargetAssignmentController::class, 'unassignWeekly'])
        ->name('target-assignment.unassign-weekly');

    // Hapus Data (diaktifkan sementara per keputusan 2 Juni 2026)
    Route::delete('monthly-targets/{monthlyTarget}', [TargetAssignmentController::class, 'destroyMonthly'])
        ->name('monthly-targets.destroy');
    Route::delete('weekly-targets/{weeklyTarget}', [TargetAssignmentController::class, 'destroyWeekly'])
        ->name('weekly-targets.destroy');
    Route::delete('daily-tasks/{dailyTask}', [TargetAssignmentController::class, 'destroyDailyTask'])
        ->name('daily-tasks.destroy');

    // ── Fase 2: Panel KPI Settings & Override Log (hanya jika AI aktif) ──────────
    if (! empty(config('services.groq.api_key'))) {
        Route::get('kpi-settings', [KpiSettingsController::class, 'index'])
            ->name('kpi-settings.index');
        Route::post('kpi-settings', [KpiSettingsController::class, 'store'])
            ->name('kpi-settings.store');

        // Log histori Override oleh Leader (untuk monitoring manajemen)
        Route::get('ai/override-logs', [AiEvaluationController::class, 'overrideLogs'])
            ->name('ai.override-logs');
    }
});

// Route rahasia untuk menjalankan migration & seeder dengan aman di production
Route::get('/deploy-update', function () {
    try {
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        Artisan::call('db:seed', [
            '--class' => 'UserSeeder',
            '--force' => true,
        ]);

        return 'Berhasil! Database Production (Railway) sudah di-migrate dan seluruh akun (termasuk dummy) sudah di-seed dengan aman.';
    } catch (Exception $e) {
        return 'Terjadi Kesalahan (500): '.$e->getMessage().' <br>File: '.$e->getFile().' <br>Baris: '.$e->getLine();
    }
});

require __DIR__.'/auth.php';

Route::get('/debug/run-migration', function () {
    Artisan::call('app:migrate-legacy-targets');

    return "<pre style='background:#111; color:#0f0; padding:20px; font-size:14px; border-radius:8px; line-height:1.5; font-family:monospace;'>".
           "EXECUTING MIGRATION...\n\n".
           Artisan::output().
           '</pre>';
});

Route::get('/debug/logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (! file_exists($logFile)) {
        return 'No log file found.';
    }

    // Read last 100 lines
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);

    return "<pre style='background:#111; color:#fff; padding:10px; font-size:12px; white-space:pre-wrap;'>".htmlspecialchars(implode('', $lastLines)).'</pre>';
});
