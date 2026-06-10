<?php

namespace App\Jobs;

use App\Models\GapAnalysisReport;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateMonthlyGapAnalysisJob — Dijalankan di akhir bulan untuk
 * Monthly Target yang tidak tercapai.
 *
 * AI membaca seluruh Gap Analysis Mingguan di bulan itu dan
 * menghasilkan laporan strategis "Helicopter View" untuk C-Level.
 * AI TIDAK membaca Daily Task satu per satu (agar tidak noisy).
 */
class GenerateMonthlyGapAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(
        public readonly int $monthlyTargetId
    ) {}

    public function handle(GeminiService $gemini): void
    {
        $monthly = MonthlyTarget::with(['user'])->find($this->monthlyTargetId);

        if (!$monthly) {
            Log::warning("GenerateMonthlyGapAnalysisJob: MonthlyTarget ID {$this->monthlyTargetId} tidak ditemukan.");
            return;
        }

        // Hindari duplikasi
        if ($monthly->gapAnalysisReport()->exists()) {
            return;
        }

        $department = $monthly->user?->department ?? 'Umum';

        // Ambil semua Weekly Target di bawah Monthly Target ini beserta gap analysis-nya
        $weeklyTargets = WeeklyTarget::where('monthly_target_id', $monthly->id)
            ->with('gapAnalysisReport')
            ->get();

        // Hanya ambil yang punya gap analysis (yang gagal)
        $weeklyGapSummaries = $weeklyTargets
            ->filter(fn($w) => $w->gapAnalysisReport !== null)
            ->map(fn($w) => [
                'root_cause_type' => $w->gapAnalysisReport->root_cause_type,
                'narrative'       => $w->gapAnalysisReport->narrative,
            ])
            ->values()
            ->toArray();

        if (empty($weeklyGapSummaries)) {
            Log::info("GenerateMonthlyGapAnalysisJob: Tidak ada weekly gap analysis untuk monthly ID {$this->monthlyTargetId}");
            return;
        }

        $monthlyData = [
            'title'  => $monthly->title,
            'period' => \Carbon\Carbon::create($monthly->year, $monthly->month, 1)->isoFormat('MMMM YYYY'),
        ];

        // Panggil Gemini AI
        $result = $gemini->generateMonthlyGapAnalysis($monthlyData, $weeklyGapSummaries, $department);

        if (!$result) {
            Log::error("GenerateMonthlyGapAnalysisJob: Gemini gagal untuk monthly ID {$this->monthlyTargetId}");
            return;
        }

        // Simpan laporan strategis
        GapAnalysisReport::create([
            'reportable_type' => MonthlyTarget::class,
            'reportable_id'   => $monthly->id,
            'root_cause_type' => $result['root_cause_type'],
            'narrative'       => $result['narrative'],
            'recommendation'  => $result['recommendation'],
            'tasks_analyzed'  => count($weeklyGapSummaries),
            'generated_at'    => now(),
        ]);

        Log::info("GenerateMonthlyGapAnalysisJob: Selesai untuk MonthlyTarget ID {$monthly->id}");
    }
}
