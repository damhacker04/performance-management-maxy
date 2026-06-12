<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService — Service utama untuk komunikasi dengan Groq API.
 * (Nama kelas dipertahankan untuk kompatibilitas dengan kode lain)
 *
 * Model: llama-3.3-70b-versatile via Groq Cloud
 * Cara kerja:
 * 1. Menerima konteks departemen + data laporan staf
 * 2. Merakit prompt yang tepat (Dynamic Prompting)
 * 3. Mengirim ke Groq API (format OpenAI-compatible)
 * 4. Mengembalikan respons JSON terstruktur
 */
class GeminiService
{
    private string $apiKey;
    private string $apiUrl  = 'https://api.groq.com/openai/v1/chat/completions';
    private string $model   = 'llama-3.3-70b-versatile';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
    }

    /**
     * Evaluasi satu Daily Task Entry dan kembalikan skor KPI.
     */
    public function evaluateDailyTask(
        array $taskData,
        string $department,
        string $impactLevel,
        array $weights,
        ?string $linkContent = null
    ): ?array {
        $prompt = $this->buildEvaluationPrompt($taskData, $department, $impactLevel, $weights, $linkContent);

        $response = $this->callGroq($prompt);

        if (!$response) return null;

        return $this->parseEvaluationResponse($response);
    }

    /**
     * Generate Gap Analysis untuk Weekly Target yang gagal.
     */
    public function generateWeeklyGapAnalysis(
        array $targetData,
        array $taskSummaries,
        string $department
    ): ?array {
        $prompt = $this->buildWeeklyGapPrompt($targetData, $taskSummaries, $department);
        $response = $this->callGroq($prompt);
        if (!$response) return null;
        return $this->parseGapAnalysisResponse($response);
    }

    /**
     * Generate Gap Analysis Bulanan (Strategis) untuk Monthly Target yang gagal.
     */
    public function generateMonthlyGapAnalysis(
        array $monthlyTargetData,
        array $weeklyGapSummaries,
        string $department
    ): ?array {
        $prompt = $this->buildMonthlyGapPrompt($monthlyTargetData, $weeklyGapSummaries, $department);
        $response = $this->callGroq($prompt);
        if (!$response) return null;
        return $this->parseGapAnalysisResponse($response);
    }

    // ── Private: Prompt Builders ─────────────────────────────────────────────

    private function buildEvaluationPrompt(
        array $taskData,
        string $department,
        string $impactLevel,
        array $weights,
        ?string $linkContent
    ): string {
        $impactLabel = match($impactLevel) {
            'high'   => 'TINGGI (berdampak langsung pada pendapatan/target utama bisnis)',
            'medium' => 'SEDANG (mendukung target bulanan departemen)',
            'low'    => 'RENDAH (administratif/rutin)',
            default  => 'SEDANG',
        };

        $linkSection = $linkContent
            ? "KONTEN DOKUMEN TERLAMPIR:\n```\n{$linkContent}\n```"
            : "Tidak ada dokumen terlampir. Nilai berdasarkan deskripsi teks saja.";

        $weightInfo = "Bobot Penilaian: Pencapaian={$weights['achievement']}%, Efisiensi={$weights['efficiency']}%, Kontribusi={$weights['contribution']}%, ProblemSolving={$weights['problem_solving']}%";

        $weeklyTargetTitle  = $taskData['weekly_target_title'] ?? 'Tidak ada target';
        $taskDescription    = $taskData['task_description']    ?? '-';
        $durationMinutes    = $taskData['duration_minutes']    ?? 0;
        $taskStatus         = $taskData['status']              ?? '-';
        $taskNotes          = $taskData['notes']               ?? 'Tidak ada';

        return <<<PROMPT
Kamu adalah sistem evaluasi kinerja AI untuk perusahaan di Indonesia bernama Maxy Academy.
Kamu sedang menilai laporan harian karyawan dari Departemen: {$department}.

KONTEKS PEKERJAAN:
- Target Mingguan: {$weeklyTargetTitle}
- Tingkat Dampak Target: {$impactLabel}
- Deskripsi Tugas yang Dilaporkan: {$taskDescription}
- Durasi Kerja: {$durationMinutes} menit
- Status: {$taskStatus}
- Catatan Tambahan: {$taskNotes}

{$linkSection}

INSTRUKSI PENILAIAN:
{$weightInfo}

Nilai karyawan ini berdasarkan 4 dimensi berikut (skala 0.00 - 10.00):
1. score_achievement: Seberapa jelas dan konkret tugas ini mencapai target yang disebutkan?
2. score_efficiency: Apakah waktu yang dihabiskan sebanding dengan output yang dihasilkan?
3. score_contribution: Seberapa relevan tugas ini dengan dampak bisnis target (yang sudah di-set {$impactLabel})?
4. score_problem_solving: Adakah bukti staf menghadapi dan mengatasi hambatan secara kreatif?

ATURAN KETAT:
- Jika deskripsi tugas sangat singkat/tidak jelas (< 10 kata), beri skor rendah untuk semua dimensi (max 4.0).
- Jika tugas tidak relevan dengan target mingguan, beri score_contribution max 3.0.
- Jangan pernah memberi skor sempurna (10.0) kecuali ada bukti yang sangat kuat.
- ai_feedback harus dalam Bahasa Indonesia, maksimal 2 kalimat, spesifik dan konstruktif.

Kembalikan HANYA JSON berikut tanpa teks, penjelasan, atau markdown lain:
{"score_achievement": 0.00, "score_efficiency": 0.00, "score_contribution": 0.00, "score_problem_solving": 0.00, "ai_feedback": "..."}
PROMPT;
    }

    private function buildWeeklyGapPrompt(
        array $targetData,
        array $taskSummaries,
        string $department
    ): string {
        $tasksText = collect($taskSummaries)->map(function($t, $i) {
            $notes = $t['notes'] ?? '-';
            return ($i+1) . ". [{$t['date']}] {$t['description']} ({$t['duration']} menit, Status: {$t['status']}) - Catatan: {$notes}";
        })->implode("\n");

        $targetValue = $targetData['target_value'] ?? '-';
        $targetUnit  = $targetData['target_unit']  ?? '';
        $weekNumber  = $targetData['week_number']  ?? '-';
        $monthYear   = $targetData['month_year']   ?? '-';
        $title       = $targetData['title']        ?? '-';

        return <<<PROMPT
Kamu adalah analis kinerja AI untuk Maxy Academy, sebuah perusahaan di Indonesia.

TUGAS: Lakukan analisis mendalam mengapa Target Mingguan berikut GAGAL dicapai.

TARGET MINGGUAN YANG GAGAL:
- Judul: {$title}
- Departemen: {$department}
- Target: {$targetValue} {$targetUnit}
- Periode: Minggu {$weekNumber}, {$monthYear}

LAPORAN HARIAN STAF SELAMA SEMINGGU:
{$tasksText}

INSTRUKSI ANALISIS:
1. Identifikasi pola masalah dari laporan harian.
2. Tentukan apakah kegagalan ini disebabkan faktor INTERNAL (kinerja staf), EXTERNAL (sistem/birokrasi/klien), atau MIXED.
3. Tulis narrative investigasi dalam Bahasa Indonesia, maksimal 3 kalimat, spesifik dan berbasis data dari laporan.
4. Tulis recommendation untuk Leader, maksimal 2 kalimat, actionable.

Kembalikan HANYA JSON berikut tanpa teks atau markdown lain:
{"root_cause_type": "internal|external|mixed", "narrative": "...", "recommendation": "..."}
PROMPT;
    }

    private function buildMonthlyGapPrompt(
        array $monthlyTargetData,
        array $weeklyGapSummaries,
        string $department
    ): string {
        $weeksText = collect($weeklyGapSummaries)->map(function($w, $i) {
            return "Minggu " . ($i+1) . ": [{$w['root_cause_type']}] {$w['narrative']}";
        })->implode("\n");

        return <<<PROMPT
Kamu adalah konsultan manajemen AI senior untuk Maxy Academy, perusahaan di Indonesia.

TUGAS: Buat laporan Gap Analysis STRATEGIS level C-Level/Eksekutif mengapa Target Bulanan gagal.

TARGET BULANAN YANG GAGAL:
- Judul: {$monthlyTargetData['title']}
- Departemen: {$department}
- Periode: {$monthlyTargetData['period']}

RINGKASAN GAP ANALYSIS MINGGUAN (4 minggu):
{$weeksText}

INSTRUKSI:
1. Identifikasi BENANG MERAH (pola yang berulang di lebih dari 1 minggu).
2. Tentukan root_cause_type keseluruhan: internal/external/mixed.
3. Tulis narrative untuk C-Level, maksimal 4 kalimat.
4. Tulis recommendation strategis untuk manajemen puncak, maksimal 2 kalimat, bersifat sistemik.

Kembalikan HANYA JSON berikut tanpa teks atau markdown lain:
{"root_cause_type": "internal|external|mixed", "narrative": "...", "recommendation": "..."}
PROMPT;
    }

    // ── Private: API Call & Response Parsers ────────────────────────────────

    private function callGroq(string $prompt): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model'    => $this->model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Kamu adalah AI evaluator kinerja karyawan. Selalu kembalikan HANYA JSON murni tanpa markdown, kode block, atau teks penjelasan apapun.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature'     => 0.2,
                'max_tokens'      => 512,
                'response_format' => ['type' => 'json_object'], // Groq mendukung JSON mode!
            ]);

            if (!$response->successful()) {
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) return null;

            // Bersihkan jika masih ada markdown (fallback)
            $content = preg_replace('/```(?:json)?\n?(.*?)\n?```/s', '$1', $content);
            $content = trim($content);

            return json_decode($content, true);

        } catch (\Exception $e) {
            Log::error('GroqService exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function parseEvaluationResponse(?array $data): ?array
    {
        if (!$data) return null;

        $required = ['score_achievement', 'score_efficiency', 'score_contribution', 'score_problem_solving', 'ai_feedback'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) return null;
        }

        // Clamp skor ke range 0-10
        foreach (['score_achievement', 'score_efficiency', 'score_contribution', 'score_problem_solving'] as $key) {
            $data[$key] = max(0, min(10, (float) $data[$key]));
        }

        return $data;
    }

    private function parseGapAnalysisResponse(?array $data): ?array
    {
        if (!$data) return null;

        $required = ['root_cause_type', 'narrative', 'recommendation'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) return null;
        }

        if (!in_array($data['root_cause_type'], ['internal', 'external', 'mixed'])) {
            $data['root_cause_type'] = 'mixed';
        }

        return $data;
    }
}
