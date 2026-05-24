<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\WeeklyTargetController;
use App\Http\Controllers\DailyTaskEntryController;
use App\Http\Controllers\StaffTargetController;
use App\Http\Controllers\LeaderTargetController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Google Auth Routes
Route::get('/auth/google', [App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('google.callback');

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

    // Notifikasi
    Route::get('/notifications',                    [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read',[NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all',          [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Export Laporan — hanya c_level dan user dengan is_management = true
    // Guard dilakukan di controller via canExport() agar lebih fleksibel.
    Route::get('/export',               [ExportController::class, 'index'])->name('export.index');
    Route::get('/export/download-excel',[ExportController::class, 'downloadExcel'])->name('export.download-excel');
    Route::get('/export/print',         [ExportController::class, 'printView'])->name('export.print');
});

// Temporary route for Railway DB Migration without CLI
Route::get('/migrate-now', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
        '--seed' => true,
        '--force' => true
    ]);
    return 'Database migrated and seeded successfully! You can now log in.';
});

// Developer Login Route (Local Only)
if (app()->environment('local')) {
    Route::get('/dev/impersonate/{role}', function ($role) {
        $user = \App\Models\User::where('role', $role)->first();
        if (!$user) {
            // Jika user tidak ada, coba buatkan dummy sementara agar tidak error
            $user = \App\Models\User::create([
                'name' => ucfirst($role) . ' Dummy',
                'email' => $role . '@maxy.academy',
                'password' => bcrypt('password'),
                'role' => $role,
                'department' => 'product_it', // default department
            ]);
        }
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect()->route('dashboard')->with('success', "Berhasil login sebagai {$role}!");
    })->name('dev.impersonate');
}

require __DIR__ . '/auth.php';
