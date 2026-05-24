<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyTaskEntry;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;

class AutoRejectExpiredRevisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:auto-reject-revisions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menolak laporan harian yang batas waktu revisinya (10 jam) sudah habis.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan laporan revisi yang kedaluwarsa...');

        // Cari semua laporan dengan status 'revision' yang melewati 10 jam sejak di-review
        // Karena canBeRevised() adalah (reviewed_at + 10 jam) > now
        // Maka yang kedaluwarsa adalah (reviewed_at + 10 jam) <= now
        // Alias reviewed_at <= now - 10 jam
        $expiredLimit = Carbon::now()->subHours(10);

        $expiredEntries = DailyTaskEntry::where('verification_status', 'revision')
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '<=', $expiredLimit)
            ->get();

        $count = 0;

        foreach ($expiredEntries as $entry) {
            $entry->update([
                'verification_status' => 'rejected',
                'rejection_note'      => 'Otomatis ditolak karena melewati batas waktu revisi (10 jam).'
            ]);

            // Kirim notifikasi ke pembuat laporan
            NotificationHelper::send(
                $entry->user_id,
                'auto_rejected',
                'Batas Waktu Revisi Habis',
                'Laporan "' . \Illuminate\Support\Str::limit($entry->task_description, 40) . '" otomatis ditolak karena melebihi batas waktu 10 jam.',
                $entry->id,
                null // metadata
            );

            $count++;
        }

        $this->info("Pengecekan selesai. Sebanyak {$count} laporan otomatis ditolak.");
    }
}
