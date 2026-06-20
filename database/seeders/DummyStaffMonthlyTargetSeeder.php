<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Models\DailyTaskEntry;
use App\Models\User;

class DummyStaffMonthlyTargetSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ambil staf yang ada ──────────────────────────────────────────────
        $staffList = User::where('role', 'staff')->where('is_active', true)->get();

        if ($staffList->isEmpty()) {
            $this->command->warn('Tidak ada staf aktif. Dummy tidak dibuat.');
            return;
        }

        $leader = User::where('role', 'leader')->where('is_active', true)->first()
               ?? User::where('role', 'c_level')->where('is_active', true)->first();

        if (!$leader) {
            $this->command->warn('Tidak ada leader. Dummy tidak dibuat.');
            return;
        }

        $now       = now();
        $month     = (int) $now->month;
        $year      = (int) $now->year;
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;

        // Template per departemen
        $targetTemplates = [
            'sales'           => [['title'=>'Closing Deal Bulanan',      'desc'=>'Target minimal 50 closing deal bulan ini'],
                                  ['title'=>'Follow Up & Nurturing Leads','desc'=>'Follow up semua leads yang belum diproses']],
            'marketing'       => [['title'=>'Campaign 3 Kota',            'desc'=>'Jalankan campaign di 3 kota besar'],
                                  ['title'=>'Konten Media Sosial',        'desc'=>'Minimal 20 konten IG, TikTok, LinkedIn']],
            'operational'     => [['title'=>'Optimasi SOP Internal',      'desc'=>'Review dan update SOP yang berjalan'],
                                  ['title'=>'Laporan Operasional Bulanan','desc'=>'Rekap laporan harian dan mingguan tim']],
            'hr'              => [['title'=>'Rekrutmen & Interview',       'desc'=>'Interview minimal 10 kandidat posisi kosong'],
                                  ['title'=>'Onboarding Karyawan Baru',   'desc'=>'Siapkan proses onboarding bulan ini']],
            'finance'         => [['title'=>'Rekap Keuangan Bulanan',     'desc'=>'Laporan keuangan lengkap akhir bulan'],
                                  ['title'=>'Audit Transaksi Internal',   'desc'=>'Audit semua transaksi bulan berjalan']],
            'product_it'      => [['title'=>'Sprint Development',         'desc'=>'Selesaikan sprint backlog bulan ini'],
                                  ['title'=>'Bug Fixing & QA',            'desc'=>'Tutup semua tiket bug prioritas tinggi']],
            'creative'        => [['title'=>'Desain Materi Promosi',      'desc'=>'Semua materi desain untuk campaign'],
                                  ['title'=>'Produksi Video Konten',      'desc'=>'Minimal 4 video konten bulanan']],
            'customer_support'=> [['title'=>'Resolusi Tiket CS',          'desc'=>'Selesaikan semua tiket customer support'],
                                  ['title'=>'Survei Kepuasan Customer',   'desc'=>'Jalankan survei NPS ke customer aktif']],
        ];

        $weeklyTitles = [
            'Perencanaan & Persiapan',
            'Eksekusi Tahap 1',
            'Eksekusi Tahap 2',
            'Review & Evaluasi',
        ];

        $reportPool = [
            'Sudah follow up 5 klien hari ini, 3 deal berhasil closing.',
            'Meeting internal briefing target minggu ini bersama tim.',
            'Buat proposal untuk klien baru, masih dalam proses review.',
            'Presentasi ke calon klien, hasilnya positif dan lanjut negosiasi.',
            'Update database leads dan data klien yang sudah masuk.',
            'Review hasil campaign kemarin dan adjust strategi untuk besok.',
            'Koordinasi dengan tim lain untuk kebutuhan project berjalan.',
            'Cek dan rekap laporan harian, input semua data ke sistem.',
            'Menyelesaikan dokumentasi pekerjaan minggu ini.',
            'Diskusi dengan leader terkait progress dan kendala yang dihadapi.',
        ];

        $created = 0;

        foreach ($staffList->take(6) as $idx => $staff) {
            $dept   = $staff->department ?? 'operational';
            $tmpls  = $targetTemplates[$dept] ?? $targetTemplates['operational'];

            foreach ([$month => $year, $prevMonth => $prevYear] as $mo => $yr) {
                $tmpl = $tmpls[$idx % count($tmpls)];

                // Skip jika sudah ada
                if (MonthlyTarget::where('assigned_to', $staff->id)->where('month', $mo)->where('year', $yr)->exists()) {
                    $this->command->line("  Skip: {$staff->name} {$mo}/{$yr} sudah ada.");
                    continue;
                }

                $mt = MonthlyTarget::create([
                    'user_id'     => $leader->id,
                    'assigned_to' => $staff->id,
                    'department'  => $dept,
                    'title'       => $tmpl['title'],
                    'description' => $tmpl['desc'],
                    'month'       => $mo,
                    'year'        => $yr,
                ]);

                // Buat 2–3 weekly targets
                $weekCount = ($mo === $month) ? 3 : 2;
                for ($w = 1; $w <= $weekCount; $w++) {
                    $wt = WeeklyTarget::create([
                        'monthly_target_id' => $mt->id,
                        'user_id'           => $leader->id,   // pembuat = leader
                        'assigned_to'       => $staff->id,
                        'title'             => ($weeklyTitles[$w-1] ?? "Minggu $w") . " — " . $tmpl['title'],
                        'description'       => "Target minggu ke-{$w} dalam rangka {$tmpl['title']}.",
                        'week_number'       => $w,
                        'month'             => $mo,
                        'year'              => $yr,
                        'target_type'       => ($w % 2 === 0) ? 'quantitative' : 'qualitative',
                        'target_value'      => ($w % 2 === 0) ? rand(5, 20) : null,
                        'target_unit'       => ($w % 2 === 0) ? 'item' : null,
                        'category'          => 'planned',
                        'impact_level'      => ['low','medium','high'][rand(0,2)],
                    ]);

                    // Buat 2–4 daily task entries
                    $entryCount = rand(2, 4);
                    for ($d = 0; $d < $entryCount; $d++) {
                        $isBulanLalu = ($mo === $prevMonth);
                        $status  = $isBulanLalu ? 'selesai' : (['selesai','selesai','pending'])[rand(0,2)];
                        $verif   = $isBulanLalu
                            ? 'approved'
                            : ($status === 'selesai' ? ['approved','pending'][rand(0,1)] : 'pending');
                        $day     = rand(1, min(28, cal_days_in_month(CAL_GREGORIAN, $mo, $yr)));

                        DailyTaskEntry::create([
                            'user_id'             => $staff->id,
                            'weekly_target_id'    => $wt->id,
                            'monthly_target_id'   => $mt->id,
                            'task_description'    => $reportPool[rand(0, count($reportPool)-1)],
                            'duration_minutes'    => rand(30, 180),
                            'status'              => $status,
                            'verification_status' => $verif,
                            'task_date'           => sprintf('%04d-%02d-%02d', $yr, $mo, $day),
                            'percent_done'        => $status === 'selesai' ? 100 : rand(20, 80),
                            'priority'            => ['low','medium','high'][rand(0,2)],
                        ]);
                    }
                }

                $this->command->info("  ✅ {$staff->name} ({$dept}) — {$tmpl['title']} {$mo}/{$yr} | {$weekCount} weekly target");
                $created++;
            }
        }

        $this->command->newLine();
        $this->command->info("Selesai! Total {$created} monthly target dummy dibuat.");
    }
}
