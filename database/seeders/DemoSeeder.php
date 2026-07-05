<?php

namespace Database\Seeders;

use App\Models\DailyTaskEntry;
use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use App\Models\WorkloadReport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh "hidup" untuk DEMO — mencakup seluruh flow yang dipresentasikan:
 * penetapan target (CEO→Leader→Staff) → laporan harian → verifikasi (approved /
 * pending / revisi) → KPI (dept L2 → staff L3 → realisasi).
 *
 * Aman dijalankan berulang (idempoten via updateOrCreate). Semua data di
 * departemen "operational", periode bulan berjalan. Memakai akun dummy
 * @maxy.academy (punya password 'maxy2026') supaya mudah login saat demo.
 *
 * Jalankan:  php artisan db:seed --class=DemoSeeder --force
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $month = (int) now()->month;
        $year  = (int) now()->year;
        $dept  = 'operational';

        // ── 1. Aktor demo (dummy account, password: maxy2026) ───────────────
        $this->actor('superadmin@maxy.academy',                'Admin HR (Demo)',  'super_admin', null,  'Human Capital',    true);
        $ceo = $this->actor('c_level@maxy.academy',            'Ko Isaac (CEO)',   'c_level',     null,  'CEO',              true);
        $lead = $this->actor('leader.operational@maxy.academy', 'Pak Andi (Leader)','leader',      $dept, 'Head of Operational', true);
        $staffA = $this->actor('staff@maxy.academy',            'Budi Santoso',     'staff',       $dept, 'Talent Placement', false);
        $staffB = $this->actor('staff.testing@maxy.academy',    'Sari Dewi',        'staff',       $dept, 'Finance & Legal',  false);

        // ── 2. KPI Dept (L2) + KPI Staff (L3) + Realisasi ───────────────────
        $kpiL2 = KpiTarget::updateOrCreate(
            ['department' => $dept, 'kpi_name' => 'Penyelesaian Tugas Operasional', 'kpi_level' => 2, 'month' => $month, 'year' => $year, 'parent_id' => null],
            ['target_value' => 100, 'unit' => 'tugas', 'set_by' => $ceo->id, 'is_active' => true, 'notes' => 'Benchmark tim operasional bulan ini.'],
        );

        $kpiA = KpiTarget::updateOrCreate(
            ['parent_id' => $kpiL2->id, 'kpi_level' => 3, 'user_id' => $staffA->id, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'kpi_name' => $kpiL2->kpi_name, 'target_value' => 50, 'unit' => 'tugas', 'set_by' => $ceo->id, 'is_active' => true],
        );
        $kpiB = KpiTarget::updateOrCreate(
            ['parent_id' => $kpiL2->id, 'kpi_level' => 3, 'user_id' => $staffB->id, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'kpi_name' => $kpiL2->kpi_name, 'target_value' => 50, 'unit' => 'tugas', 'set_by' => $ceo->id, 'is_active' => true],
        );

        // Realisasi Budi diisi manual (bar KPI terisi). Sari sengaja DIKOSONGKAN
        // supaya bisa dipakai demo tombol "✨ AI" (auto-detect dari laporan).
        KpiActual::updateOrCreate(
            ['kpi_target_id' => $kpiA->id, 'staff_id' => $staffA->id, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'actual_value' => 42, 'source' => 'manual', 'notes' => 'Input manual HR.', 'created_by' => $ceo->id],
        );

        // ── 2b. Contoh KPI jenis lain (average / shared / milestone) ─────────
        // AVERAGE — dept = rata-rata capaian staf (mis. ketepatan waktu %)
        $kpiAvg = KpiTarget::updateOrCreate(
            ['department' => $dept, 'kpi_name' => 'Ketepatan Waktu Penyelesaian', 'kpi_level' => 2, 'month' => $month, 'year' => $year, 'parent_id' => null],
            ['aggregation' => 'average', 'target_value' => 100, 'unit' => '%', 'set_by' => $ceo->id, 'is_active' => true, 'notes' => 'Rata-rata ketepatan waktu tiap staf.'],
        );
        $kpiAvgA = KpiTarget::updateOrCreate(
            ['parent_id' => $kpiAvg->id, 'kpi_level' => 3, 'user_id' => $staffA->id, 'month' => $month, 'year' => $year],
            ['aggregation' => 'average', 'department' => $dept, 'kpi_name' => $kpiAvg->kpi_name, 'target_value' => 100, 'unit' => '%', 'set_by' => $ceo->id, 'is_active' => true],
        );
        $kpiAvgB = KpiTarget::updateOrCreate(
            ['parent_id' => $kpiAvg->id, 'kpi_level' => 3, 'user_id' => $staffB->id, 'month' => $month, 'year' => $year],
            ['aggregation' => 'average', 'department' => $dept, 'kpi_name' => $kpiAvg->kpi_name, 'target_value' => 100, 'unit' => '%', 'set_by' => $ceo->id, 'is_active' => true],
        );
        KpiActual::updateOrCreate(['kpi_target_id' => $kpiAvgA->id, 'staff_id' => $staffA->id, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'actual_value' => 80, 'source' => 'manual', 'created_by' => $ceo->id]);
        KpiActual::updateOrCreate(['kpi_target_id' => $kpiAvgB->id, 'staff_id' => $staffB->id, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'actual_value' => 95, 'source' => 'manual', 'created_by' => $ceo->id]);

        // SHARED — target tim bersama (kepatuhan SOP 95%), actual di level dept
        $kpiShared = KpiTarget::updateOrCreate(
            ['department' => $dept, 'kpi_name' => 'Kepatuhan SOP Operasional', 'kpi_level' => 2, 'month' => $month, 'year' => $year, 'parent_id' => null],
            ['aggregation' => 'shared', 'target_value' => 95, 'unit' => '%', 'set_by' => $ceo->id, 'is_active' => true, 'notes' => 'Target tim, tidak dibagi per staf.'],
        );
        KpiActual::updateOrCreate(['kpi_target_id' => $kpiShared->id, 'staff_id' => null, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'actual_value' => 90, 'source' => 'manual', 'created_by' => $ceo->id]);

        // MILESTONE — progress 0–100% (digitalisasi arsip), actual di level dept
        $kpiMile = KpiTarget::updateOrCreate(
            ['department' => $dept, 'kpi_name' => 'Digitalisasi Arsip 2026', 'kpi_level' => 2, 'month' => $month, 'year' => $year, 'parent_id' => null],
            ['aggregation' => 'milestone', 'target_value' => 100, 'unit' => '%', 'set_by' => $ceo->id, 'is_active' => true, 'notes' => 'Milestone proyek — progress.'],
        );
        KpiActual::updateOrCreate(['kpi_target_id' => $kpiMile->id, 'staff_id' => null, 'month' => $month, 'year' => $year],
            ['department' => $dept, 'actual_value' => 60, 'source' => 'manual', 'created_by' => $ceo->id]);

        // ── 3. Target: CEO → Leader ─────────────────────────────────────────
        $mtCeo = MonthlyTarget::updateOrCreate(
            ['user_id' => $ceo->id, 'assigned_to' => $lead->id, 'title' => 'Efisiensi Operasional Bulan Ini', 'month' => $month, 'year' => $year],
            ['department' => $dept, 'description' => 'Target dari CEO untuk leader operasional.', 'kpi_target_id' => $kpiL2->id],
        );
        $wtL1 = $this->weekly($mtCeo, $lead, 1, 'Audit 3 proses operasional', 'qualitative');
        $wtL2 = $this->weekly($mtCeo, $lead, 2, 'Susun SOP baru', 'qualitative');

        // ── 4. Target: Leader → Staff A (Budi) ──────────────────────────────
        $mtA = MonthlyTarget::updateOrCreate(
            ['user_id' => $lead->id, 'assigned_to' => $staffA->id, 'title' => 'Administrasi & Penempatan Talent', 'month' => $month, 'year' => $year],
            ['department' => $dept, 'description' => 'Target bulanan Budi.'],
        );
        $wtA1 = $this->weekly($mtA, $staffA, 1, 'Proses 20 penempatan talent', 'quantitative', 20, 'penempatan');
        $wtA2 = $this->weekly($mtA, $staffA, 2, 'Rapikan dokumen administrasi', 'qualitative');

        // ── 5. Target: Leader → Staff B (Sari) ──────────────────────────────
        $mtB = MonthlyTarget::updateOrCreate(
            ['user_id' => $lead->id, 'assigned_to' => $staffB->id, 'title' => 'Rekap Keuangan & Legal', 'month' => $month, 'year' => $year],
            ['department' => $dept, 'description' => 'Target bulanan Sari.'],
        );
        $wtB1 = $this->weekly($mtB, $staffB, 1, 'Rekap keuangan mingguan', 'quantitative', 4, 'laporan');

        // ── 6. Laporan harian (semua status verifikasi untuk demo) ──────────
        // Budi (Staff A): 1 disetujui, 1 MENUNGGU (untuk demo verifikasi), 1 revisi
        $this->daily($staffA, $mtA, $wtA1, 'Memproses penempatan talent batch 1', 'selesai', 'approved', $lead,
            'Berhasil menempatkan 8 kandidat ke perusahaan mitra minggu ini.', now()->subDays(3));
        $this->daily($staffA, $mtA, $wtA1, 'Follow up kandidat pending', 'selesai', 'pending', null,
            'Menyelesaikan 5 penempatan tambahan, total minggu ini 13 penempatan.', now()->subDay());
        $this->daily($staffA, $mtA, $wtA2, 'Rekap dokumen administrasi', 'dalam_proses', 'revision', $lead,
            'Sudah 60% dokumen dirapikan, sisanya besok.', now()->subDay(),
            rejection: 'Mohon lampirkan file rekap-nya ya.');

        // Sari (Staff B): 1 disetujui, 1 MENUNGGU (angka jelas → bahan demo AI)
        $this->daily($staffB, $mtB, $wtB1, 'Rekap keuangan minggu 1', 'selesai', 'approved', $lead,
            'Menyelesaikan 3 laporan keuangan mingguan.', now()->subDays(2));
        $this->daily($staffB, $mtB, $wtB1, 'Rekap keuangan & dokumen legal', 'selesai', 'pending', null,
            'Merampungkan 4 rekap keuangan dan 2 dokumen legal minggu ini.', now());

        // Leader: laporan sendiri di target dari CEO (agar Overview/Target ada progres)
        $this->daily($lead, $mtCeo, $wtL1, 'Audit proses operasional', 'selesai', 'approved', $ceo,
            'Audit 3 proses inti operasional selesai.', now()->subDays(2));
        $this->daily($lead, $mtCeo, $wtL2, 'Draft SOP baru', 'dalam_proses', 'pending', null,
            'Draft SOP baru sudah 50% rampung.', now());

        // ── 7. Workload Report (AI) siap-pakai — biar demo tak perlu panggil AI live ──
        WorkloadReport::updateOrCreate(
            ['staff_id' => $staffA->id, 'month' => $month, 'year' => $year],
            ['score' => 85, 'summary_flag' => '✅', 'report_data' => [
                'achievement' => ['Administrasi & Penempatan Talent' => 'Budi menuntaskan 13 penempatan talent dari target 20, dengan dokumentasi rapi dan pelaporan harian yang konsisten.'],
                'optimization_areas' => [['title' => 'Dokumentasi administrasi', 'detail' => 'Rekap dokumen baru 60%; sebaiknya diselesaikan lebih awal agar tidak menumpuk.']],
                'score' => 85,
                'score_reasoning' => 'Pencapaian penempatan tinggi dan konsisten melapor tiap hari; sedikit tertinggal di sisi administrasi.',
                'ceo_recommendations' => ['Pertahankan ritme penempatan talent.', 'Alokasikan waktu khusus untuk merapikan dokumen.'],
                'summary_flag' => '✅',
                'flag_reason' => 'Performa kuat, sedikit PR di administrasi.',
            ]],
        );
        WorkloadReport::updateOrCreate(
            ['staff_id' => $staffB->id, 'month' => $month, 'year' => $year],
            ['score' => 72, 'summary_flag' => '🟡', 'report_data' => [
                'achievement' => ['Rekap Keuangan & Legal' => 'Sari menyelesaikan 4 rekap keuangan dan 2 dokumen legal, sesuai ekspektasi mingguan.'],
                'optimization_areas' => [['title' => 'Konsistensi pelaporan', 'detail' => 'Ada beberapa hari tanpa laporan; keteraturan input harian perlu ditingkatkan.']],
                'score' => 72,
                'score_reasoning' => 'Output sesuai target, namun frekuensi pelaporan harian belum konsisten.',
                'ceo_recommendations' => ['Lapor harian secara rutin.', 'Isi realisasi KPI lebih awal dalam periode.'],
                'summary_flag' => '🟡',
                'flag_reason' => 'Output oke, konsistensi pelaporan perlu naik.',
            ]],
        );

        $this->command?->info('DemoSeeder selesai: 5 aktor, KPI 4 jenis (sum/average/shared/milestone) + realisasi, 3 monthly target, 5 weekly target, 7 laporan harian, 2 workload report AI.');
    }

    /** Buat/segarkan aktor demo dengan password supaya mudah login. */
    private function actor(string $email, string $name, string $role, ?string $dept, string $division, bool $mgmt): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name'          => $name,
                'password'      => Hash::make('maxy2026'),
                'role'          => $role,
                'department'    => $dept,
                'division'      => $division,
                'is_management' => $mgmt,
                'is_active'     => true,
            ],
        );
    }

    /** Buat/segarkan weekly target. */
    private function weekly(MonthlyTarget $mt, User $owner, int $week, string $title, string $type, ?float $value = null, ?string $unit = null): WeeklyTarget
    {
        return WeeklyTarget::updateOrCreate(
            ['monthly_target_id' => $mt->id, 'week_number' => $week, 'title' => $title],
            [
                'user_id'      => $mt->user_id,
                'assigned_to'  => $owner->id,
                'category'     => 'planned',
                'impact_level' => 'medium',
                'target_type'  => $type,
                'target_value' => $value,
                'target_unit'  => $unit,
                'description'  => null,
                'month'        => $mt->month,
                'year'         => $mt->year,
            ],
        );
    }

    /** Buat/segarkan laporan harian dengan status verifikasi tertentu. */
    private function daily(
        User $staff, MonthlyTarget $mt, WeeklyTarget $wt, string $desc,
        string $status, string $verif, ?User $verifier, string $notes, \Carbon\Carbon $date, ?string $rejection = null
    ): void {
        // Kunci TANPA tanggal (tanggal relatif ke now() → agar tetap idempoten
        // walau di-seed di hari berbeda). Kombinasi ini sudah unik per laporan.
        DailyTaskEntry::updateOrCreate(
            ['user_id' => $staff->id, 'weekly_target_id' => $wt->id, 'task_description' => $desc],
            [
                'task_date'           => $date->toDateString(),
                'monthly_target_id'   => $mt->id,
                'priority'            => 'medium',
                'duration_minutes'    => 90,
                'status'              => $status,
                'notes'               => $notes,
                'verification_status' => $verif,
                'verified_by'         => in_array($verif, ['approved']) ? $verifier?->id : null,
                'verified_at'         => $verif === 'approved' ? $date->copy()->addHours(4) : null,
                'reviewed_at'         => $verif === 'revision' ? now()->subHours(3) : null,
                'rejection_note'      => $rejection,
            ],
        );
    }
}
