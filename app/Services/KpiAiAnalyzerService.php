<?php

namespace App\Services;

use App\Models\DailyTaskEntry;
use App\Models\KpiTarget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KpiAiAnalyzerService — Menganalisis laporan harian staf menggunakan Groq AI
 * untuk menghitung realisasi KPI secara otomatis.
 *
 * Alur:
 * 1. Ambil semua laporan harian staf yang selesai/approved untuk bulan tersebut
 * 2. Fetch konten dari proof_url (Google Docs/Sheets/link publik) via LinkExtractorService
 * 3. Rakit prompt ke Groq beserta konteks KPI
 * 4. Parse response JSON → kembalikan actual_value & reasoning
 */
class KpiAiAnalyzerService
{
    private string $apiUrl  = 'https://api.groq.com/openai/v1/chat/completions';
    private string $model   = 'llama-3.3-70b-versatile';
    private ?string $apiKey;

    public function __construct(
        private readonly LinkExtractorService $linkExtractor
    ) {
        $this->apiKey = config('services.groq.api_key') ?: '';
    }

    /**
     * Analisis realisasi KPI L3 untuk satu staf pada bulan tertentu.
     *
     * @return array{actual_value: float, reasoning: string, reports_analyzed: int}
     */
    public function analyzeForStaff(KpiTarget $kpiL3, int $month, int $year): array
    {
        // Ambil semua laporan staf yang sudah selesai/approved bulan ini
        $entries = DailyTaskEntry::where('user_id', $kpiL3->user_id)
            ->whereMonth('task_date', $month)
            ->whereYear('task_date', $year)
            ->where(function ($q) {
                $q->where('status', 'selesai')
                  ->orWhere('verification_status', 'approved');
            })
            ->orderBy('task_date')
            ->get(['id', 'task_date', 'task_description', 'proof_url', 'status', 'verification_status']);

        if ($entries->isEmpty()) {
            return [
                'actual_value'     => 0,
                'reasoning'        => 'Tidak ada laporan yang selesai/disetujui pada periode ini.',
                'reports_analyzed' => 0,
            ];
        }

        // Rakit konteks laporan (deskripsi + isi link bukti)
        $reportLines = [];
        foreach ($entries as $i => $entry) {
            $num     = $i + 1;
            $date    = $entry->task_date->format('d M Y');
            $desc    = trim($entry->task_description ?? '(tidak ada deskripsi)');
            $line    = "{$num}. [{$date}] {$desc}";

            // Fetch isi link bukti jika ada
            if (!empty($entry->proof_url)) {
                $linkContent = $this->linkExtractor->extract($entry->proof_url);
                if ($linkContent) {
                    $preview = mb_substr(trim($linkContent), 0, 400);
                    $line   .= "\n   📎 Isi bukti link: {$preview}";
                }
            }

            $reportLines[] = $line;
        }

        $reportsText   = implode("\n\n", $reportLines);
        $reportsCount  = $entries->count();
        $monthNames    = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Kirim ke Groq
        $prompt  = $this->buildPrompt($kpiL3, $month, $year, $monthNames, $reportsText, $reportsCount);
        $raw     = $this->callGroq($prompt);

        if (!$raw) {
            return [
                'actual_value'     => 0,
                'reasoning'        => 'AI tidak dapat merespons. Coba lagi nanti.',
                'reports_analyzed' => $reportsCount,
            ];
        }

        $parsed = $this->parseResponse($raw);

        return array_merge($parsed, ['reports_analyzed' => $reportsCount]);
    }

    // ─── Prompt Builder ────────────────────────────────────────────────────

    private function buildPrompt(
        KpiTarget $kpi,
        int $month,
        int $year,
        array $monthNames,
        string $reportsText,
        int $count
    ): string {
        $periodLabel = "{$monthNames[$month]} {$year}";
        $staffName   = $kpi->staff?->name ?? 'Staff';
        $unit        = $kpi->unit;
        $kpiName     = $kpi->kpi_name;
        $target      = number_format((float) $kpi->target_value, 0, ',', '.');

        return <<<PROMPT
Kamu adalah asisten evaluasi KPI kinerja karyawan yang sangat teliti dan objektif.

## Tugas
Hitung total realisasi KPI berdasarkan laporan harian karyawan di bawah ini.

## Informasi KPI
- Nama KPI     : {$kpiName}
- Target       : {$target} {$unit} per bulan
- Periode      : {$periodLabel}
- Nama Staf    : {$staffName}

## Laporan Harian ({$count} laporan)
{$reportsText}

## Instruksi
1. Baca setiap laporan dengan cermat, termasuk isi bukti link jika ada.
2. Identifikasi angka konkret yang berkaitan langsung dengan KPI "{$kpiName}".
3. Jika angka tidak disebutkan eksplisit, estimasikan berdasarkan konteks (contoh: "berhasil closing dengan PT ABC" = 1 {$unit}).
4. Jika sebuah laporan sama sekali tidak relevan dengan KPI ini, hitung sebagai 0.
5. Jumlahkan semua pencapaian → total realisasi.

## Format Jawaban (WAJIB JSON, tidak boleh ada teks lain)
{"actual_value": <angka desimal>, "reasoning": "<penjelasan ringkas max 150 kata dalam Bahasa Indonesia>"}
PROMPT;
    }

    // ─── Groq API Call ─────────────────────────────────────────────────────

    private function callGroq(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('KpiAiAnalyzerService: GROQ_API_KEY tidak dikonfigurasi.');
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post($this->apiUrl, [
                    'model'       => $this->model,
                    'temperature' => 0.1,  // rendah agar konsisten & faktual
                    'max_tokens'  => 300,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Kamu adalah asisten evaluasi KPI. Selalu jawab HANYA dalam format JSON yang valid tanpa markdown code block.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('KpiAiAnalyzerService: Groq API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json('choices.0.message.content');

        } catch (\Exception $e) {
            Log::error('KpiAiAnalyzerService: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    // ─── Response Parser ───────────────────────────────────────────────────

    private function parseResponse(string $raw): array
    {
        // Bersihkan dari markdown code block jika ada
        $clean = preg_replace('/```json\s*|\s*```/', '', $raw);
        $clean = trim($clean);

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['actual_value'])) {
            Log::warning('KpiAiAnalyzerService: Gagal parse JSON', ['raw' => $raw]);

            // Coba ekstrak angka dengan regex sebagai fallback
            preg_match('/"actual_value"\s*:\s*([\d.]+)/', $raw, $matches);
            $fallbackValue = isset($matches[1]) ? (float) $matches[1] : 0;

            return [
                'actual_value' => $fallbackValue,
                'reasoning'    => 'AI memberikan respons yang tidak terstruktur. Nilai diekstrak secara parsial.',
            ];
        }

        return [
            'actual_value' => (float) $data['actual_value'],
            'reasoning'    => $data['reasoning'] ?? '',
        ];
    }
}
