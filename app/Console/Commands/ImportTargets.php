<?php

namespace App\Console\Commands;

use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor target bulanan + mingguan dari "Report HR (Bulanan & Mingguan ONLY).xlsx".
 *
 * File berisi 1 sheet per orang, kolom: Bulan | Target Bulanan | Target Mingguan | Status.
 * Hanya baris ber-"Bulan = Mei" yang bersih (kolom mingguannya target asli).
 * Baris ber-tanggal berisi fragmen laporan harian → DILEWATI.
 *
 * Hanya sheet "lengkap" (punya target bulanan DAN mingguan pada baris Mei) yang
 * diimpor. Tiap Target Bulanan → 1 monthly_target; tiap Target Mingguan → 1
 * weekly_target di bawah monthly pertama orang itu. Periode: Mei 2026.
 *
 * Idempotent (cleanup via penanda). Default DRY-RUN; --commit untuk menulis.
 */
class ImportTargets extends Command
{
    protected $signature = 'import:targets {file : Path ke Report HR (Bulanan & Mingguan ONLY).xlsx} {--commit}';

    protected $description = 'Impor target bulanan + mingguan (baris Mei yang lengkap) dari file HR';

    private const MARK  = 'Impor Target HR';
    private const MONTH = 5;   // Mei
    private const YEAR  = 2026;

    /** Nama sheet PERSIS → email user (hanya sheet lengkap & bersih). */
    private array $aliasMap = [
        'Wempi Darwis Napitupulu'         => 'wempi.maxy.academy@gmail.com',
        'Sydney Yuanita'                  => 'yua.maxy.academy@gmail.com',
        'Maria Felicia Widyawati'         => 'feli.maxy.academy@gmail.com',
        'Matthew Chandra Gregorious Pali' => 'matthew.maxy.academy@gmail.com',
        'Regina Sydney Rosalind'          => 'sydneyrosalind.maxy.academy@gmail.com',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: $file");
            return self::FAILURE;
        }

        $this->info('Membaca file...');
        // Hemat memori: hanya muat sheet yang relevan + data saja (tanpa style).
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(array_keys($this->aliasMap));
        $book = $reader->load($file);
        $usersByEmail = User::pluck('id', 'email')->all();

        $qualifying = [];
        $skipped = [];

        foreach ($book->getWorksheetIterator() as $sheet) {
            $title = $sheet->getTitle();
            $rows  = $sheet->toArray(null, true, false, false);

            $monthly = [];
            $weekly  = [];
            foreach ($rows as $i => $r) {
                if ($i === 0) { continue; } // header
                $bulan = trim((string) ($r[0] ?? ''));
                if ($bulan !== 'Mei') { continue; } // hanya baris Mei (bersih)
                $tb = trim((string) ($r[1] ?? ''));
                $tm = trim((string) ($r[2] ?? ''));
                if ($tb !== '') { $monthly[] = $tb; }
                if ($tm !== '') { $weekly[]  = $tm; }
            }

            // Hanya yang "lengkap": ada bulanan DAN mingguan.
            if (! $monthly || ! $weekly) {
                if ($monthly || $weekly) { $skipped[$title] = 'tak lengkap (Mei)'; }
                continue;
            }

            $email = $this->aliasMap[$title] ?? null;
            $userId = $email ? ($usersByEmail[$email] ?? null) : null;
            if (! $userId) {
                $skipped[$title] = 'user belum ada / belum di-alias';
                continue;
            }

            $qualifying[] = [
                'sheet'   => $title,
                'user_id' => $userId,
                'monthly' => $monthly,
                'weekly'  => $weekly,
            ];
        }

        $this->newLine();
        $this->info('Sheet lengkap yang akan diimpor: ' . count($qualifying));
        foreach ($qualifying as $q) {
            $u = User::find($q['user_id']);
            $this->line('  - ' . str_pad($q['sheet'], 32) . ' [' . $u->department . '] '
                . count($q['monthly']) . ' bulanan, ' . count($q['weekly']) . ' mingguan');
        }
        if ($skipped) {
            $this->warn('Dilewati:');
            foreach ($skipped as $s => $why) { $this->line("  - $s — $why"); }
        }

        if (! $this->option('commit')) {
            $this->newLine();
            $this->warn('DRY-RUN. Tambahkan --commit untuk menulis ke DB.');
            return self::SUCCESS;
        }

        // Idempotent: hapus target impor sebelumnya (cascade → weekly & daily).
        $del = MonthlyTarget::where('description', 'like', '%' . self::MARK . '%')->get();
        foreach ($del as $mt) { $mt->delete(); }
        if ($del->count()) { $this->line("Menghapus {$del->count()} target impor sebelumnya."); }

        // Penetap target = Admin HR (super_admin). Halaman staff hanya menampilkan
        // target yang dibuat leader/super_admin, jadi ini WAJIB (sales tak ada leader).
        $adminId = User::where('role', 'super_admin')->value('id');

        $mtCount = $wtCount = 0;
        foreach ($qualifying as $q) {
            $user = User::find($q['user_id']);

            // Satu monthly target "payung" per orang: semua sasaran bulanan disusun di
            // deskripsi, semua weekly tergantung di sini (staff-view butuh ≥1 weekly).
            $goals = collect($q['monthly'])->map(fn ($t, $i) => ($i + 1) . '. ' . $t)->implode("\n");
            $mt = MonthlyTarget::create([
                'user_id'     => $adminId ?? $user->id,
                'assigned_to' => $user->id,
                'department'  => $user->department,
                'title'       => \Illuminate\Support\Str::limit($q['monthly'][0], 120, '…'),
                'description' => "Sasaran bulanan Mei 2026:\n" . $goals . "\n\n[" . self::MARK . ']',
                'month'       => self::MONTH,
                'year'        => self::YEAR,
            ]);
            $mtCount++;

            $week = 1;
            foreach ($q['weekly'] as $text) {
                WeeklyTarget::create([
                    'monthly_target_id' => $mt->id,
                    'week_number'       => $week,
                    'title'             => \Illuminate\Support\Str::limit($text, 150, '…'),
                    'user_id'           => $adminId ?? $user->id,
                    'assigned_to'       => $user->id,
                    'category'          => 'planned',
                    'impact_level'      => 'medium',
                    'target_type'       => 'qualitative',
                    'description'       => '[' . self::MARK . ']',
                    'month'             => self::MONTH,
                    'year'              => self::YEAR,
                ]);
                $week = min($week + 1, 5);
                $wtCount++;
            }
        }

        $this->newLine();
        $this->info("Commit selesai. $mtCount target bulanan + $wtCount target mingguan (Mei 2026).");

        return self::SUCCESS;
    }
}
