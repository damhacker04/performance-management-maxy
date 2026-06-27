<?php

namespace App\Services;

use App\Models\DailyTaskEntry;
use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use App\Models\WorkloadReport;
use Carbon\Carbon;

/**
 * Membangun agregat data Workload & Performance Report untuk seorang staf.
 *
 * Diekstrak dari WorkloadReportController agar bisa dipakai bersama oleh
 * controller (request HTTP) dan GenerateWorkloadReportJob (batch) tanpa trik
 * Reflection ke method privat controller.
 */
class WorkloadReportDataService
{
    /** Ringkasan ringkas per staf (untuk tabel index). */
    public function buildStaffSummary(User $staff, int $month, int $year): array
    {
        $tasks = DailyTaskEntry::where('user_id', $staff->id)
            ->whereYear('task_date', $year)
            ->whereMonth('task_date', $month)
            ->get();

        $taskCount = $tasks->count();
        $activeDays = $tasks->groupBy(fn ($t) => $t->task_date->format('Y-m-d'))->count();
        $completed = $tasks->where('status', 'selesai')->count();
        $monthlyPct = $taskCount > 0 ? round($completed / $taskCount * 100) : 0;

        $kpiL3 = KpiTarget::forStaff($staff->id)->where('is_active', true)->get();
        $kpiActual = KpiActual::where('staff_id', $staff->id)
            ->where('month', $month)->where('year', $year)->get();

        $kpiPct = null;
        if ($kpiL3->count() > 0 && $kpiActual->count() > 0) {
            $totalTarget = $kpiL3->sum('target_value');
            $totalActual = $kpiActual->sum('actual_value');
            $kpiPct = $totalTarget > 0 ? round($totalActual / $totalTarget * 100) : null;
        }

        $savedReport = WorkloadReport::where('staff_id', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return [
            'staff' => $staff,
            'task_count' => $taskCount,
            'active_days' => $activeDays,
            'monthly_pct' => $monthlyPct,
            'kpi_pct' => $kpiPct,
            'flag' => $savedReport ? $savedReport->summary_flag : '??',
            'score' => $savedReport ? $savedReport->score : null,
        ];
    }

    /** Data lengkap per staf (untuk halaman detail + prompt AI). */
    public function buildFullStaffData(User $staff, int $month, int $year): array
    {
        $kpiL2 = KpiTarget::level2()
            ->where('department', $staff->department)
            ->where('is_active', true)
            ->get();

        $kpiL3 = KpiTarget::forStaff($staff->id)
            ->where('is_active', true)
            ->with('parent')
            ->get();

        $kpiActuals = KpiActual::where('staff_id', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('kpiTarget')
            ->get();

        $monthlyTargets = MonthlyTarget::where('assigned_to', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('weeklyTargets')
            ->get();

        $weeklyTargets = WeeklyTarget::whereIn('monthly_target_id', $monthlyTargets->pluck('id'))
            ->orderBy('week_number')
            ->get()
            ->groupBy('week_number');

        $tasks = DailyTaskEntry::where('user_id', $staff->id)
            ->whereYear('task_date', $year)
            ->whereMonth('task_date', $month)
            ->with('aiEvaluation')
            ->orderBy('task_date')
            ->orderBy('created_at')
            ->get();

        $taskCount = $tasks->count();
        $activeDays = $tasks->groupBy(fn ($t) => $t->task_date->format('Y-m-d'))->count();
        $dateRange = $tasks->isNotEmpty()
            ? [$tasks->first()->task_date->format('d M Y'), $tasks->last()->task_date->format('d M Y')]
            : ['-', '-'];
        $avgPerDay = $activeDays > 0 ? round($taskCount / $activeDays, 1) : 0;
        $workingDays = $this->countWorkingDays($month, $year);

        return [
            'staff_name' => $staff->name,
            'staff_id' => $staff->id,
            'division' => $staff->department,
            'kpi_l2' => $kpiL2,
            'kpi_l3' => $kpiL3,
            'kpi_actuals' => $kpiActuals,
            'monthly_targets' => $monthlyTargets,
            'weekly_targets' => $weeklyTargets,
            'tasks' => $tasks,
            'task_count' => $taskCount,
            'active_days' => $activeDays,
            'working_days' => $workingDays,
            'avg_per_day' => $avgPerDay,
            'date_range' => $dateRange,
            'month' => $month,
            'year' => $year,
        ];
    }

    /** Jumlah hari kerja (Senin–Jumat) dalam bulan tertentu. */
    public function countWorkingDays(int $month, int $year): int
    {
        $count = 0;
        $days = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        for ($d = 1; $d <= $days; $d++) {
            if (Carbon::createFromDate($year, $month, $d)->isWeekday()) {
                $count++;
            }
        }

        return $count;
    }
}
