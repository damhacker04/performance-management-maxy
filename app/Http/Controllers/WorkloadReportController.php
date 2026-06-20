<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Models\DailyTaskEntry;
use App\Models\WorkloadReport;
use App\Models\AppNotification;
use App\Jobs\GenerateWorkloadReportJob;
use Illuminate\Support\Facades\Bus;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class WorkloadReportController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    // ─── INDEX — Summary Table ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $user  = auth()->user();
        $this->authorizeAccess($user);

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);
        $dept  = $request->input('department');

        // Tentukan dept scope berdasarkan role
        $query = User::where('is_active', true)
            ->whereNotIn('role', ['c_level', 'super_admin'])
            ->orderBy('department')
            ->orderBy('name');

        if (in_array($user->role, ['c_level', 'super_admin']) || $user->is_management) {
            // C-Level/HR: bisa filter dept atau lihat semua
            if ($dept) {
                $query->where('department', $dept);
            }
        } else {
            // Leader: hanya dept sendiri
            $query->where('department', $user->department);
        }

        $staffList = $query->get();

        // Cari job batch aktif untuk departemen ini (optional, untuk UI feedback)
        $isGenerating = false;

        // Hitung agregat per staf
        $staffData = $staffList->map(function (User $staff) use ($month, $year) {
            return $this->buildStaffSummary($staff, $month, $year);
        });

        $departments = User::DEPARTMENTS;
        $months      = range(1, 12);
        $monthNames  = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return view('workload-report.index', compact(
            'staffData', 'departments', 'months', 'monthNames',
            'month', 'year', 'dept', 'user'
        ));
    }

    // ─── SHOW — Detail per Staf ──────────────────────────────────────────────

    public function show(User $staff, int $month, int $year)
    {
        $user = auth()->user();
        $this->authorizeAccess($user);

        // Leader hanya bisa lihat dept sendiri
        if ($user->role === 'leader' && $staff->department !== $user->department) {
            abort(403, 'Tidak dapat mengakses laporan staf dari departemen lain.');
        }

        $data = $this->buildFullStaffData($staff, $month, $year);

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                       'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $backUrl = route('workload-report.index', ['month' => $month, 'year' => $year]);

        // Cek apakah laporan sudah ada di database
        $savedReport = WorkloadReport::where('staff_id', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $existingReportJson = $savedReport ? json_encode($savedReport->report_data) : null;

        return view('workload-report.show', compact('staff', 'data', 'month', 'year', 'monthNames', 'backUrl', 'existingReportJson'));
    }

    // ─── GENERATE AI ─── AJAX ─────────────────────────────────────────────────

    public function generateReport(Request $request)
    {
        $user = auth()->user();
        $this->authorizeAccess($user);

        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'month'    => 'required|integer|min:1|max:12',
            'year'     => 'required|integer|min:2024|max:2030',
        ]);

        $staff = User::findOrFail($request->staff_id);
        $month = (int) $request->month;
        $year  = (int) $request->year;

        // Leader hanya bisa generate untuk dept sendiri
        if ($user->role === 'leader' && $staff->department !== $user->department) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $data = $this->buildFullStaffData($staff, $month, $year);

        try {
            $result = $this->gemini->generateWorkloadReport($data);
            
            // Simpan ke DB
            WorkloadReport::updateOrCreate(
                ['staff_id' => $staff->id, 'month' => $month, 'year' => $year],
                [
                    'score'        => $result['score'] ?? null,
                    'summary_flag' => $result['summary_flag'] ?? '??',
                    'report_data'  => $result,
                ]
            );

            return response()->json(['success' => true, 'report' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal generate laporan: ' . $e->getMessage()], 500);
        }
    }

    // ─── GENERATE BATCH AI ─── AJAX ────────────────────────────────────────

    public function generateBatch(Request $request)
    {
        $request->validate(['month' => 'required', 'year' => 'required', 'department' => 'required']);
        $user = auth()->user();

        // Ambil list staf
        $query = User::where('is_active', true)
            ->whereNotIn('role', ['c_level', 'super_admin']);
            
        if ($request->department) {
            $query->where('department', $request->department);
        } else {
            // Kalau "Semua" tapi dia leader, lock ke dept dia
            if ($user->role === 'leader') {
                $query->where('department', $user->department);
            }
        }
        $users = $query->get();

        if ($users->isEmpty()) {
            return response()->json(['error' => 'Tidak ada staf untuk di-generate'], 400);
        }

        $jobs = [];
        foreach ($users as $staff) {
            $jobs[] = new GenerateWorkloadReportJob($staff->id, (int)$request->month, (int)$request->year);
        }

        $monthName = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int)$request->month] ?? '';
        $year = $request->year;

        Bus::batch($jobs)->then(function () use ($user, $monthName, $year) {
            AppNotification::create([
                'user_id' => $user->id,
                'type'    => 'system',
                'title'   => 'Laporan AI Selesai',
                'body'    => "Laporan Workload AI untuk periode $monthName $year telah selesai di-generate.",
            ]);
        })->dispatch();

        return response()->json(['success' => true, 'message' => 'Sistem sedang men-generate laporan AI di background. Notifikasi akan dikirim saat selesai.']);
    }

    // ─── Helper: Build Summary (untuk index) ─────────────────────────────────

    private function buildStaffSummary(User $staff, int $month, int $year): array
    {
        // Daily tasks dalam bulan
        $tasks = DailyTaskEntry::where('user_id', $staff->id)
            ->whereYear('task_date', $year)
            ->whereMonth('task_date', $month)
            ->get();

        $taskCount  = $tasks->count();
        $activeDays = $tasks->groupBy(fn($t) => $t->task_date->format('Y-m-d'))->count();
        $completed  = $tasks->where('status', 'selesai')->count();
        $monthlyPct = $taskCount > 0 ? round($completed / $taskCount * 100) : 0;

        // KPI Actual
        $kpiL3     = KpiTarget::forStaff($staff->id)->where('is_active', true)->get();
        $kpiActual = KpiActual::where('staff_id', $staff->id)
            ->where('month', $month)->where('year', $year)->get();

        $kpiPct = null;
        if ($kpiL3->count() > 0 && $kpiActual->count() > 0) {
            $totalTarget = $kpiL3->sum('target_value');
            $totalActual = $kpiActual->sum('actual_value');
            $kpiPct      = $totalTarget > 0 ? round($totalActual / $totalTarget * 100) : null;
        }

        // Cek laporan yang sudah disave di DB
        $savedReport = WorkloadReport::where('staff_id', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $flag  = $savedReport ? $savedReport->summary_flag : '??';
        $score = $savedReport ? $savedReport->score : null;

        return [
            'staff'       => $staff,
            'task_count'  => $taskCount,
            'active_days' => $activeDays,
            'monthly_pct' => $monthlyPct,
            'kpi_pct'     => $kpiPct,
            'flag'        => $flag,
            'score'       => $score,
        ];
    }

    // ─── Helper: Build Full Data (untuk show + AI) ───────────────────────────

    private function buildFullStaffData(User $staff, int $month, int $year): array
    {
        // 1. KPI L2 (dept benchmark)
        $kpiL2 = KpiTarget::level2()
            ->where('department', $staff->department)
            ->where('is_active', true)
            ->get();

        // 2. KPI L3 (staff individual)
        $kpiL3 = KpiTarget::forStaff($staff->id)
            ->where('is_active', true)
            ->with('parent')
            ->get();

        // 3. KPI Actual
        $kpiActuals = KpiActual::where('staff_id', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('kpiTarget')
            ->get();

        // 4. Monthly Targets
        $monthlyTargets = MonthlyTarget::where('assigned_to', $staff->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('weeklyTargets')
            ->get();

        // 5. Weekly Targets
        $weeklyTargets = WeeklyTarget::whereIn('monthly_target_id', $monthlyTargets->pluck('id'))
            ->orderBy('week_number')
            ->get()
            ->groupBy('week_number');

        // 6. Daily Task Entries (lengkap dengan konten)
        $tasks = DailyTaskEntry::where('user_id', $staff->id)
            ->whereYear('task_date', $year)
            ->whereMonth('task_date', $month)
            ->with('aiEvaluation')
            ->orderBy('task_date')
            ->orderBy('created_at')
            ->get();

        // Agregat
        $taskCount    = $tasks->count();
        $activeDaysGrp= $tasks->groupBy(fn($t) => $t->task_date->format('Y-m-d'));
        $activeDays   = $activeDaysGrp->count();
        $dateRange    = $tasks->isNotEmpty()
            ? [$tasks->first()->task_date->format('d M Y'), $tasks->last()->task_date->format('d M Y')]
            : ['-', '-'];
        $avgPerDay    = $activeDays > 0 ? round($taskCount / $activeDays, 1) : 0;

        // Estimasi hari kerja dalam bulan (Mon-Fri)
        $workingDays = $this->countWorkingDays($month, $year);

        return [
            'staff_name'     => $staff->name,
            'staff_id'       => $staff->id,
            'division'       => $staff->department,
            'kpi_l2'         => $kpiL2,
            'kpi_l3'         => $kpiL3,
            'kpi_actuals'    => $kpiActuals,
            'monthly_targets'=> $monthlyTargets,
            'weekly_targets' => $weeklyTargets,
            'tasks'          => $tasks,
            'task_count'     => $taskCount,
            'active_days'    => $activeDays,
            'working_days'   => $workingDays,
            'avg_per_day'    => $avgPerDay,
            'date_range'     => $dateRange,
            'month'          => $month,
            'year'           => $year,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function authorizeAccess(User $user): void
    {
        if (!in_array($user->role, ['c_level', 'super_admin', 'leader']) && !$user->is_management) {
            abort(403, 'Akses ditolak. Fitur ini hanya untuk C-Level, Admin HR, dan Leader.');
        }
    }

    private function countWorkingDays(int $month, int $year): int
    {
        $count = 0;
        $days  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($d = 1; $d <= $days; $d++) {
            $dow = date('N', mktime(0, 0, 0, $month, $d, $year));
            if ($dow < 6) $count++;  // Mon=1 ... Fri=5
        }
        return $count;
    }
}
