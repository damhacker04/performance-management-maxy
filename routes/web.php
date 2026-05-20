<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\WeeklyTargetController;
use App\Http\Controllers\DailyTaskEntryController;
use App\Http\Controllers\StaffTargetController;
use App\Http\Controllers\LeaderTargetController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Routes untuk Leader
Route::middleware(['auth'])->group(function () {

    // Dashboard — tampilkan beranda sesuai role
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Monthly Target, Weekly Target, KPI — hanya Leader & C-Level
    // Menu independent sesuai notul 12 Mei 2026: Monthly & Weekly dipisah
    Route::middleware(['role:leader,c_level'])->group(function () {
        Route::resource('monthly-targets', MonthlyTargetController::class);

        // Weekly Target — standalone resource (BUKAN nested).
        // Bisa linked ke monthly target tertentu via query ?monthly_target_id=X.
        Route::resource('weekly-targets', WeeklyTargetController::class);

        // Leader Target — read-only view: target yang dibuat C-Level untuk dept leader.
        // Sesuai notul 12 Mei 2026: leader juga punya target dari C-Level & input daily task.
        Route::get('/leader-targets', [LeaderTargetController::class, 'index'])->name('leader-targets.index');
        Route::get('/leader-targets/{monthlyTarget}', [LeaderTargetController::class, 'show'])->name('leader-targets.show');

        Route::get('/kpi', function () {
            return view('kpi');
        })->name('kpi');
    });

    // Daily Task — Staff, Leader, dan C-Level semua bisa input laporan harian
    // (Leader & C-Level juga punya aktivitas harian yang perlu dilaporkan)
    Route::resource('daily-tasks', DailyTaskEntryController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/daily-tasks/{dailyTask}/complete',  [DailyTaskEntryController::class, 'complete'])
        ->name('daily-tasks.complete');
    Route::patch('/daily-tasks/{dailyTask}/approve',   [DailyTaskEntryController::class, 'approve'])
        ->name('daily-tasks.approve');
    Route::patch('/daily-tasks/{dailyTask}/revision',  [DailyTaskEntryController::class, 'sendToRevision'])
        ->name('daily-tasks.revision');
    Route::patch('/daily-tasks/{dailyTask}/reject',    [DailyTaskEntryController::class, 'reject'])
        ->name('daily-tasks.reject');

    // Target view khusus Staff (read-only: lihat target bulanan & mingguan dept-nya)
    Route::middleware(['role:staff'])->group(function () {
        Route::get('/my-targets', [StaffTargetController::class, 'index'])->name('staff-targets.index');
        Route::get('/my-targets/{monthlyTarget}', [StaffTargetController::class, 'show'])->name('staff-targets.show');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Export Laporan — hanya c_level dan user dengan is_management = true
    // Guard dilakukan di controller via canExport() agar lebih fleksibel.
    Route::get('/export',               [ExportController::class, 'index'])->name('export.index');
    Route::get('/export/download-excel',[ExportController::class, 'downloadExcel'])->name('export.download-excel');
    Route::get('/export/print',         [ExportController::class, 'printView'])->name('export.print');
});

require __DIR__ . '/auth.php';
