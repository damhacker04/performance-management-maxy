<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService — Service utama untuk komunikasi dengan Google Gemini 1.5 Flash API.
 *
 * Cara kerja:
 * 1. Menerima konteks departemen + data laporan staf
 * 2. Merakit prompt yang tepat (Dynamic Prompting)
 * 3. Mengirim ke Gemini API
 * 4. Mengembalikan respons JSON terstruktur
 */
class GeminiService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model = 'gemini-1.5-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Evaluasi satu Daily Task Entry dan kembalikan skor KPI.
     *
     * @param  array  $taskData    Data task dari Daily Task Entry
     * @param  string $department  Nama departemen staf
     * @param  string $impactLevel Dampak target mingguan (high/medium/low)
     * @param  array  $weights     Bobot 4 dimensi KPI dari KpiWeightSetting
     * @param  string|null $linkContent Isi teks dari link publik (jika ada)
     * @return array|null
     */
    public function evaluateDailyTask(
        array $taskData,
        string $department,
        string $impactLevel,
        array $weights,
        ?string $linkContent = null
    ): ?array {
        $prompt = $this->buildEvaluationPrompt($taskData, $department, $impactLevel, $weights, $linkContent);

        $response = $this->callGemini($prompt);

        if (!$response) return null;

        return $this->parseEvaluationResponse($response);
    }

    /**
     * Generate Gap Analysis untuk Weekly Target yang gagal.
     *
     * @param  array  $targetData  Data Weekly Target
     * @param  array  $taskSummaries Ringkasan Daily Tasks dalam minggu itu
     * @param  string $department  Nama departemen
     * @return array|null
     */
    public function generateWeeklyGapAnalysis(
        array $targetData,
        array $taskSummaries,
        string $department
    ): ?array {
        $prompt = $this->buildWeeklyGapPrompt($targetData, $taskSummaries, $department);
        $response = $this->callGemini($prompt);
        if (!$response) return null;
        return $this->parseGapAnalysisResponse($response);
    }

    /**
     * Generate Gap Analysis Bulanan (Strategis) untuk Monthly Target yang gagal.
     *
     * @param  array  $monthlyTargetData   Data Monthly Target
     * @param  array  $weeklyGapSummaries  Ringkasan Gap Analysis mingguan di bulan itu
     * @param  string $department          Nama departemen
     * @return array|null
     */
    public function generateMonthlyGapAnalysis(
        array $monthlyTargetData,
        array $weeklyGapSummaries,
        string $department
    ): ?array {
        $prompt = $this->buildMonthlyGapPrompt($monthlyTargetData, $weeklyGapSummaries, $department);
        $response = $this->callGemini($prompt);
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

        return <<<PROMPT
Kamu adalah sistem evaluasi kinerja AI untuk perusahaan di Indonesia bernama Maxy Academy.
Kamu sedang menilai laporan harian karyawan dari Departemen: {$department}.

KONTEKS PEKERJAAN:
- Target Mingguan: {$taskData['weekly_target_title']}
- Tingkat Dampak Target: {$impactLabel}
- Deskripsi Tugas yang Dilaporkan: {$taskData['task_description']}
- Durasi Kerja: {$taskData['duration_minutes']} menit
- Status: {$taskData['status']}
- Catatan Tambahan: {$taskData['notes'] ?? 'Tidak ada'}

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

Kembalikan HANYA JSON berikut tanpa teks lain:
{
  "score_achievement": 0.00,
  "score_efficiency": 0.00,
  "score_contribution": 0.00,
  "score_problem_solving": 0.00,
  "ai_feedback": "..."
}
PROMPT;
    }

    private function buildWeeklyGapPrompt(
        array $targetData,
        array $taskSummaries,
        string $department
    ): string {
        $tasksText = collect($taskSummaries)->map(function($t, $i) {
            return ($i+1) . ". [{$t['date']}] {$t['description']} ({$t['duration']} menit, Status: {$t['status']}) - Catatan: {$t['notes'] ?? '-'}";
        })->implode("\n");

        return <<<PROMPT
Kamu adalah analis kinerja AI untuk Maxy Academy, sebuah perusahaan di Indonesia.

TUGAS: Lakukan analisis mendalam mengapa Target Mingguan berikut GAGAL dicapai.

TARGET MINGGUAN YANG GAGAL:
- Judul: {$targetData['title']}
- Departemen: {$department}
- Target: {$targetData['target_value']} {$targetData['target_unit']}
- Periode: Minggu {$targetData['week_number']}, {$targetData['month_year']}

LAPORAN HARIAN STAF SELAMA SEMINGGU:
{$tasksText}

INSTRUKSI ANALISIS:
1. Identifikasi pola masalah dari laporan harian (misalnya: alokasi waktu salah, kendala berulang, fokus tidak pada target).
2. Tentukan apakah kegagalan ini disebabkan faktor INTERNAL (kinerja staf), EXTERNAL (sistem/birokrasi/klien), atau MIXED.
3. Tulis narrative investigasi dalam Bahasa Indonesia, maksimal 3 kalimat, spesifik dan berbasis data dari laporan.
4. Tulis recommendation untuk Leader, maksimal 2 kalimat, actionable.

Kembalikan HANYA JSON berikut:
{
  "root_cause_type": "internal|external|mixed",
  "narrative": "...",
  "recommendation": "..."
}
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
Fokus pada masalah STRUKTURAL, bukan detail operasional harian yang remeh.

TARGET BULANAN YANG GAGAL:
- Judul: {$monthlyTargetData['title']}
- Departemen: {$department}
- Periode: {$monthlyTargetData['period']}

RINGKASAN GAP ANALYSIS MINGGUAN (4 minggu):
{$weeksText}

INSTRUKSI:
1. Identifikasi BENANG MERAH (pola yang berulang di lebih dari 1 minggu) dari 4 gap analysis mingguan.
2. Tentukan root_cause_type keseluruhan: internal/external/mixed.
3. Tulis narrative untuk C-Level: masalah struktural apa yang menghambat target bulan ini? Maksimal 4 kalimat.
4. Tulis recommendation strategis untuk manajemen puncak. Maksimal 2 kalimat, bersifat sistemik.

Kembalikan HANYA JSON berikut:
{
  "root_cause_type": "internal|external|mixed",
  "narrative": "...",
  "recommendation": "..."
}
PROMPT;
    }

    // ── Private: API Call & Response Parsers ────────────────────────────────

    private function callGemini(string $prompt): ?array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.2,  // Rendah = lebih konsisten & tidak kreatif berlebihan
                    'maxOutputTokens'  => 512,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) return null;

            return json_decode($text, true);

        } catch (\Exception $e) {
            Log::error('GeminiService exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function parseEvaluationResponse(?array $data): ?array
    {
        if (!$data) return null;

        // Validasi semua field ada
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

        // Pastikan root_cause_type valid
        if (!in_array($data['root_cause_type'], ['internal', 'external', 'mixed'])) {
            $data['root_cause_type'] = 'mixed';
        }

        return $data;
    }
}
