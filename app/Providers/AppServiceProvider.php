<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Set locale Carbon ke Bahasa Indonesia agar tanggal tampil dalam bahasa Indonesia
        Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');

        // Hak kelola KPI (CRUD target & actual) — C-Level, Super Admin, atau
        // user is_management. Satu sumber kebenaran, dipasang via middleware
        // `can:manage-kpi` di route. Staff/Leader tetap view-only.
        Gate::define('manage-kpi', fn (User $user) => $user->isExecutive() || $user->is_management);
    }
}
