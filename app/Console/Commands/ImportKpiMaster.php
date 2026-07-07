<?php

namespace App\Console\Commands;

use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor KPI dari "Quick Reference KPI.xlsx" sebagai KPI level-departemen (L2).
 *
 * Format Quick Reference: 1 baris per POSISI, kolom:
 *   Department | Position | KPI #1 | KPI #2 | KPI #3 | Target Range
 * di mana "Target Range" berisi 3 target dipisah "|" sesuai KPI #1/#2/#3.
 * Tiap baris dipecah jadi 3 KPI.
 *
 * Sistem kita butuh target angka + unit + jenis agregasi. AI (Groq) dipakai HANYA
 * saat impor untuk menerjemahkan tiap KPI → field sistem. Skema TIDAK berubah.
 * Pemetaan departemen deterministik; dept yang tak ada di sistem dilewati.
 * Default DRY-RUN (preview CSV); --commit untuk menulis ke kpi_targets.
 */
class ImportKpiMaster extends Command
{
    protected $signature = 'import:kpi {file : Path ke Quick Reference KPI.xlsx} {--commit : Tulis ke DB (default hanya preview)}';

    protected $description = 'Impor Quick Reference KPI (L2 dept) dengan bantuan AI untuk jenis & parsing target';

    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $model  = 'llama-3.3-70b-versatile';

    /** Penanda notes untuk KPI hasil impor (agar idempotent). */
    private const MARK = 'Impor KPI';

    /** Dept di Excel → key DEPARTMENTS sistem. Yang tak terdaftar = dilewati. */
    private array $deptMap = [
        'Marketing'              => 'marketing',
        'Sales'                  => 'sales',
        'Product Development'    => 'product_it',
        'IT'                     => 'product_it',
        'Finance & Accounting'   => 'finance',
        'Finance'                => 'finance',
        'CEO Office'             => 'ceo_office',
        'Human Capital'          => 'hr',
        'University Partnership' => 'univ_partnership',
        'Operations'             => 'operational',
        'C-Level'                => 'c_level',
        // Dilewati: Academic, Legal, Learning & Development,
        // Talent Placement & Partnership, Facilities.
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: $file");
            return self::FAILURE;
        }
        if (! config('services.groq.api_key')) {
            $this->error('GROQ_API_KEY belum diset — AI dibutuhkan untuk klasifikasi.');
            return self::FAILURE;
        }

        $this->info('Membaca file...');
        $book  = IOFactory::load($file);
        $sheet = $book->getSheetByName('Quick Reference') ?? $book->getActiveSheet();
        $rows  = $sheet->toArray(null, true, false, false);

        // Cari baris header (kolom 0 = 'Department' / 'Dept').
        $start = 0;
        foreach ($rows as $i => $r) {
            $c0 = trim((string) ($r[0] ?? ''));
            if ($c0 === 'Department' || $c0 === 'Dept') { $start = $i + 1; break; }
        }

        // Pecah tiap baris posisi → 3 KPI.
        $known = [];
        $skipped = [];
        for ($i = $start; $i < count($rows); $i++) {
            $r = $rows[$i];
            $dept = trim((string) ($r[0] ?? ''));
            $position = trim((string) ($r[1] ?? ''));
            if ($dept === '') { continue; }

            if (! isset($this->deptMap[$dept])) {
                $skipped[$dept] = ($skipped[$dept] ?? 0) + 1;
                continue;
            }
            $deptKey = $this->deptMap[$dept];

            $names   = [trim((string) ($r[2] ?? '')), trim((string) ($r[3] ?? '')), trim((string) ($r[4] ?? ''))];
            $targets = array_map('trim', explode('|', (string) ($r[5] ?? '')));

            foreach ($names as $j => $name) {
                if ($name === '') { continue; }
                $known[] = [
                    'dept_key' => $deptKey,
                    'dept_src' => $dept,
                    'position' => $position,
                    'name'     => $name,
                    'target'   => $targets[$j] ?? '',
                ];
            }
        }

        $this->line('KPI dikenal: ' . count($known) . ' | dilewati (dept asing): ' . array_sum($skipped));
        if (! $known) { $this->warn('Tidak ada baris untuk diproses.'); return self::SUCCESS; }

        // ── Klasifikasi via AI (batch) ──────────────────────────────────────
        $this->info('Meminta AI mengklasifikasi jenis & menerjemahkan target...');
        foreach (array_chunk($known, 12, true) as $chunk) {
            $result = $this->classifyBatch($chunk);
            foreach ($chunk as $idx => $row) {
                $ai = $result[$idx] ?? null;
                $agg = $ai['aggregation'] ?? 'milestone';
                $known[$idx]['aggregation']  = $agg;
                $known[$idx]['target_value'] = $agg === 'milestone' ? 100 : ($ai['target_value'] ?? 100);
                $known[$idx]['unit']         = $agg === 'milestone' ? '%'  : ($ai['unit'] ?? '');
            }
            $this->output->write('.');
        }
        $this->newLine();

        // ── Preview CSV ─────────────────────────────────────────────────────
        $previewPath = storage_path('app/kpi-import-preview.csv');
        $fh = fopen($previewPath, 'w');
        fputcsv($fh, ['dept_key', 'kpi_name', 'aggregation', 'target_value', 'unit', 'position', 'target_asli']);
        foreach ($known as $r) {
            fputcsv($fh, [$r['dept_key'], $r['name'], $r['aggregation'], $r['target_value'], $r['unit'], $r['position'], $r['target']]);
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
        foreach ($byDept as $d => $c) { $this->line('  ' . str_pad($d, 18) . " $c"); }
        $this->info('Per jenis KPI:');
        foreach ($byAgg as $a => $c) { $this->line('  ' . str_pad($a, 12) . " $c"); }
        if ($skipped) {
            $this->warn('Dept dilewati (belum ada di sistem):');
            foreach ($skipped as $d => $c) { $this->line("  - $d ($c)"); }
        }
        $this->info("Preview lengkap: $previewPath");

        // ── Commit ──────────────────────────────────────────────────────────
        if (! $this->option('commit')) {
            $this->warn('DRY-RUN. Periksa CSV di atas. Tambahkan --commit untuk menulis ke DB.');
            return self::SUCCESS;
        }

        // Idempotent: hapus dulu semua KPI hasil impor sebelumnya (penanda di notes).
        $deleted = KpiTarget::where('notes', 'like', '%' . self::MARK . '%')->delete();
        if ($deleted) { $this->line("Menghapus $deleted KPI hasil impor sebelumnya."); }

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
                    'notes'        => self::MARK . " (posisi: {$r['position']}; target asli: {$r['target']})",
                ]
            );
            $written++;
        }
        $this->info("Commit selesai. $written KPI dept (L2) ditulis untuk periode $month/$year.");

        return self::SUCCESS;
    }

    /** Minta Groq klasifikasi satu batch → [indexAsli => [aggregation,target_value,unit]]. */
    private function classifyBatch(array $chunk): array
    {
        $items = [];
        foreach ($chunk as $idx => $r) {
            $items[] = ['i' => $idx, 'name' => $r['name'], 'target' => $r['target']];
        }
        $json = json_encode($items, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Klasifikasikan tiap KPI ke sistem dengan 4 jenis agregasi:
- "sum": total yang DIJUMLAHKAN dari tiap staff (jumlah deal, jumlah siswa, revenue, jumlah leads, jumlah MOU).
- "average": rasio/persentase yang DIRATA-RATA antar staff (completion rate, conversion rate, skor kualitas per orang).
- "shared": satu metrik TIM yang tak dibagi per staff (uptime, SLA, NPS, CAC, ROAS, response/resolution time, security incident).
- "milestone": target kualitatif/biner/progress ATAU non-numerik ("Sesuai target", "Trend naik", "Sesuai baseline", "≥ B grade"). Diukur progress 0-100%.

Untuk tiap item tentukan:
- aggregation: sum|average|shared|milestone
- target_value: HANYA angka (buang ≥, ≤, ~, teks, satuan, dan keterangan periode). Untuk milestone/non-numerik pakai 100.
- unit: satuan singkat diperkirakan dari nama & target (%, kontrak, siswa, Rp, leads, MOU, skor, jam, bug). Untuk milestone pakai "%".

Data:
$json

Jawab HANYA array JSON tanpa markdown: [{"i":<index>,"aggregation":"..","target_value":<angka>,"unit":".."}]
PROMPT;

        $raw = $this->callGroq($prompt);
        if (! $raw) { return []; }

        $clean = trim(preg_replace('/```json\s*|\s*```/', '', $raw));
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
