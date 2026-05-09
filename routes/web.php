<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\DailyTaskEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// TEMPORARY: trigger seeder once on Railway. Remove after first use.
Route::get('/__seed-users-once-9k3m2', function () {
    \Artisan::call('db:seed', ['--class' => 'UserSeeder', '--force' => true]);
    return 'Seeded. Users count: ' . \App\Models\User::count();
});

// Routes untuk Leader
Route::middleware(['auth'])->group(function () {

    // Dashboard — tampilkan beranda sesuai role
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Monthly Target & KPI — hanya Leader & C-Level
    Route::middleware(['role:leader,c_level'])->group(function () {
        Route::resource('monthly-targets', MonthlyTargetController::class);
        Route::get('/kpi', function () {
            return view('kpi');
        })->name('kpi');
    });

    // Daily Task — hanya Staff
    Route::middleware(['role:staff'])->group(function () {
        Route::resource('daily-tasks', DailyTaskEntryController::class)
            ->only(['index', 'create', 'store']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';