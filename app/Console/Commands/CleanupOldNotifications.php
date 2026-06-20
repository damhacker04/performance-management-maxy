<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppNotification;
use Carbon\Carbon;

class CleanupOldNotifications extends Command
{
    /**
     * Perintah artisan: php artisan notifications:cleanup
     * Tujuan: Hapus notifikasi yang sudah dibaca dan berumur > 90 hari,
     * agar tabel notifications tidak menumpuk di production.
     */
    protected $signature   = 'notifications:cleanup {--days=90 : Hapus notifikasi yang sudah lebih dari N hari}';
    protected $description = 'Hapus notifikasi lama yang sudah dibaca (default: > 90 hari)';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $threshold = Carbon::now()->subDays($days);

        // Hanya hapus yang sudah read_at terisi (sudah dibaca)
        // agar notifikasi yang belum dibaca tidak hilang
        $deleted = AppNotification::where('read_at', '<=', $threshold)
            ->where('created_at', '<=', $threshold)
            ->delete();

        $this->info("✅ Cleanup selesai: {$deleted} notifikasi lama dihapus (threshold: {$days} hari).");

        return Command::SUCCESS;
    }
}
