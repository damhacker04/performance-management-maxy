<?php

namespace App\Console\Commands;

use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor KPI dari "MAXY_Academy_KPI_Master.xlsx" sebagai KPI level-departemen (L2).
 *
 * File berisi target berupa teks ("≥ 30%", "Sesuai target") dan TANPA kolom jenis
 * KPI. Sistem kita butuh target angka + unit + jenis agregasi (sum/average/shared/
 * milestone). Command ini memakai AI (Groq) HANYA saat impor untuk menerjemahkan
 * tiap baris → field yang sistem butuhkan. Skema/logika sistem TIDAK berubah.
 *
 * Pemetaan departemen deterministik (bukan tebakan AI). Dept yang tak ada di sistem
 * dilewati & dilaporkan. Default DRY-RUN (tulis preview ke CSV); pakai --commit
 * untuk menyimpan ke kpi_targets.
 */
class ImportKpiMaster extends Command
{
    protected $signature = 'import:kpi-master {file : Path ke MAXY_Academy_KPI_Master.xlsx} {--commit : Tulis ke DB (default hanya preview)}';

    protected $description = 'Impor KPI Master (L2 dept) dengan bantuan AI untuk jenis & parsing target';

    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $model  = 'llama-3.3-70b-versatile';

    /** Dept di Excel → key DEPARTMENTS sistem. Yang tak terdaftar = dilewati. */
    private array $deptMap = [
        'Marketing'              => 'marketing',
        'Sales'                  => 'sales',
        'Product Development'    => 'product_it',
        'IT'                     => 'product_it',
        'Finance & Accounting'   => 'finance',
        'CEO Office'             => 'ceo_office',
        'Human Capital'          => 'hr',
        'University Partnership' => 'univ_partnership',
        'Operations'             => 'operational',
        'C-Level'                => 'c_level', // dept level-perusahaan (CEO/CTO)
        // Sengaja TIDAK dipetakan (dilewati): Academic, Legal,
        // Learning & Development, Talent Placement & Partnership, Facilities.
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: $file");
            return self::FAILURE;
        }
        if (! config('services.groq.api_key')) {
            $this->error('GROQ_API_KEY belum diset — AI dibutuhkan untuk klasifikasi. Set dulu lalu ulangi.');
            return self::FAILURE;
        }

        $this->info('Membaca file...');
        $sheet = IOFactory::load($file)->getSheetByName('KPI Master') ?? IOFactory::load($file)->getActiveSheet();
        $rows  = $sheet->toArray(null, true, false, false); // index numerik 0..n

        // Baris data mulai setelah header (cari baris yang kolom 0 = 'Dept').
        $start = 0;
        foreach ($rows as $i => $r) {
            if (($r[0] ?? null) === 'Dept') { $start = $i + 1; break; }
        }

        $known = [];
        $skipped = [];
        for ($i = $start; $i < count($rows); $i++) {
            $r = $rows[$i];
            $dept = trim((string) ($r[0] ?? ''));
            $name = trim((string) ($r[3] ?? ''));
            if ($dept === '' || $name === '') { continue; }

            if (! isset($this->deptMap[$dept])) {
                $skipped[$dept] = ($skipped[$dept] ?? 0) + 1;
                continue;
            }
            $known[] = [
                'dept_key'  => $this->deptMap[$dept],
                'dept_src'  => $dept,
                'position'  => trim((string) ($r[1] ?? '')),
                'name'      => $name,
                'desc'      => trim((string) ($r[4] ?? '')),
                'unit_src'  => trim((string) ($r[5] ?? '')),
                'target'    => trim((string) ($r[6] ?? '')),
                'frequency' => trim((string) ($r[7] ?? '')),
            ];
        }

        $this->line('KPI dept dikenal: ' . count($known) . ' | dilewati (dept asing): ' . array_sum($skipped));
        if (! $known) { $this->warn('Tidak ada baris untuk diproses.'); return self::SUCCESS; }

        // ── Klasifikasi via AI (batch) ──────────────────────────────────────
        $this->info('Meminta AI mengklasifikasi jenis & menerjemahkan target...');
        $batchSize = 12;
        foreach (array_chunk($known, $batchSize, true) as $chunk) {
            $result = $this->classifyBatch($chunk);
            foreach ($chunk as $idx => $row) {
                $ai = $result[$idx] ?? null;
                $known[$idx]['aggregation']  = $ai['aggregation']  ?? 'milestone';
                $known[$idx]['target_value'] = $ai['target_value'] ?? 100;
                $known[$idx]['unit']         = $ai['unit']         ?? '%';
                // Konvensi milestone sistem kita: target 100, unit '%'.
                if ($known[$idx]['aggregation'] === 'milestone') {
                    $known[$idx]['target_value'] = 100;
                    $known[$idx]['unit'] = '%';
                }
            }
            $this->output->write('.');
        }
        $this->newLine();

        // ── Tulis preview CSV ───────────────────────────────────────────────
        $previewPath = storage_path('app/kpi-import-preview.csv');
        $fh = fopen($previewPath, 'w');
        fputcsv($fh, ['dept_key', 'kpi_name', 'aggregation', 'target_value', 'unit', 'position', 'target_asli', 'frequency']);
        foreach ($known as $r) {
            fputcsv($fh, [$r['dept_key'], $r['name'], $r['aggregation'], $r['target_value'], $r['unit'], $r['position'], $r['target'], $r['frequency']]);
        }
        fclose($fh);

        // ── Ringkasan ───────────────────────────────────────────────────────
        $byDept = [];
        $byAgg  = [];
        foreach ($known as $r) {
            $byDept[$r['dept_key']] = ($byDept[$r['dept_key']] ?? 0) + 1;
            $byAgg[$r['aggregation']] = ($byAgg[$r['aggregation']] ?? 0) + 1;
        }
        $this->newLine();
        $this->info('Per departemen:');
        foreach ($byDept as $d => $c) { $this->line("  " . str_pad($d, 18) . " $c"); }
        $this->info('Per jenis KPI:');
        foreach ($byAgg as $a => $c) { $this->line("  " . str_pad($a, 12) . " $c"); }
        if ($skipped) {
            $this->warn('Dept dilewati (belum ada di sistem):');
            foreach ($skipped as $d => $c) { $this->line("  - $d ($c)"); }
        }
        $this->newLine();
        $this->info("Preview lengkap: $previewPath");

        // ── Commit ──────────────────────────────────────────────────────────
        if (! $this->option('commit')) {
            $this->warn('DRY-RUN. Periksa CSV di atas. Tambahkan --commit untuk menulis ke DB.');
            return self::SUCCESS;
        }

        $month = (int) now()->month;
        $year  = (int) now()->year;
        $setBy = User::where('role', 'super_admin')->value('id');
        $written = 0;
        foreach ($known as $r) {
            KpiTarget::updateOrCreate(
                [
                    'department' => $r['dept_key'],
                    'kpi_name'   => $r['name'],
                    'kpi_level'  => 2,
                    'month'      => $month,
                    'year'       => $year,
                ],
                [
                    'parent_id'    => null,
                    'user_id'      => null,
                    'aggregation'  => $r['aggregation'],
                    'target_value' => $r['target_value'],
                    'unit'         => $r['unit'],
                    'is_active'    => true,
                    'set_by'       => $setBy,
                    'notes'        => trim(($r['desc'] ? $r['desc'] . ' · ' : '') . "Impor KPI Master (posisi: {$r['position']}; freq: {$r['frequency']}; target asli: {$r['target']})"),
                ]
            );
            $written++;
        }
        $this->info("Commit selesai. $written KPI dept (L2) ditulis untuk periode $month/$year.");

        return self::SUCCESS;
    }

    /**
     * Minta Groq mengklasifikasi satu batch. Kembalikan [indexAsli => [aggregation,target_value,unit]].
     */
    private function classifyBatch(array $chunk): array
    {
        // Susun payload ringkas dengan index asli agar bisa dipetakan balik.
        $items = [];
        foreach ($chunk as $idx => $r) {
            $items[] = ['i' => $idx, 'name' => $r['name'], 'unit' => $r['unit_src'], 'target' => $r['target'], 'desc' => $r['desc']];
        }
        $json = json_encode($items, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Klasifikasikan tiap KPI ke sistem dengan 4 jenis agregasi:
- "sum": total yang DIJUMLAHKAN dari tiap staff (jumlah deal, jumlah siswa, revenue, jumlah leads, jumlah konten).
- "average": rasio/persentase yang DIRATA-RATA antar staff (completion rate, conversion rate, skor kualitas per orang).
- "shared": satu metrik TIM yang tak dibagi per staff (uptime, SLA, NPS, CAC, ROAS, brand awareness, response time).
- "milestone": target kualitatif/biner/progress ATAU non-numerik ("Sesuai target", "Trend naik", "≥ B grade"). Diukur progress 0-100%.

Untuk tiap item tentukan:
- aggregation: sum|average|shared|milestone
- target_value: HANYA angka (buang ≥, ≤, ~, teks, dan satuan). Untuk milestone/target non-numerik pakai 100.
- unit: satuan singkat (%, kontrak, siswa, Rp, leads, skor, jam). Untuk milestone pakai "%".

Data:
$json

Jawab HANYA array JSON tanpa markdown: [{"i":<index>,"aggregation":"..","target_value":<angka>,"unit":".."}]
PROMPT;

        $raw = $this->callGroq($prompt);
        if (! $raw) { return []; }

        $clean = trim(preg_replace('/```json\s*|\s*```/', '', $raw));
        // Ambil bagian array saja bila ada teks lain.
        if (preg_match('/\[.*\]/s', $clean, $m)) { $clean = $m[0]; }
        $arr = json_decode($clean, true);
        if (! is_array($arr)) { return []; }

        $out = [];
        foreach ($arr as $item) {
            if (! isset($item['i'])) { continue; }
            $agg = in_array($item['aggregation'] ?? '', ['sum', 'average', 'shared', 'milestone'], true)
                ? $item['aggregation'] : 'milestone';
            $out[(int) $item['i']] = [
                'aggregation'  => $agg,
                'target_value' => is_numeric($item['target_value'] ?? null) ? (float) $item['target_value'] : 100,
                'unit'         => trim((string) ($item['unit'] ?? '%')) ?: '%',
            ];
        }
        return $out;
    }

    private function callGroq(string $prompt): ?string
    {
        try {
            $response = Http::withToken(config('services.groq.api_key'))
                ->timeout(90)
                ->post($this->apiUrl, [
                    'model'       => $this->model,
                    'temperature' => 0.1,
                    'max_tokens'  => 2000,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Kamu asisten klasifikasi KPI. Jawab HANYA JSON valid tanpa markdown.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
            return $response->successful() ? $response->json('choices.0.message.content') : null;
        } catch (\Throwable $e) {
            $this->warn('Groq error: ' . $e->getMessage());
            return null;
        }
    }
}
