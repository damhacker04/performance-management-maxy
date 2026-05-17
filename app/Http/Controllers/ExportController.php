<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DailyTaskEntry;
use App\Exports\KpiReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    /**
     * Halaman preview export laporan.
     */
    public function index(Request $request)
    {
        $this->authorizeExport();

        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $reportableUsers = $this->getReportableUsers();
        $period          = Carbon::createFromDate($year, $month, 1);
        $startDate       = $period->copy()->startOfMonth();
        $endDate         = $period->copy()->endOfMonth();

        $reports = [];
        foreach ($reportableUsers as $user) {
            $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                ->where('user_id', $user->id)
                ->whereBetween('task_date', [$startDate, $endDate])
                ->orderBy('task_date')
                ->get();

            if ($entries->isNotEmpty()) {
                $reports[] = ['user' => $user, 'entries' => $entries];
            }
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $months = collect(range(1, 12))->map(fn($m) => [
            'value' => $m,
            'label' => $monthNames[$m],
        ]);

        try {
            $minRaw    = DailyTaskEntry::min('task_date');
            $firstYear = ($minRaw && strlen((string)$minRaw) >= 4)
                       ? (int) Carbon::parse((string)$minRaw)->format('Y')
                       : now()->year;
        } catch (\Throwable) {
            $firstYear = now()->year;
        }
        $years = range($firstYear, now()->year);

        return view('export.index', compact(
            'reports', 'months', 'years', 'month', 'year', 'period'
        ));
    }

    /**
     * Download sebagai Excel (XLSX) — bisa dianalisis, filter, sort.
     */
    public function downloadExcel(Request $request)
    {
        $this->authorizeExport();

        [$startDate, $endDate, $periodLabel, $filename] = $this->getPeriodInfo($request, 'xlsx');

        $export = new KpiReportExport(
            reportableUsers: $this->getReportableUsers(),
            startDate:       $startDate,
            endDate:         $endDate,
            periodLabel:     $periodLabel,
        );

        return Excel::download($export, $filename);
    }

    /**
     * Download sebagai PDF — untuk print atau share.
     */
    public function downloadPdf(Request $request)
    {
        $this->authorizeExport();

        [$startDate, $endDate, $periodLabel, $filename] = $this->getPeriodInfo($request, 'pdf');

        $reportableUsers = $this->getReportableUsers();
        $reports = [];
        foreach ($reportableUsers as $user) {
            $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                ->where('user_id', $user->id)
                ->whereBetween('task_date', [$startDate, $endDate])
                ->orderBy('task_date')
                ->get();

            if ($entries->isNotEmpty()) {
                $reports[] = ['user' => $user, 'entries' => $entries];
            }
        }

        $pdf = Pdf::loadView('export.pdf', compact('reports', 'periodLabel'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getPeriodInfo(Request $request, string $ext): array
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $month       = $request->integer('month', now()->month);
        $year        = $request->integer('year',  now()->year);
        $period      = Carbon::createFromDate($year, $month, 1);
        $startDate   = $period->copy()->startOfMonth();
        $endDate     = $period->copy()->endOfMonth();
        $periodLabel = ($monthNames[$month] ?? 'Bulan') . ' ' . $year;
        $filename    = 'laporan_kpi_' . $period->format('Y_m') . '.' . $ext;

        return [$startDate, $endDate, $periodLabel, $filename];
    }

    private function getReportableUsers()
    {
        $authUser = auth()->user();
        $query    = User::whereIn('role', ['staff', 'leader'])
                        ->orderBy('department')
                        ->orderBy('name');

        if ($authUser->role === 'leader') {
            $query->where('department', $authUser->department)
                  ->where('id', '!=', $authUser->id);
        }

        return $query->get();
    }

    private function authorizeExport(): void
    {
        $user = auth()->user();
        if (!$user || !$user->canExport()) {
            abort(403, 'Anda tidak memiliki akses ke fitur Export.');
        }
    }
}
