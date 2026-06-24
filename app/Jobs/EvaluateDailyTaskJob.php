<?php

namespace App\Jobs;

use App\Models\AiEvaluation;
use App\Models\DailyTaskEntry;
use App\Models\KpiWeightSetting;
use App\Services\GeminiService;
use App\Services\LinkExtractorService;
use App\Services\LinkValidatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * EvaluateDailyTaskJob — Background Job yang dijalankan setiap kali
 * staf men-submit Daily Task Entry.
 *
 * Alur:
 * 1. Ambil data task + weekly target terkait
 * 2. Validasi dan ekstrak link bukti kerja (jika ada)
 * 3. Ambil bobot KPI aktif dari database
 * 4. Kirim ke GeminiService untuk dinilai
 * 5. Hitung skor akhir berbobot
 * 6. Simpan hasil ke tabel ai_evaluations
 */
class EvaluateDailyTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;           // Coba ulang 3x jika gagal

    public array $backoff = [10, 30]; // Tunggu 10s lalu 30s sebelum retry

    public function __construct(
        public readonly int $dailyTaskEntryId
    ) {}

    public function handle(
        GeminiService $gemini,
        LinkValidatorService $validator,
        LinkExtractorService $extractor
    ): void {
        $task = DailyTaskEntry::with(['weeklyTarget', 'user', 'evidences'])->find($this->dailyTaskEntryId);

        if (! $task) {
            Log::warning("EvaluateDailyTaskJob: Task ID {$this->dailyTaskEntryId} tidak ditemukan.");

            return;
        }

        // Jika sudah pernah dievaluasi, skip (idempotent)
        if ($task->aiEvaluation()->exists()) {
            return;
        }

        // Tentukan departemen staf
        $department = $task->user->department ?? 'Umum';

        // Tentukan impact level dari Weekly Target (default medium)
        $impactLevel = $task->weeklyTarget?->impact_level ?? 'medium';

        // Ambil bobot KPI aktif
        $weightSetting = KpiWeightSetting::getActive();
        $weights = [
            'achievement' => (float) $weightSetting->weight_achievement,
            'efficiency' => (float) $weightSetting->weight_efficiency,
            'contribution' => (float) $weightSetting->weight_contribution,
            'problem_solving' => (float) $weightSetting->weight_problem_solving,
        ];

        // Proses link bukti kerja dari multi-evidence (tipe 'link').
        // Sebelumnya job ini membaca kolom legacy `proof_url` yang tidak pernah
        // diisi alur baru, sehingga AI tak pernah membaca isi dokumen bukti.
        $linkStatus = 'no_link';
        $linkContent = null;

        $linkUrls = $task->evidences
            ->where('type', 'link')
            ->pluck('path_or_url')
            ->filter()
            ->values();

        foreach ($linkUrls as $url) {
            $validation = $validator->check($url);
            $linkStatus = $validation['status'];

            if ($linkStatus === 'public') {
                $extracted = $extractor->extract($url);
                if ($extracted) {
                    $linkContent = $extracted;
                    break; // cukup satu dokumen publik yang berhasil dibaca
                }
            }
        }

        // Siapkan data task untuk dikirim ke Gemini
        $taskData = [
            'weekly_target_title' => $task->weeklyTarget?->title ?? 'Tanpa Target Mingguan',
            'task_description' => $task->task_description,
            'duration_minutes' => $task->actual_duration_minutes ?? $task->duration_minutes ?? 0,
            'status' => $task->status,
            'notes' => $task->notes,
        ];

        // Panggil Gemini AI
        $result = $gemini->evaluateDailyTask($taskData, $department, $impactLevel, $weights, $linkContent);

        if (! $result) {
            Log::error("EvaluateDailyTaskJob: Groq gagal evaluasi task ID {$this->dailyTaskEntryId}");
            throw new \Exception("Groq gagal evaluasi task ID {$this->dailyTaskEntryId}");
        }

        // Hitung skor akhir berbobot
        $finalScore = (
            ($result['score_achievement'] * $weights['achievement'] / 100) +
            ($result['score_efficiency'] * $weights['efficiency'] / 100) +
            ($result['score_contribution'] * $weights['contribution'] / 100) +
            ($result['score_problem_solving'] * $weights['problem_solving'] / 100)
        );

        // Simpan hasil ke database. firstOrCreate + unique constraint pada
        // daily_task_entry_id menjamin idempoten walau job ter-dispatch ganda.
        AiEvaluation::firstOrCreate(
            ['daily_task_entry_id' => $task->id],
            [
                'score_achievement' => $result['score_achievement'],
                'score_efficiency' => $result['score_efficiency'],
                'score_contribution' => $result['score_contribution'],
                'score_problem_solving' => $result['score_problem_solving'],
                'final_score' => round($finalScore, 2),
                'ai_feedback' => $result['ai_feedback'],
                'link_status' => $linkStatus,
                'is_overridden' => false,
                'raw_response' => $result,
            ]
        );

        Log::info("EvaluateDailyTaskJob: Selesai. Task ID {$task->id}, Skor: {$finalScore}");
    }
}
