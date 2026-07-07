<?php

namespace App\Console\Commands;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

/**
 * Impor laporan harian historis dari Google Form "Daily Performance Report".
 *
 * File tidak berisi target — hanya laporan harian. Karena skema kita mewajibkan
 * daily_task_entry menempel ke sebuah monthly_target, importer membuat target
 * "wadah" per (user, bulan) berjudul "Aktivitas Harian (Impor) — <Bulan> <Tahun>",
 * lalu memasukkan tiap baris sebagai satu laporan harian.
 *
 * Idempotent: setiap run menghapus dulu semua target impor sebelumnya
 * (beserta laporan hariannya via cascade), lalu mengimpor ulang.
 */
class ImportDailyReports extends Command
{
    protected $signature = 'import:daily-reports {file : Path ke file .xlsx Form Responses}';

    protected $description = 'Impor laporan harian historis dari Google Form ke daily_task_entries';

    /** Peta alias nama (dinormalisasi) → email user di sistem. */
    private array $emailAliases = [
        'eka'                            => 'eka.maxy.academy@gmail.com',
        'eka kurnia'                     => 'eka.maxy.academy@gmail.com',
        'elroy pemerena karosekali'      => 'elroy.maxy.academy@gmail.com',
        'fanny anjelika'                 => 'fanny.maxy.academy@gmail.com',
        'fannyanjelika'                  => 'fanny.maxy.academy@gmail.com',
        'ghufron bagaskara'              => 'ghufron.maxy.academy@gmail.com',
        'ika'                            => 'ika.maxy.academy@gmail.com',
        'jessica'                        => 'jessica.maxy.academy@gmail.com',
        'jessica charisma'               => 'jessica.maxy.academy@gmail.com',
        'jessicac'                       => 'jessica.maxy.academy@gmail.com',
        'jessica maria p waworuntu'      => 'jessicamaria.maxy.academy@gmail.com',
        'joseph'                         => 'joseph.maxy.academy@gmail.com',
        'joseph christian'               => 'joseph.maxy.academy@gmail.com',
        'joseph christian seraf sasongko' => 'joseph.maxy.academy@gmail.com',
        'joseph christian ss'            => 'joseph.maxy.academy@gmail.com',
        'kartika saraswati'              => 'kartika.maxy.academy@gmail.com',
        'matthew'                        => 'matthew.maxy.academy@gmail.com',
        'olivia'                         => 'olivia.maxy.academy@gmail.com',
        'olivia abigail'                 => 'olivia.maxy.academy@gmail.com',
        'olivia abigail silitonga'       => 'olivia.maxy.academy@gmail.com',
        'stefen'                         => 'stefen.maxy.academy@gmail.com',
        'sydney rosalind'                => 'sydneyrosalind.maxy.academy@gmail.com',
        'sydney rosaind'                 => 'sydneyrosalind.maxy.academy@gmail.com',
        'bryan austin lontoh'            => 'bryan.maxy.academy@gmail.com',
        'bryan lontoh'                   => 'bryan.maxy.academy@gmail.com',
    ];

    private const MONTHS_ID = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: $file");
            return self::FAILURE;
        }

        // Preload users by email.
        $usersByEmail = User::pluck('id', 'email')->all();

        // Bersihkan impor sebelumnya (idempotent).
        $deleted = MonthlyTarget::where('title', 'like', 'Aktivitas Harian (Impor)%')->get();
        foreach ($deleted as $mt) {
            $mt->delete(); // cascade → daily entries ikut terhapus
        }
        if ($deleted->count()) {
            $this->line("Menghapus {$deleted->count()} target impor lama (beserta laporannya).");
        }

        $this->info('Membaca file...');
        $sheet = IOFactory::load($file)->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, true); // key kolom A,B,C...

        $containers = [];   // "userId-year-month" => MonthlyTarget id
        $imported   = 0;
        $skipped    = [];   // nama tak dikenal => count
        $noUser     = [];   // email teralias tapi user belum ada
        $perUser    = [];

        $first = true;
        foreach ($rows as $row) {
            if ($first) { $first = false; continue; } // header

            $name = trim((string) ($row['D'] ?? ''));
            if ($name === '') { continue; }

            $key   = $this->normalize($name);
            $email = $this->emailAliases[$key] ?? null;
            if (! $email) { $skipped[$name] = ($skipped[$name] ?? 0) + 1; continue; }

            $userId = $usersByEmail[$email] ?? null;
            if (! $userId) { $noUser[$email] = ($noUser[$email] ?? 0) + 1; continue; }

            $date = $this->parseDate($row['B'] ?? null);
            if (! $date) { continue; }

            $achievements = trim((string) ($row['E'] ?? ''));
            $description  = $achievements !== '' ? $achievements : '-';
            $notes        = $this->buildNotes($row);

            $ckey = "{$userId}-{$date->year}-{$date->month}";
            if (! isset($containers[$ckey])) {
                $user  = User::find($userId);
                $title = 'Aktivitas Harian (Impor) — ' . self::MONTHS_ID[$date->month] . ' ' . $date->year;
                $mt = MonthlyTarget::firstOrCreate(
                    ['user_id' => $userId, 'month' => $date->month, 'year' => $date->year, 'title' => $title],
                    [
                        'assigned_to' => $userId,
                        'department'  => $user?->department,
                        'description' => 'Wadah laporan harian hasil impor dari Google Form.',
                    ]
                );
                $containers[$ckey] = $mt->id;
            }

            DailyTaskEntry::create([
                'user_id'             => $userId,
                'monthly_target_id'   => $containers[$ckey],
                'weekly_target_id'    => null,
                'task_description'    => $description,
                'priority'            => 'medium',
                'duration_minutes'    => 0,
                'status'              => 'selesai',
                'notes'               => $notes ?: null,
                'task_date'           => $date->toDateString(),
                'verification_status' => 'approved',
                'verified_at'         => $date->toDateTimeString(),
            ]);

            $imported++;
            $perUser[$name] = ($perUser[$name] ?? 0) + 1;
        }

        $this->newLine();
        $this->info("Selesai. {$imported} laporan diimpor ke " . count($containers) . ' target wadah.');

        if ($skipped) {
            $this->warn('Nama TAK dikenal (dilewati):');
            foreach ($skipped as $n => $c) { $this->line("  - {$n} ({$c})"); }
        }
        if ($noUser) {
            $this->warn('Email teralias tapi user belum ada di DB (dilewati):');
            foreach ($noUser as $e => $c) { $this->line("  - {$e} ({$c})"); }
        }

        return self::SUCCESS;
    }

    private function normalize(string $name): string
    {
        $n = mb_strtolower(trim($name));
        $n = str_replace(['.', ','], '', $n);
        return preg_replace('/\s+/', ' ', $n);
    }

    /** Terima Excel serial (numeric), DateTime, atau string tanggal. */
    private function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') { return null; }
        try {
            if (is_numeric($value)) {
                return Carbon::instance(XlsDate::excelToDateTimeObject((float) $value));
            }
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Gabungkan Metrics/Challenges/Priorities/Support/Evidence ke satu catatan. */
    private function buildNotes(array $row): string
    {
        $parts = [
            'Metrics & Targets'    => $row['F'] ?? null,
            'Challenges'           => $row['G'] ?? null,
            '3 Prioritas Besok'    => $row['H'] ?? null,
            'Support Needed'       => $row['I'] ?? null,
            'Evidence'             => $row['J'] ?? null,
        ];
        $out = [];
        foreach ($parts as $label => $val) {
            $val = trim((string) $val);
            if ($val !== '' && $val !== '-') {
                $out[] = "{$label}: {$val}";
            }
        }
        return implode("\n\n", $out);
    }
}
