<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\WeeklyTargetController;
use App\Http\Controllers\DailyTaskEntryController;
use App\Http\Controllers\StaffTargetController;
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

    // Monthly Target & Weekly Target & KPI — hanya Leader & C-Level
    Route::middleware(['role:leader,c_level'])->group(function () {
        Route::resource('monthly-targets', MonthlyTargetController::class);

        // Weekly Targets — nested under monthly untuk index/create/store
        Route::get('monthly-targets/{monthlyTarget}/weekly-targets',
            [WeeklyTargetController::class, 'index'])->name('weekly-targets.index');
        Route::get('monthly-targets/{monthlyTarget}/weekly-targets/create',
            [WeeklyTargetController::class, 'create'])->name('weekly-targets.create');
        Route::post('monthly-targets/{monthlyTarget}/weekly-targets',
            [WeeklyTargetController::class, 'store'])->name('weekly-targets.store');

        // Shallow untuk show/edit/update/destroy (tidak perlu monthly id)
        Route::get('weekly-targets/{weeklyTarget}',
            [WeeklyTargetController::class, 'show'])->name('weekly-targets.show');
        Route::get('weekly-targets/{weeklyTarget}/edit',
            [WeeklyTargetController::class, 'edit'])->name('weekly-targets.edit');
        Route::patch('weekly-targets/{weeklyTarget}',
            [WeeklyTargetController::class, 'update'])->name('weekly-targets.update');
        Route::delete('weekly-targets/{weeklyTarget}',
            [WeeklyTargetController::class, 'destroy'])->name('weekly-targets.destroy');

        Route::get('/kpi', function () {
            return view('kpi');
        })->name('kpi');
    });

    // Daily Task & Target View — hanya Staff
    Route::middleware(['role:staff'])->group(function () {
        Route::resource('daily-tasks', DailyTaskEntryController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/daily-tasks/{dailyTask}/complete', [DailyTaskEntryController::class, 'complete'])
            ->name('daily-tasks.complete');

        // Target view untuk staff (read-only: lihat target bulanan & mingguan dept-nya)
        Route::get('/my-targets', [StaffTargetController::class, 'index'])->name('staff-targets.index');
        Route::get('/my-targets/{monthlyTarget}', [StaffTargetController::class, 'show'])->name('staff-targets.show');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
