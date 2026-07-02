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

    /**
     * Analisis KPI level departemen (shared / milestone) — baca laporan SELURUH
     * staf di departemen, lalu AI estimasi realisasi (shared) atau progress %
     * (milestone). Untuk milestone, hasil dijaga di rentang 0–100.
     *
     * @return array{actual_value: float, reasoning: string, reports_analyzed: int}
     */
    public function analyzeForDept(KpiTarget $kpiL2, int $month, int $year): array
    {
        $isMile = $kpiL2->isMilestone();

        $staffIds = \App\Models\User::where('department', $kpiL2->department)
            ->where('is_active', true)
            ->whereIn('role', ['staff', 'leader'])
            ->pluck('id');

        $entries = DailyTaskEntry::whereIn('user_id', $staffIds)
            ->whereMonth('task_date', $month)
            ->whereYear('task_date', $year)
            ->where(function ($q) {
                $q->where('status', 'selesai')->orWhere('verification_status', 'approved');
            })
            ->orderBy('task_date')
            ->get(['id', 'user_id', 'task_date', 'task_description', 'proof_url']);

        if ($entries->isEmpty()) {
            return [
                'actual_value'     => 0,
                'reasoning'        => 'Tidak ada laporan yang selesai/disetujui di departemen ini pada periode.',
                'reports_analyzed' => 0,
            ];
        }

        // Batasi jumlah laporan agar prompt tetap wajar.
        $reportLines = [];
        foreach ($entries->take(40) as $i => $entry) {
            $num  = $i + 1;
            $date = $entry->task_date->format('d M Y');
            $desc = trim($entry->task_description ?? '(tidak ada deskripsi)');
            $line = "{$num}. [{$date}] {$desc}";
            if (!empty($entry->proof_url)) {
                $c = $this->linkExtractor->extract($entry->proof_url);
                if ($c) {
                    $line .= "\n   📎 Isi bukti link: " . mb_substr(trim($c), 0, 300);
                }
            }
            $reportLines[] = $line;
        }

        $reportsText  = implode("\n\n", $reportLines);
        $reportsCount = $entries->count();
        $monthNames   = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $prompt = $this->buildDeptPrompt($kpiL2, $isMile, $month, $year, $monthNames, $reportsText, $reportsCount);
        $raw    = $this->callGroq($prompt);

        if (!$raw) {
            return [
                'actual_value'     => 0,
                'reasoning'        => 'AI tidak dapat merespons. Coba lagi nanti.',
                'reports_analyzed' => $reportsCount,
            ];
        }

        $parsed = $this->parseResponse($raw);
        if ($isMile) {
            $parsed['actual_value'] = min(100, max(0, (float) $parsed['actual_value']));
        }

        return array_merge($parsed, ['reports_analyzed' => $reportsCount]);
    }

    // ─── Prompt Builder ────────────────────────────────────────────────────

    private function buildDeptPrompt(
        KpiTarget $kpi,
        bool $isMile,
        int $month,
        int $year,
        array $monthNames,
        string $reportsText,
        int $count
    ): string {
        $periodLabel = "{$monthNames[$month]} {$year}";
        $deptLabel   = ucfirst(str_replace('_', ' ', $kpi->department ?? ''));
        $kpiName     = $kpi->kpi_name;

        if ($isMile) {
            return <<<PROMPT
Kamu analis KPI. Perkirakan PROGRESS sebuah milestone/proyek dalam persen (0–100) berdasarkan aktivitas kolektif satu departemen.

## Milestone
- Nama     : {$kpiName}
- Periode  : {$periodLabel}
- Dept     : {$deptLabel}

## Laporan Harian Seluruh Staf Dept ({$count} laporan)
{$reportsText}

## Tugas
Perkirakan berapa persen (0–100) progress milestone "{$kpiName}" berdasarkan laporan di atas.
- Jika banyak aktivitas relevan & tuntas → progress tinggi.
- Jika baru sebagian/persiapan → progress rendah-menengah.
- Jika hampir tidak ada aktivitas terkait → progress rendah.

## Format Jawaban (WAJIB JSON murni)
{"actual_value": <angka 0-100>, "reasoning": "<max 150 kata: dasar estimasi progress + dari laporan mana>"}
PROMPT;
        }

        $unit   = $kpi->unit;
        $target = number_format((float) $kpi->target_value, 0, ',', '.');

        return <<<PROMPT
Kamu analis KPI. Perkirakan REALISASI sebuah KPI TIM (level departemen) berdasarkan aktivitas kolektif staf.

## KPI Tim
- Nama    : {$kpiName}
- Target  : {$target} {$unit}
- Periode : {$periodLabel}
- Dept    : {$deptLabel}

## Laporan Harian Seluruh Staf Dept ({$count} laporan)
{$reportsText}

## Tugas
Estimasi nilai realisasi KPI "{$kpiName}" dalam satuan {$unit} untuk seluruh departemen.
Jika metrik ini biasanya diukur sistem (mis. uptime, SLA, kepatuhan) dan tak bisa dipastikan dari laporan,
beri estimasi terbaik yang konservatif dan SEBUTKAN bahwa ini perkiraan yang perlu diverifikasi manual.

## Format Jawaban (WAJIB JSON murni)
{"actual_value": <angka {$unit}>, "reasoning": "<max 150 kata: dasar estimasi + catatan ketidakpastian bila ada>"}
PROMPT;
    }

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
Kamu adalah analis KPI kinerja karyawan yang sangat teliti, kontekstual, dan tidak boleh menyerah hanya karena tidak ada angka eksplisit.

## Informasi KPI
- Nama KPI  : {$kpiName}
- Target    : {$target} {$unit} per bulan
- Periode   : {$periodLabel}
- Nama Staf : {$staffName}

## Laporan Harian ({$count} laporan)
{$reportsText}

## Cara Menghitung Realisasi (ikuti hierarki ini secara berurutan)

### Prioritas 1 — Angka Eksplisit (paling akurat)
Cari angka yang langsung menyebut KPI ini.
Contoh: "closing 3 deal", "dapat 5 leads", "revenue 10 juta" → langsung pakai angkanya.

### Prioritas 2 — Hitung Entitas/Nama (jika tidak ada angka)
Jika tidak ada angka eksplisit, hitung jumlah entitas yang disebutkan:
- Nama perusahaan/klien: "PT ABC, CV Maju, UD Sejahtera" → 3 {$unit}
- Nama orang/kontak: "Pak Budi, Bu Rina" → 2 {$unit}
- Aktivitas unik yang relevan per laporan → hitung satu per satu

### Prioritas 3 — Baca Isi Bukti Link (jika ada 📎)
Jika laporan menyertakan isi bukti link (ditandai 📎), baca datanya dengan seksama.
Data di spreadsheet/dokumen lebih akurat dari deskripsi teks.
Ambil angka atau hitung entitas dari sana.

### Prioritas 4 — Estimasi Konteks (terakhir)
Jika ketiga cara di atas tidak menghasilkan angka, estimasikan berdasarkan:
- Kata kerja tindakan: "berhasil closing", "presentasi ke klien", "follow up deal" = masing-masing 1 {$unit}
- Intensitas: "meeting seharian dengan banyak klien" → estimasi 3-5 {$unit}
- Hasil tersirat: "kontrak ditandatangani" → 1 {$unit}

## Aturan Wajib
- JANGAN return 0 kecuali laporan benar-benar tidak ada hubungannya sama sekali dengan "{$kpiName}".
- Jika ragu antara 0 dan nilai tertentu, SELALU pilih nilai yang lebih konservatif tapi bukan 0.
- Jelaskan dengan singkat metode mana yang kamu pakai dan darimana angka itu berasal.
- Jumlahkan pencapaian dari SEMUA laporan → total realisasi bulan ini.

## Format Jawaban (WAJIB JSON murni, tidak ada teks lain di luar JSON)
{"actual_value": <angka desimal>, "reasoning": "<penjelasan singkat max 150 kata: metode yang dipakai + dari laporan mana angka berasal>"}
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
