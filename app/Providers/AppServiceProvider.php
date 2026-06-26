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
        // Dev (php artisan serve di Windows): cegah compiled Blade view terpotong
        // saat request di-abort di tengah kompilasi — penyebab ParseError
        // "unexpected end of file, expecting endif" yang berulang. Pemicunya
        // reload-storm dari Vite `refresh: true` saat banyak file blade diedit.
        // Dengan ignore_user_abort, request tetap menyelesaikan penulisan file
        // compiled meski browser keburu reload/navigasi. Lokal saja.
        if ($this->app->environment('local')) {
            ignore_user_abort(true);
        }

        // Set locale Carbon ke Bahasa Indonesia agar tanggal tampil dalam bahasa Indonesia
        Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');

        // Hak kelola KPI (CRUD target & actual) — C-Level, Super Admin, atau
        // user is_management. Satu sumber kebenaran, dipasang via middleware
        // `can:manage-kpi` di route. Staff/Leader tetap view-only.
        Gate::define('manage-kpi', fn (User $user) => $user->isExecutive() || $user->is_management);
    }
}
