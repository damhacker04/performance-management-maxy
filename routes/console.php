<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:clean-dummy', function () {
    $this->warn('⚠️  Ini akan menghapus SEMUA data laporan, target mingguan, dan target bulanan.');
    $this->warn('   Akun user TIDAK akan terhapus.');
    $this->newLine();

    if (! $this->confirm('Lanjutkan?', false)) {
        $this->info('Dibatalkan.');
        return;
    }

    $isMysql = DB::getDriverName() === 'mysql';

    if ($isMysql) {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    } else {
        DB::statement('PRAGMA foreign_keys = OFF;');
    }

    DB::table('daily_task_entries')->truncate();
    DB::table('weekly_targets')->truncate();
    DB::table('monthly_targets')->truncate();

    if ($isMysql) {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    } else {
        DB::statement('PRAGMA foreign_keys = ON;');
    }

    $this->newLine();
    $this->info('✅  Data berhasil dibersihkan:');
    $this->line('   • daily_task_entries → 0 rows');
    $this->line('   • weekly_targets     → 0 rows');
    $this->line('   • monthly_targets    → 0 rows');
    $this->line('   • users              → tetap (tidak diubah)');
})->purpose('Hapus semua data dummy (target & laporan), users tetap aman');

// Jalankan auto-reject setiap jam
Schedule::command('tasks:auto-reject-revisions')->hourly();
