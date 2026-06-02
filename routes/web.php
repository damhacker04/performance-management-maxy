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

// ============================================================
// Panel Admin — hanya bisa diakses oleh Super Admin (HR)
// ============================================================
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\TargetAssignmentController;

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {

    // Manajemen User (Karyawan)
    Route::resource('users', UserManagementController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])
        ->name('users.toggle-active');

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
});


Route::get('/migrate-now', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
        '--seed' => true,
        '--force' => true
    ]);
    return 'Database migrated and seeded successfully! You can now log in.';
});

// Route rahasia untuk mengupdate Role Bu Ika menjadi Leader di Railway tanpa menghapus data
Route::get('/update-user-roles', function() {
    \Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'InitialUserSeeder',
        '--force' => true
    ]);
    return 'Berhasil memperbarui Role User di Railway! Bu Ika sekarang adalah Leader.';
});

// Developer Login Route (Local Only)
if (app()->environment('local')) {
    Route::get('/dev/impersonate/{role}', function ($role) {
        // Cari user dengan role tersebut, utamakan yang di departemen operational
        $user = \App\Models\User::where('role', $role)->where('department', 'operational')->first();
        
        // Jika tidak ketemu di operational, cari bebas
        if (!$user) {
            $user = \App\Models\User::where('role', $role)->first();
        }

        if (!$user) {
            // Jika user tidak ada sama sekali, coba buatkan dummy
            $user = \App\Models\User::create([
                'name' => ucfirst($role) . ' Dummy',
                'email' => $role . '@maxy.academy',
                'password' => bcrypt('password'),
                'role' => $role,
                'department' => 'operational', // ganti ke operational
            ]);
        } else {
            // Paksa update department ke operational agar testing tidak bocor/berbeda
            if ($user->department !== 'operational' && str_contains($user->email, 'dummy')) {
                $user->update(['department' => 'operational']);
            }
        }
        
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect()->route('dashboard')->with('success', "Berhasil login sebagai {$user->name} ({$role})!");
    })->name('dev.impersonate');
}

require __DIR__ . '/auth.php';
