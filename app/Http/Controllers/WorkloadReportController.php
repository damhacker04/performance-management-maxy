<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWorkloadReportJob;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\WorkloadReport;
use App\Services\GeminiService;
use App\Services\WorkloadReportDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class WorkloadReportController extends Controller
{
    public function __construct(
        protected GeminiService $gemini,
        protected WorkloadReportDataService $dataService,
    ) {}

    // ─── INDEX — Summary Table ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = auth()->user();
        $this->authorizeAccess($user);

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $dept = $request->input('department');

        // Tentukan dept scope berdasarkan role
        $query = User::where('is_active', true)
            ->whereNotIn('role', ['c_level', 'super_admin'])
            ->orderBy('department')
            ->orderBy('name');

        if ($user->isExecutive() || $user->is_management) {
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
            return $this->dataService->buildStaffSummary($staff, $month, $year);
        });

        $departments = User::DEPARTMENTS;
        $months = range(1, 12);
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
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

        // Batasi parameter periode dari URL agar tidak liar.
        if ($month < 1 || $month > 12 || $year < 2024 || $year > 2030) {
            abort(404);
        }

        // Leader hanya bisa lihat dept sendiri
        if ($user->role === 'leader' && $staff->department !== $user->department) {
            abort(403, 'Tidak dapat mengakses laporan staf dari departemen lain.');
        }

        $data = $this->dataService->buildFullStaffData($staff, $month, $year);

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
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
        ]);

        $staff = User::findOrFail($request->staff_id);
        $month = (int) $request->month;
        $year = (int) $request->year;

        // Leader hanya bisa generate untuk dept sendiri
        if ($user->role === 'leader' && $staff->department !== $user->department) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $data = $this->dataService->buildFullStaffData($staff, $month, $year);

        try {
            $result = $this->gemini->generateWorkloadReport($data);

            // Simpan ke DB
            WorkloadReport::updateOrCreate(
                ['staff_id' => $staff->id, 'month' => $month, 'year' => $year],
                [
                    'score' => $result['score'] ?? null,
                    'summary_flag' => $result['summary_flag'] ?? '??',
                    'report_data' => $result,
                ]
            );

            return response()->json(['success' => true, 'report' => $result]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Gagal generate laporan. Silakan coba beberapa saat lagi.'], 500);
        }
    }

    // ─── GENERATE BATCH AI ─── AJAX ────────────────────────────────────────

    public function generateBatch(Request $request)
    {
        $user = auth()->user();
        $this->authorizeAccess($user);

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
            'department' => 'required|string',
        ]);

        // Ambil list staf
        $query = User::where('is_active', true)
            ->whereNotIn('role', ['c_level', 'super_admin']);

        // Leader SELALU dikunci ke departemennya sendiri, apa pun input yang
        // dikirim (mencegah generate lintas departemen). Executive/management
        // boleh memilih departemen mana pun.
        if ($user->role === 'leader') {
            $query->where('department', $user->department);
        } elseif ($request->department) {
            $query->where('department', $request->department);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return response()->json(['error' => 'Tidak ada staf untuk di-generate'], 400);
        }

        $jobs = [];
        foreach ($users as $staff) {
            $jobs[] = new GenerateWorkloadReportJob($staff->id, (int) $request->month, (int) $request->year);
        }

        $monthName = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int) $request->month] ?? '';
        $year = $request->year;

        Bus::batch($jobs)->then(function () use ($user, $monthName, $year) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => 'Laporan AI Selesai',
                'body' => "Laporan Workload AI untuk periode $monthName $year telah selesai di-generate.",
            ]);
        })->dispatch();

        return response()->json(['success' => true, 'message' => 'Sistem sedang men-generate laporan AI di background. Notifikasi akan dikirim saat selesai.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function authorizeAccess(User $user): void
    {
        if (! $user->isLeadership() && ! $user->is_management) {
            abort(403, 'Akses ditolak. Fitur ini hanya untuk C-Level, Admin HR, dan Leader.');
        }
    }
}
