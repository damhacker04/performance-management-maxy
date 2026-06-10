<?php

namespace App\Jobs;

use App\Models\GapAnalysisReport;
use App\Models\WeeklyTarget;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateWeeklyGapAnalysisJob — Dijalankan otomatis ketika sebuah
 * Weekly Target ditandai sebagai Gagal/Tidak Tercapai.
 *
 * AI membaca seluruh riwayat Daily Task staf di minggu itu untuk
 * mencari pola kegagalan dan menghasilkan laporan naratif operasional.
 */
class GenerateWeeklyGapAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public readonly int $weeklyTargetId
    ) {}

    public function handle(GeminiService $gemini): void
    {
        $target = WeeklyTarget::with(['dailyTaskEntries.user', 'assignee'])->find($this->weeklyTargetId);

        if (!$target) {
            Log::warning("GenerateWeeklyGapAnalysisJob: WeeklyTarget ID {$this->weeklyTargetId} tidak ditemukan.");
            return;
        }

        // Hindari duplikasi laporan
        if ($target->gapAnalysisReport()->exists()) {
            return;
        }

        // Ambil departemen dari assignee atau creator target
        $user       = $target->assignee ?? $target->user;
        $department = $user?->department ?? 'Umum';

        // Siapkan ringkasan semua daily task di minggu ini
        $taskSummaries = $target->dailyTaskEntries->map(fn($t) => [
            'date'        => $t->task_date?->format('d/m/Y') ?? '-',
            'description' => $t->task_description,
            'duration'    => $t->actual_duration_minutes ?? $t->duration_minutes ?? 0,
            'status'      => $t->status,
            'notes'       => $t->notes,
        ])->toArray();

        if (empty($taskSummaries)) {
            Log::info("GenerateWeeklyGapAnalysisJob: Tidak ada daily task untuk target ID {$this->weeklyTargetId}");
            return;
        }

        $targetData = [
            'title'        => $target->title,
            'target_value' => $target->target_value,
            'target_unit'  => $target->target_unit,
            'week_number'  => $target->week_number,
            'month_year'   => \Carbon\Carbon::create($target->year, $target->month, 1)->isoFormat('MMMM YYYY'),
        ];

        // Panggil Gemini AI
        $result = $gemini->generateWeeklyGapAnalysis($targetData, $taskSummaries, $department);

        if (!$result) {
            Log::error("GenerateWeeklyGapAnalysisJob: Gemini gagal generate gap analysis untuk target ID {$this->weeklyTargetId}");
            return;
        }

        // Simpan laporan
        GapAnalysisReport::create([
            'reportable_type' => WeeklyTarget::class,
            'reportable_id'   => $target->id,
            'root_cause_type' => $result['root_cause_type'],
            'narrative'       => $result['narrative'],
            'recommendation'  => $result['recommendation'],
            'tasks_analyzed'  => count($taskSummaries),
            'generated_at'    => now(),
        ]);

        Log::info("GenerateWeeklyGapAnalysisJob: Gap Analysis selesai untuk WeeklyTarget ID {$target->id}");
    }
}
