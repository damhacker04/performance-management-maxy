<?php

namespace App\Console\Commands;

use App\Jobs\EvaluateDailyTaskJob;
use App\Models\DailyTaskEntry;
use Illuminate\Console\Command;

/**
 * Dispatch ulang EvaluateDailyTaskJob untuk Daily Task Entry yang belum punya
 * evaluasi AI. Menggantikan skrip lepas `dispatch-jobs.php` yang dulu ada di
 * root project. Idempoten — job sendiri sudah skip task yang sudah dievaluasi.
 */
class AiBackfillEvaluations extends Command
{
    protected $signature = 'ai:backfill-evaluations {--limit=50 : Jumlah maksimum entri yang diproses}';

    protected $description = 'Dispatch EvaluateDailyTaskJob untuk laporan harian yang belum memiliki evaluasi AI.';

    public function handle(): int
    {
        if (! ai_enabled()) {
            $this->warn('AI tidak aktif (GROQ_API_KEY belum di-set). Tidak ada yang di-dispatch.');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');

        $entries = DailyTaskEntry::whereDoesntHave('aiEvaluation')
            ->latest()
            ->take($limit)
            ->get();

        $this->info("Entri tanpa evaluasi AI: {$entries->count()}");

        foreach ($entries as $entry) {
            EvaluateDailyTaskJob::dispatch($entry->id)->onQueue('default');
            $this->line("Dispatched job untuk entri ID: {$entry->id}");
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
