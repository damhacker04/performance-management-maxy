<?php

namespace Database\Seeders;

use App\Models\AiEvaluation;
use App\Models\AppNotification;
use App\Models\BackdateRequest;
use App\Models\DailyTaskEntry;
use App\Models\DailyTaskEvidence;
use App\Models\GapAnalysisReport;
use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\KpiWeightSetting;
use App\Models\LeaderOverride;
use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use App\Models\WorkloadReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UseCaseSeeder extends Seeder
{
    private int $currentMonth;
    private int $currentYear;
    private int $prevMonth;
    private int $prevYear;

    private ?int $staffRejectedEntryId = null;

    public function run(): void
    {
        $now = now();
        $this->currentMonth = (int) $now->month;
        $this->currentYear  = (int) $now->year;
        $this->prevMonth    = $this->currentMonth === 1 ? 12 : $this->currentMonth - 1;
        $this->prevYear     = $this->currentMonth === 1 ? $this->currentYear - 1 : $this->currentYear;

        $cLevel  = User::where('email', 'isaac.maxy.academy@gmail.com')->firstOrFail();
        $admin   = User::where('email', 'adminhr.maxy.academy@gmail.com')->firstOrFail();

        $leaderOp = User::where('email', 'leader.operational@maxy.academy')->firstOrFail();
        $leaderSales = User::where('email', 'rangga.maxy.academy@gmail.com')->firstOrFail();
        $leaderMkt = User::where('email', 'maya.maxy.academy@gmail.com')->firstOrFail();

        $staffOp = User::where('email', 'staff.testing@maxy.academy')->firstOrFail();
        $staffGhufron = User::where('email', 'ghufron.maxy.academy@gmail.com')->firstOrFail();

        $this->command->info('=== UseCaseSeeder ===');

        $this->seedKpiWeightSettings($admin);

        $kpisL2Prev = [];
        $kpisL2Current = [];

        foreach (['operational', 'sales', 'marketing'] as $dept) {
            $kpisL2Prev[$dept] = $this->seedKpiL2($cLevel, $dept, $this->prevMonth, $this->prevYear);
            $kpisL2Current[$dept] = $this->seedKpiL2($cLevel, $dept, $this->currentMonth, $this->currentYear);
        }

        $kpisL3Prev = $this->seedKpiL3($cLevel, $leaderOp, $leaderSales, $leaderMkt, $staffOp, $staffGhufron, $kpisL2Prev, $this->prevMonth, $this->prevYear);
        $kpisL3Current = $this->seedKpiL3($cLevel, $leaderOp, $leaderSales, $leaderMkt, $staffOp, $staffGhufron, $kpisL2Current, $this->currentMonth, $this->currentYear);

        $this->seedKpiActuals($cLevel, $kpisL3Prev, $this->prevMonth, $this->prevYear);

        $this->seedDeptLevelKpis($cLevel);

        $this->seedStaffTargetsAndTasks($leaderOp, $staffOp, $kpisL3Current, $kpisL3Prev);
        $this->seedStaffGhufronTargetsAndTasks($leaderOp, $staffGhufron, $kpisL3Current, $kpisL3Prev);

        $this->seedCeoToLeaderTargets($cLevel, $leaderOp, $kpisL3Current, $kpisL3Prev);
        $this->seedCeoToSalesLeaderTargets($cLevel, $leaderSales, $kpisL3Current, $kpisL3Prev);
        $this->seedCeoToMktLeaderTargets($cLevel, $leaderMkt, $kpisL3Current, $kpisL3Prev);

        $this->seedWorkloadReports($staffOp, $leaderOp, $leaderSales);

        $this->seedBackdateRequests($leaderOp, $staffOp);

        $this->seedNotifications($cLevel, $leaderOp, $staffOp);

        $this->command->info('=== Selesai! ===');
    }

    private function seedKpiWeightSettings(User $admin): void
    {
        KpiWeightSetting::create([
            'department_id'          => null,
            'weight_achievement'     => 30,
            'weight_efficiency'      => 25,
            'weight_contribution'    => 25,
            'weight_problem_solving' => 20,
            'set_by'                 => $admin->id,
            'effective_from'         => '2026-01-01',
            'is_active'              => true,
        ]);
        $this->command->info('  ✅ KPI Weight Settings (global, aktif)');
    }

    private function seedKpiL2(User $cLevel, string $dept, int $month, int $year): array
    {
        $kpiDefs = [
            'operational' => [
                ['name' => 'Penyelesaian SOP & Dokumentasi',  'value' => 10, 'unit' => 'dokumen'],
                ['name' => 'Ketepatan Waktu Pelaporan',        'value' => 95, 'unit' => '%'],
            ],
            'sales' => [
                ['name' => 'Closing Deal Bulanan',            'value' => 40, 'unit' => 'deal'],
                ['name' => 'Persentase Konversi Leads',       'value' => 15, 'unit' => '%'],
            ],
            'marketing' => [
                ['name' => 'Pencapaian Reach Campaign',       'value' => 50000, 'unit' => 'reach'],
                ['name' => 'Produksi Konten Media Sosial',    'value' => 20, 'unit' => 'konten'],
            ]
        ];

        $result = [];
        $defs = $kpiDefs[$dept] ?? [];
        foreach ($defs as $def) {
            $result[] = KpiTarget::create([
                'kpi_level'    => 2,
                'aggregation'  => 'sum',
                'department'   => $dept,
                'kpi_name'     => $def['name'],
                'target_value' => $def['value'],
                'unit'         => $def['unit'],
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);
        }
        $this->command->info("  ✅ KPI L2 {$dept} {$month}/{$year} (" . count($result) . " KPI)");
        return $result;
    }

    private function seedKpiL3(User $cLevel, User $leaderOp, User $leaderSales, User $leaderMkt, User $staffOp, User $staffGhufron, array $kpiL2Depts, int $month, int $year): array
    {
        $result = [];

        $opsL2 = $kpiL2Depts['operational'] ?? [];
        if (count($opsL2) >= 2) {

            $result['leader_op_kpi1'] = KpiTarget::create([
                'parent_id'    => $opsL2[0]->id,
                'kpi_level'    => 3,
                'aggregation'  => 'sum',
                'department'   => 'operational',
                'user_id'      => $leaderOp->id,
                'kpi_name'     => "Target Leader — " . $opsL2[0]->kpi_name,
                'target_value' => 3.0,
                'unit'         => $opsL2[0]->unit,
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);

            $result['leader_op_kpi2'] = KpiTarget::create([
                'parent_id'    => $opsL2[1]->id,
                'kpi_level'    => 3,
                'aggregation'  => 'sum',
                'department'   => 'operational',
                'user_id'      => $leaderOp->id,
                'kpi_name'     => "Target Leader — " . $opsL2[1]->kpi_name,
                'target_value' => 98.0,
                'unit'         => $opsL2[1]->unit,
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);

            $result['staff_op_kpi1'] = KpiTarget::create([
                'parent_id'    => $opsL2[0]->id,
                'kpi_level'    => 3,
                'aggregation'  => 'sum',
                'department'   => 'operational',
                'user_id'      => $staffOp->id,
                'kpi_name'     => "Target Staf — " . $opsL2[0]->kpi_name,
                'target_value' => 2.0,
                'unit'         => $opsL2[0]->unit,
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);

            $result['staff_ghufron_kpi1'] = KpiTarget::create([
                'parent_id'    => $opsL2[0]->id,
                'kpi_level'    => 3,
                'aggregation'  => 'sum',
                'department'   => 'operational',
                'user_id'      => $staffGhufron->id,
                'kpi_name'     => "Target Staf — " . $opsL2[0]->kpi_name,
                'target_value' => 2.0,
                'unit'         => $opsL2[0]->unit,
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);
        }

        $salesL2 = $kpiL2Depts['sales'] ?? [];
        if (count($salesL2) >= 1) {

            $result['leader_sales_kpi1'] = KpiTarget::create([
                'parent_id'    => $salesL2[0]->id,
                'kpi_level'    => 3,
                'aggregation'  => 'sum',
                'department'   => 'sales',
                'user_id'      => $leaderSales->id,
                'kpi_name'     => "Target Leader Sales — " . $salesL2[0]->kpi_name,
                'target_value' => 15.0,
                'unit'         => $salesL2[0]->unit,
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);
        }

        $mktL2 = $kpiL2Depts['marketing'] ?? [];
        if (count($mktL2) >= 1) {

            $result['leader_mkt_kpi1'] = KpiTarget::create([
                'parent_id'    => $mktL2[0]->id,
                'kpi_level'    => 3,
                'aggregation'  => 'sum',
                'department'   => 'marketing',
                'user_id'      => $leaderMkt->id,
                'kpi_name'     => "Target Leader Marketing — " . $mktL2[0]->kpi_name,
                'target_value' => 20000.0,
                'unit'         => $mktL2[0]->unit,
                'month'        => $month,
                'year'         => $year,
                'set_by'       => $cLevel->id,
                'is_active'    => true,
            ]);
        }

        $this->command->info("  ✅ KPI L3 Staff & Leader {$month}/{$year} (" . count($result) . " L3 targets)");
        return $result;
    }

    private function seedKpiActuals(User $cLevel, array $kpisL3Prev, int $month, int $year): void
    {
        $count = 0;
        foreach ($kpisL3Prev as $key => $kpiTarget) {

            $mult = str_contains($key, 'leader') ? rand(90, 110) : rand(70, 105);
            $achievement = $kpiTarget->target_value * ($mult / 100);

            KpiActual::create([
                'kpi_target_id' => $kpiTarget->id,
                'staff_id'      => $kpiTarget->user_id,
                'department'    => $kpiTarget->department,
                'month'         => $month,
                'year'          => $year,
                'actual_value'  => round($achievement, 1),
                'source'        => 'manual',
                'notes'         => 'Realisasi bulan lalu diverifikasi oleh C-Level.',
                'created_by'    => $cLevel->id,
            ]);
            $count++;
        }
        $this->command->info("  ✅ KPI Actuals {$month}/{$year} ({$count} entri)");
    }

    private function seedDeptLevelKpis(User $cLevel): void
    {
        $shared = KpiTarget::create([
            'kpi_level'    => 2,
            'aggregation'  => 'shared',
            'department'   => 'operational',
            'kpi_name'     => 'Kepuasan Layanan Internal (Tim)',
            'target_value' => 90,
            'unit'         => '%',
            'month'        => $this->currentMonth,
            'year'         => $this->currentYear,
            'set_by'       => $cLevel->id,
            'is_active'    => true,
        ]);

        KpiActual::create([
            'kpi_target_id' => $shared->id,
            'staff_id'      => null,
            'department'    => 'operational',
            'month'         => $this->currentMonth,
            'year'          => $this->currentYear,
            'actual_value'  => 82,
            'source'        => 'manual',
            'notes'         => 'Realisasi survei kepuasan layanan internal bulan berjalan.',
            'created_by'    => $cLevel->id,
        ]);

        $milestone = KpiTarget::create([
            'kpi_level'    => 2,
            'aggregation'  => 'milestone',
            'department'   => 'operational',
            'kpi_name'     => 'Peluncuran Sistem Manajemen Aset',
            'target_value' => 100,
            'unit'         => '%',
            'month'        => $this->currentMonth,
            'year'         => $this->currentYear,
            'set_by'       => $cLevel->id,
            'is_active'    => true,
        ]);

        KpiActual::create([
            'kpi_target_id' => $milestone->id,
            'staff_id'      => null,
            'department'    => 'operational',
            'month'         => $this->currentMonth,
            'year'          => $this->currentYear,
            'actual_value'  => 65,
            'source'        => 'manual',
            'notes'         => 'Progress implementasi tahap konfigurasi & migrasi data.',
            'created_by'    => $cLevel->id,
        ]);

        $this->command->info('  ✅ KPI Level Departemen (shared & milestone) seeded');
    }

    private function seedStaffTargetsAndTasks(User $leader, User $staff, array $kpisL3Current, array $kpisL3Prev): void
    {
        $periods = [
            ['month' => $this->prevMonth,    'year' => $this->prevYear,    'is_current' => false, 'kpi' => $kpisL3Prev['staff_op_kpi1'] ?? null],
            ['month' => $this->currentMonth, 'year' => $this->currentYear, 'is_current' => true,  'kpi' => $kpisL3Current['staff_op_kpi1'] ?? null],
        ];

        foreach ($periods as $p) {

            $mt = MonthlyTarget::create([
                'user_id'       => $leader->id,
                'assigned_to'   => $staff->id,
                'kpi_target_id' => $p['kpi']?->id,
                'department'    => 'operational',
                'title'         => "Penyusunan Dokumentasi & SOP Operasional — " . ($p['is_current'] ? 'Bulan Ini' : 'Periode Lalu'),
                'description'   => "Target menyusun dan merapikan SOP internal Operational sesuai arahan benchmark.",
                'month'         => $p['month'],
                'year'          => $p['year'],
            ]);

            for ($w = 1; $w <= 4; $w++) {
                $isFailedWeek = !$p['is_current'] && $w === 4;

                $wt = WeeklyTarget::create([
                    'monthly_target_id' => $mt->id,
                    'user_id'           => $leader->id,
                    'assigned_to'       => $staff->id,
                    'title'             => "Penyelesaian SOP Sub-divisi Bagian $w",
                    'description'       => "Fokus menyusun dokumen SOP bagian ke-$w dan mengajukan review.",
                    'week_number'       => $w,
                    'month'             => $p['month'],
                    'year'              => $p['year'],
                    'target_type'       => $w % 2 === 0 ? 'quantitative' : 'qualitative',
                    'target_value'      => $w % 2 === 0 ? ($isFailedWeek ? 5.0 : 3.0) : null,
                    'target_unit'       => $w % 2 === 0 ? 'dokumen' : null,
                    'category'          => 'planned',
                    'impact_level'      => $w == 1 ? 'high' : ($w == 2 ? 'medium' : 'low'),
                ]);

                if ($p['is_current']) {

                    if ($w === 1) {

                        $this->createDailyTask($staff, $wt, $mt, 'Membuat draf awal SOP Onboarding GA.', 'selesai', 'approved', $leader, 60, 65, 1);
                        $this->createDailyTask($staff, $wt, $mt, 'Revisi draf SOP Onboarding GA masukan tim.', 'selesai', 'approved', $leader, 90, 80, 2);
                    } elseif ($w === 2) {

                        $this->createDailyTask($staff, $wt, $mt, 'Penyusunan SOP Penanganan Keluhan Karyawan.', 'selesai', 'pending', null, 120, null, 8);

                        $this->createDailyTask($staff, $wt, $mt, 'Finalisasi SOP Inventarisasi Aset Kantor.', 'selesai', 'revision', $leader, 180, null, 9, 'Tolong tambahkan checklist serah terima aset di lampiran.');

                        $rejected = $this->createDailyTask($staff, $wt, $mt, 'Input data inventaris manual di Excel pribadi.', 'selesai', 'rejected', $leader, 90, null, 10, 'Laporan tidak sesuai target SOP dan tanpa bukti pendukung. Mohon ikuti format resmi.');
                        $this->staffRejectedEntryId = $rejected->id;
                    } elseif ($w === 3) {

                        $taskDay1 = DailyTaskEntry::create([
                            'user_id'                 => $staff->id,
                            'weekly_target_id'        => $wt->id,
                            'monthly_target_id'       => $mt->id,
                            'task_description'        => 'Analisis awal kebutuhan SOP Pengadaan Aset (Hari 1).',
                            'priority'                => 'high',
                            'duration_minutes'        => 120,
                            'status'                  => 'dalam_proses',
                            'task_date'               => sprintf('%04d-%02d-%02d', $wt->year, $wt->month, 13),
                            'verification_status'     => 'pending',
                        ]);

                        $taskDay2 = DailyTaskEntry::create([
                            'user_id'                 => $staff->id,
                            'weekly_target_id'        => $wt->id,
                            'monthly_target_id'       => $mt->id,
                            'parent_entry_id'         => $taskDay1->id,
                            'task_description'        => 'Menyusun draf kasar SOP Pengadaan Aset (Hari 2).',
                            'priority'                => 'high',
                            'duration_minutes'        => 180,
                            'status'                  => 'dalam_proses',
                            'task_date'               => sprintf('%04d-%02d-%02d', $wt->year, $wt->month, 14),
                            'verification_status'     => 'pending',
                        ]);

                        $taskDay3 = DailyTaskEntry::create([
                            'user_id'                 => $staff->id,
                            'weekly_target_id'        => $wt->id,
                            'monthly_target_id'       => $mt->id,
                            'parent_entry_id'         => $taskDay2->id,
                            'task_description'        => 'Finalisasi draf SOP Pengadaan Aset dan pengajuan review (Hari 3).',
                            'priority'                => 'high',
                            'duration_minutes'        => 90,
                            'actual_duration_minutes' => 100,
                            'status'                  => 'selesai',
                            'task_date'               => sprintf('%04d-%02d-%02d', $wt->year, $wt->month, 15),
                            'verification_status'     => 'pending',
                        ]);

                        $this->createDailyTask($staff, $wt, $mt, 'Mencari vendor maintenance AC kantor.', 'terhambat', 'pending', null, 60, null, 16, null, 'Menunggu persetujuan budget dari Finance.');
                    } else {

                        $this->createDailyTask($staff, $wt, $mt, 'Sosialisasi SOP baru ke seluruh staf operational.', 'belum_mulai', 'pending', null, 120, null, 23);
                    }
                } else {

                    if ($isFailedWeek) {

                        $this->createDailyTask($staff, $wt, $mt, 'Membuat draf SOP Penggunaan Ruang Rapat.', 'selesai', 'approved', $leader, 120, 130, 22);

                        $this->seedGapAnalysisReport($wt, 'internal',
                            'Staf mengalami kendala pembagian waktu karena ada beban kerja ad-hoc di luar rencana mingguan, sehingga draf dokumen SOP yang terselesaikan hanya 1 dari target 5.',
                            'Disarankan untuk menyederhanakan draf SOP menggunakan template standar agar penulisan lebih cepat, dan leader membantu menyaring tugas ad-hoc.'
                        );
                    } else {

                        $this->createDailyTask($staff, $wt, $mt, 'Merapikan format SOP Inventaris.', 'selesai', 'approved', $leader, 90, 90, $w * 7 - 4);
                        $this->createDailyTask($staff, $wt, $mt, 'Mengunggah seluruh SOP disetujui ke Drive.', 'selesai', 'approved', $leader, 60, 60, $w * 7 - 3);
                    }
                }
            }
        }
        $this->command->info("  ✅ Targets & Tasks Staff Dummy seeded");
    }

    private function seedStaffGhufronTargetsAndTasks(User $leader, User $staff, array $kpisL3Current, array $kpisL3Prev): void
    {

        $p = [
            'month' => $this->currentMonth,
            'year' => $this->currentYear,
            'kpi' => $kpisL3Current['staff_ghufron_kpi1'] ?? null
        ];

        $mt = MonthlyTarget::create([
            'user_id'       => $leader->id,
            'assigned_to'   => $staff->id,
            'kpi_target_id' => $p['kpi']?->id,
            'department'    => 'operational',
            'title'         => "Penyusunan Dokumentasi Hukum Korporasi — Bulan Ini",
            'description'   => "Fokus membantu koordinasi legalitas dan SOP korporat.",
            'month'         => $p['month'],
            'year'          => $p['year'],
        ]);

        $wt = WeeklyTarget::create([
            'monthly_target_id' => $mt->id,
            'user_id'           => $leader->id,
            'assigned_to'       => $staff->id,
            'title'             => "Review Draft Kontrak Kerja Vendor",
            'description'       => "Melakukan review legal draft perjanjian vendor operasional.",
            'week_number'       => 1,
            'month'             => $p['month'],
            'year'              => $p['year'],
            'target_type'       => 'qualitative',
            'category'          => 'planned',
            'impact_level'      => 'high',
        ]);

        $task = $this->createDailyTask($staff, $wt, $mt, 'Analisis draf hukum MOU Vendor Catering.', 'selesai', 'approved', $leader, 120, 110, 2);

        if ($task->aiEvaluation) {
            $aiEval = $task->aiEvaluation;
            $aiEval->update(['is_overridden' => true]);

            LeaderOverride::create([
                'ai_evaluation_id' => $aiEval->id,
                'overridden_by'    => $leader->id,
                'original_score'   => $aiEval->final_score,
                'new_score'        => 9.5,
                'reason'           => 'Analisis draf hukum sangat tajam, berhasil mengidentifikasi 3 klausul berisiko tinggi bagi perusahaan.',
                'overridden_at'    => now()->subDays(1),
            ]);
        }

        $this->createDailyTask($staff, $wt, $mt, 'Mengisi data inventaris manual di Excel.', 'selesai', 'rejected', $leader, 90, null, 3, 'Tugas ini bukan bagian dari target hukum vendor, tolong masukkan ke aktivitas umum.');

        $this->command->info("  ✅ Targets & Tasks Staff Ghufron seeded (with Override & Rejected)");
    }

    private function seedCeoToLeaderTargets(User $cLevel, User $leader, array $kpisL3Current, array $kpisL3Prev): void
    {
        $periods = [
            ['month' => $this->prevMonth,    'year' => $this->prevYear,    'is_current' => false, 'kpi' => $kpisL3Prev['leader_op_kpi1'] ?? null],
            ['month' => $this->currentMonth, 'year' => $this->currentYear, 'is_current' => true,  'kpi' => $kpisL3Current['leader_op_kpi1'] ?? null],
        ];

        foreach ($periods as $p) {

            $mt = MonthlyTarget::create([
                'user_id'       => $cLevel->id,
                'assigned_to'   => $leader->id,
                'kpi_target_id' => $p['kpi']?->id,
                'department'    => 'operational',
                'title'         => "Standardisasi Layanan Operational Divisi — " . ($p['is_current'] ? 'Bulan Ini' : 'Periode Lalu'),
                'description'   => "Meningkatkan efisiensi kerja tim operational dan mengawal target SOP.",
                'month'         => $p['month'],
                'year'          => $p['year'],
            ]);

            $wt = WeeklyTarget::create([
                'monthly_target_id' => $mt->id,
                'user_id'           => $cLevel->id,
                'assigned_to'       => $leader->id,
                'title'             => "Supervisi & Finalisasi SOP Terpadu",
                'description'       => "Melakukan koordinasi review SOP lintas divisi dan finalisasi draf.",
                'week_number'       => 1,
                'month'             => $p['month'],
                'year'              => $p['year'],
                'target_type'       => 'qualitative',
                'category'          => 'planned',
                'impact_level'      => 'high',
            ]);

            if ($p['is_current']) {

                $this->createDailyTask($leader, $wt, $mt, 'Review draf SOP Onboarding yang diajukan staf.', 'selesai', 'approved', $cLevel, 120, 110, 2);

                $this->createDailyTask($leader, $wt, $mt, 'Meeting penyelarasan alur kerja inventaris dengan HR.', 'selesai', 'pending', null, 90, null, 9);
            } else {

                $this->createDailyTask($leader, $wt, $mt, 'Penyelarasan SOP Layanan GA dengan kebutuhan Direksi.', 'selesai', 'approved', $cLevel, 150, 160, 5);
            }
        }
        $this->command->info("  ✅ CEO to Operational Leader Targets & Tasks seeded");
    }

    private function seedCeoToSalesLeaderTargets(User $cLevel, User $leader, array $kpisL3Current, array $kpisL3Prev): void
    {
        $periods = [
            ['month' => $this->prevMonth,    'year' => $this->prevYear,    'is_current' => false, 'kpi' => $kpisL3Prev['leader_sales_kpi1'] ?? null],
            ['month' => $this->currentMonth, 'year' => $this->currentYear, 'is_current' => true,  'kpi' => $kpisL3Current['leader_sales_kpi1'] ?? null],
        ];

        foreach ($periods as $p) {

            $mt = MonthlyTarget::create([
                'user_id'       => $cLevel->id,
                'assigned_to'   => $leader->id,
                'kpi_target_id' => $p['kpi']?->id,
                'department'    => 'sales',
                'title'         => "Peningkatan Konversi Leads Sales — " . ($p['is_current'] ? 'Bulan Ini' : 'Periode Lalu'),
                'description'   => "Mengoptimalkan pipeline prospek leads dan memantau progress tim.",
                'month'         => $p['month'],
                'year'          => $p['year'],
            ]);

            $wt = WeeklyTarget::create([
                'monthly_target_id' => $mt->id,
                'user_id'           => $cLevel->id,
                'assigned_to'       => $leader->id,
                'title'             => "Follow-up Klien Prioritas (Corporate)",
                'description'       => "Melakukan penawaran langsung ke 5 klien instansi besar.",
                'week_number'       => 1,
                'month'             => $p['month'],
                'year'              => $p['year'],
                'target_type'       => 'quantitative',
                'target_value'      => 5.0,
                'target_unit'       => 'klien',
                'category'          => 'planned',
                'impact_level'      => 'high',
            ]);

            if ($p['is_current']) {
                $this->createDailyTask($leader, $wt, $mt, 'Presentasi produk corporate ke instansi A.', 'selesai', 'approved', $cLevel, 120, 120, 3);
            } else {
                $this->createDailyTask($leader, $wt, $mt, 'Negosiasi akhir kerjasama vendor B.', 'selesai', 'approved', $cLevel, 180, 170, 4);
            }
        }
        $this->command->info("  ✅ CEO to Sales Leader Targets & Tasks seeded");
    }

    private function seedCeoToMktLeaderTargets(User $cLevel, User $leader, array $kpisL3Current, array $kpisL3Prev): void
    {
        $periods = [
            ['month' => $this->prevMonth,    'year' => $this->prevYear,    'is_current' => false, 'kpi' => $kpisL3Prev['leader_mkt_kpi1'] ?? null],
            ['month' => $this->currentMonth, 'year' => $this->currentYear, 'is_current' => true,  'kpi' => $kpisL3Current['leader_mkt_kpi1'] ?? null],
        ];

        foreach ($periods as $p) {

            $mt = MonthlyTarget::create([
                'user_id'       => $cLevel->id,
                'assigned_to'   => $leader->id,
                'kpi_target_id' => $p['kpi']?->id,
                'department'    => 'marketing',
                'title'         => "Optimasi Kampanye Digital Promosi — " . ($p['is_current'] ? 'Bulan Ini' : 'Periode Lalu'),
                'description'   => "Meningkatkan reach media sosial dan memantau draf materi promosi.",
                'month'         => $p['month'],
                'year'          => $p['year'],
            ]);

            $wt = WeeklyTarget::create([
                'monthly_target_id' => $mt->id,
                'user_id'           => $cLevel->id,
                'assigned_to'       => $leader->id,
                'title'             => "Pengembangan Materi Promosi & Video Campaign",
                'description'       => "Meluncurkan konten video dan memantau impresinya.",
                'week_number'       => 1,
                'month'             => $p['month'],
                'year'              => $p['year'],
                'target_type'       => 'quantitative',
                'target_value'      => 4.0,
                'target_unit'       => 'konten',
                'category'          => 'planned',
                'impact_level'      => 'high',
            ]);

            if ($p['is_current']) {
                $this->createDailyTask($leader, $wt, $mt, 'Briefing tim desainer grafis terkait promosi.', 'selesai', 'approved', $cLevel, 60, 60, 2);
            } else {
                $this->createDailyTask($leader, $wt, $mt, 'Review analitik reach media sosial minggu 1.', 'selesai', 'approved', $cLevel, 90, 95, 4);
            }
        }
        $this->command->info("  ✅ CEO to Marketing Leader Targets & Tasks seeded");
    }

    private function createDailyTask(
        User $user,
        WeeklyTarget $wt,
        MonthlyTarget $mt,
        string $desc,
        string $status,
        string $verifStatus,
        ?User $verifier,
        int $duration,
        ?int $actualDuration = null,
        int $day = 1,
        ?string $rejectionNote = null,
        ?string $blockReason = null
    ): DailyTaskEntry {
        $taskDate = sprintf('%04d-%02d-%02d', $wt->year, $wt->month, $day);

        $entry = DailyTaskEntry::create([
            'user_id'                 => $user->id,
            'weekly_target_id'        => $wt->id,
            'monthly_target_id'       => $mt->id,
            'task_description'        => $desc,
            'priority'                => 'high',
            'duration_minutes'        => $duration,
            'actual_duration_minutes' => $actualDuration ?? ($status === 'selesai' ? $duration + rand(-10, 20) : null),
            'status'                  => $status,
            'notes'                   => $status === 'selesai' ? 'Pekerjaan selesai dilakukan dengan output lengkap.' : ($status === 'terhambat' ? $blockReason : null),
            'task_date'               => $taskDate,
            'verification_status'     => $verifStatus,
            'verified_by'             => $verifier?->id,
            'verified_at'             => $verifier ? Carbon::parse($taskDate)->addHours(4) : null,
            'reviewed_at'             => $verifStatus === 'revision' ? now()->subHours(2) : ($verifier ? Carbon::parse($taskDate)->addHours(3) : null),
            'rejection_note'          => $rejectionNote,
            'revision_history'        => $verifStatus === 'revision' ? [['at' => now()->subHours(2)->toIso8601String(), 'note' => $rejectionNote]] : null,
        ]);

        if ($status === 'selesai') {
            DailyTaskEvidence::create([
                'daily_task_entry_id' => $entry->id,
                'type'                => 'link',
                'label'               => 'Google Drive Output',
                'path_or_url'         => 'https://drive.google.com/drive/folders/maxy-performance-dummy-' . $entry->id,
            ]);

            DailyTaskEvidence::create([
                'daily_task_entry_id' => $entry->id,
                'type'                => 'image',
                'label'               => 'Screenshot Bukti Kerja',
                'path_or_url'         => 'https://via.placeholder.com/800x600.png?text=Evidence+Task+' . $entry->id,
            ]);

            DailyTaskEvidence::create([
                'daily_task_entry_id' => $entry->id,
                'type'                => 'file',
                'label'               => 'Laporan SOP PDF',
                'path_or_url'         => 'proofs/sop_report_' . $entry->id . '.pdf',
            ]);
        }

        if ($verifStatus === 'approved') {
            $this->createAiEvaluation($entry);
        }

        return $entry;
    }

    private function createAiEvaluation(DailyTaskEntry $entry): AiEvaluation
    {
        $achievement    = round(rand(70, 95) / 10, 1);
        $efficiency     = round(rand(65, 90) / 10, 1);
        $contribution   = round(rand(70, 95) / 10, 1);
        $problemSolving = round(rand(60, 90) / 10, 1);

        $final = round(($achievement * 0.30 + $efficiency * 0.25 + $contribution * 0.25 + $problemSolving * 0.20), 2);

        return AiEvaluation::create([
            'daily_task_entry_id'   => $entry->id,
            'score_achievement'     => $achievement,
            'score_efficiency'      => $efficiency,
            'score_contribution'    => $contribution,
            'score_problem_solving' => $problemSolving,
            'final_score'           => $final,
            'ai_feedback'           => 'Laporan tugas ini didokumentasikan dengan sangat teratur. Durasi pengerjaan logis dan relevansi dengan target mingguan/bulanan sangat kuat.',
            'link_status'           => 'public',
            'is_overridden'         => false,
            'raw_response'          => [
                'model'    => 'gemini-1.5-flash',
                'seeded'   => true,
                'scores'   => compact('achievement', 'efficiency', 'contribution', 'problemSolving'),
            ],
        ]);
    }

    private function seedWorkloadReports(User $staff, User $leaderOp, User $leaderSales): void
    {
        $actors = [
            ['user' => $staff, 'score' => 8.2, 'flag' => 'green', 'desc' => 'Staf menyelesaikan tugas harian dengan efisiensi tinggi, waktu lembur minimal.'],
            ['user' => $leaderOp, 'score' => 7.5, 'flag' => 'yellow', 'desc' => 'Leader Operational memiliki beban koordinasi cukup tinggi di akhir bulan.'],
            ['user' => $leaderSales, 'score' => 5.8, 'flag' => 'red', 'desc' => 'Beban kerja terpantau kritis karena penanganan prospek klien besar yang menumpuk.'],
        ];

        foreach ($actors as $actor) {
            WorkloadReport::create([
                'staff_id'     => $actor['user']->id,
                'month'        => $this->prevMonth,
                'year'         => $this->prevYear,
                'score'        => $actor['score'],
                'summary_flag' => $actor['flag'],
                'report_data'  => [
                    'seeded'       => true,
                    'total_tasks'  => rand(15, 30),
                    'done_tasks'   => rand(12, 28),
                    'score'        => $actor['score'],
                    'summary_flag' => $actor['flag'],
                    'narrative'    => $actor['desc'],
                ],
            ]);
        }
        $this->command->info("  ✅ Workload Reports seeded (for Staff & Leaders)");
    }

    private function seedGapAnalysisReport(WeeklyTarget $wt, string $causeType, string $narrative, string $recommendation): void
    {
        GapAnalysisReport::create([
            'reportable_type' => WeeklyTarget::class,
            'reportable_id'   => $wt->id,
            'root_cause_type' => $causeType,
            'narrative'       => $narrative,
            'recommendation'  => $recommendation,
            'tasks_analyzed'  => $wt->dailyTaskEntries()->count() ?: 1,
            'generated_at'    => now()->subDays(2),
        ]);
    }

    private function seedBackdateRequests(User $leader, User $staff): void
    {

        BackdateRequest::create([
            'user_id'        => $staff->id,
            'requested_date' => now()->subDays(1)->toDateString(),
            'reason'         => 'Kemarin internet rumah bermasalah dari pagi dan tidak bisa akses website kantor.',
            'status'         => 'pending',
        ]);

        BackdateRequest::create([
            'user_id'          => $staff->id,
            'requested_date'   => now()->subDays(2)->toDateString(),
            'reason'           => 'Lupa mengisi logbook harian karena meeting onsite dengan klien GA.',
            'status'           => 'approved',
            'reviewed_by'      => $leader->id,
            'reviewed_at'      => now()->subHours(2),
            'approval_token'   => Str::uuid()->toString(),
            'token_expires_at' => now()->addHours(22),
        ]);

        BackdateRequest::create([
            'user_id'        => $staff->id,
            'requested_date' => now()->subDays(4)->toDateString(),
            'reason'         => 'Lupa.',
            'status'         => 'rejected',
            'reviewed_by'    => $leader->id,
            'reviewed_at'    => now()->subDays(1),
            'rejection_note' => 'Alasan tidak dapat diterima. Permintaan backdate hanya diizinkan untuk alasan darurat/force majeure.',
        ]);

        BackdateRequest::create([
            'user_id'          => $staff->id,
            'requested_date'   => now()->subDays(3)->toDateString(),
            'reason'           => 'Server pelaporan down saat jam pulang kantor.',
            'status'           => 'approved',
            'reviewed_by'      => $leader->id,
            'reviewed_at'      => now()->subDays(2),
            'approval_token'   => Str::uuid()->toString(),
            'token_expires_at' => now()->subHours(2),
        ]);

        $this->command->info("  ✅ Backdate Requests seeded (pending, approved, rejected, expired)");
    }

    private function seedNotifications(User $cLevel, User $leader, User $staff): void
    {

        AppNotification::create([
            'user_id'    => $staff->id,
            'type'       => AppNotification::TYPE_REVISION_REQUESTED,
            'title'      => 'Laporan Perlu Direvisi',
            'body'       => $leader->name . ' meminta kamu merevisi laporan tugas harian. Alasan: Tolong lampirkan file draf checklist.',
            'related_id' => 1,
            'meta'       => [
                'leader_name'    => $leader->name,
                'rejection_note' => 'Tolong lampirkan file draf checklist.',
            ],
            'read_at'    => null,
        ]);

        AppNotification::create([
            'user_id'    => $staff->id,
            'type'       => AppNotification::TYPE_REPORT_APPROVED,
            'title'      => 'Laporan Disetujui',
            'body'       => 'Laporan tugas harianmu telah disetujui oleh ' . $leader->name . '.',
            'related_id' => 2,
            'read_at'    => now()->subHour(),
        ]);

        AppNotification::create([
            'user_id'    => $leader->id,
            'type'       => AppNotification::TYPE_REVISION_SUBMITTED,
            'title'      => 'Revisi Laporan Dikirim',
            'body'       => $staff->name . ' telah mengumpulkan revisi laporan tugas.',
            'related_id' => 2,
            'meta'       => [
                'staff_name' => $staff->name,
            ],
            'read_at'    => null,
        ]);

        AppNotification::create([
            'user_id'    => $leader->id,
            'type'       => AppNotification::TYPE_BACKDATE_REQUESTED,
            'title'      => 'Pengajuan Izin Backdating Baru',
            'body'       => $staff->name . ' mengajukan pengisian laporan mundur. Alasan: Masalah internet.',
            'related_id' => 1,
            'meta'       => [
                'staff_name' => $staff->name,
            ],
            'read_at'    => null,
        ]);

        AppNotification::create([
            'user_id'    => $cLevel->id,
            'type'       => AppNotification::TYPE_REVISION_SUBMITTED,
            'title'      => 'Revisi Target Strategis',
            'body'       => $leader->name . ' mengumpulkan revisi target koordinasi.',
            'related_id' => 5,
            'meta'       => [
                'staff_name' => $leader->name,
            ],
            'read_at'    => null,
        ]);

        AppNotification::create([
            'user_id'    => $leader->id,
            'type'       => AppNotification::TYPE_AUTO_REJECTED,
            'title'      => 'Laporan Otomatis Ditolak',
            'body'       => $staff->name . ' tidak merevisi laporan dalam 10 jam. Laporan otomatis ditolak.',
            'related_id' => 3,
            'meta'       => [
                'staff_name' => $staff->name,
            ],
            'read_at'    => null,
        ]);

        if ($this->staffRejectedEntryId !== null) {
            AppNotification::create([
                'user_id'    => $staff->id,
                'type'       => AppNotification::TYPE_REPORT_REJECTED,
                'title'      => 'Laporan Kerja Ditolak',
                'body'       => 'Laporan harianmu ditolak permanen oleh ' . $leader->name . '. Alasan: Tidak sesuai format SOP.',
                'related_id' => $this->staffRejectedEntryId,
                'meta'       => [
                    'leader_name'    => $leader->name,
                    'rejection_note' => 'Tidak sesuai format SOP.',
                ],
                'read_at'    => null,
            ]);
        }

        AppNotification::create([
            'user_id'    => $staff->id,
            'type'       => AppNotification::TYPE_BACKDATE_REVIEWED,
            'title'      => 'Izin Backdating Disetujui',
            'body'       => 'Pengajuan backdating tanggal ' . now()->subDays(2)->isoFormat('D MMMM YYYY') . ' disetujui.',
            'related_id' => 2,
            'meta'       => [
                'status'         => 'approved',
                'reviewer_name'  => $leader->name,
            ],
            'read_at'    => null,
        ]);

        AppNotification::create([
            'user_id'    => $staff->id,
            'type'       => AppNotification::TYPE_BACKDATE_REVIEWED,
            'title'      => 'Izin Backdating Ditolak',
            'body'       => 'Pengajuan backdating tanggal ' . now()->subDays(4)->isoFormat('D MMMM YYYY') . ' ditolak.',
            'related_id' => 3,
            'meta'       => [
                'status'         => 'rejected',
                'reviewer_name'  => $leader->name,
                'rejection_note' => 'Alasan tidak darurat.',
            ],
            'read_at'    => null,
        ]);

        $this->command->info("  ✅ App Notifications seeded (unread/read dashboard)");
    }
}
