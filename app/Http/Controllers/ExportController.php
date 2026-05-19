<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DailyTaskEntry;
use App\Exports\KpiReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Halaman preview export laporan.
     */
    public function index(Request $request)
    {
        $this->authorizeExport();

        // ── Resolve filter values ──────────────────────────────────────────
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        // Pastikan end >= start
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $selectedUserId = $request->integer('user_id', 0); // 0 = semua

        // ── Daftar user yang bisa dipilih ─────────────────────────────────
        $reportableUsers = $this->getReportableUsers();

        // ── Filter user jika dipilih ──────────────────────────────────────
        $filteredUsers = $selectedUserId
            ? $reportableUsers->where('id', $selectedUserId)
            : $reportableUsers;

        // ── Build reports ─────────────────────────────────────────────────
        $reports = [];
        foreach ($filteredUsers as $user) {
            $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                ->where('user_id', $user->id)
                ->whereBetween('task_date', [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ])
                ->orderBy('task_date')
                ->get();

            if ($entries->isNotEmpty()) {
                $reports[] = ['user' => $user, 'entries' => $entries];
            }
        }

        $periodLabel = $startDate->isSameDay($endDate)
            ? $startDate->isoFormat('D MMMM YYYY')
            : $startDate->isoFormat('D MMMM YYYY') . ' – ' . $endDate->isoFormat('D MMMM YYYY');

        return view('export.index', compact(
            'reports', 'reportableUsers',
            'startDate', 'endDate',
            'selectedUserId', 'periodLabel'
        ));
    }

    /**
     * Download sebagai Excel (XLSX).
     */
    public function downloadExcel(Request $request)
    {
        $this->authorizeExport();

        [$startDate, $endDate, $periodLabel, $filename, $users] = $this->resolveFilters($request, 'xlsx');

        $export = new KpiReportExport(
            reportableUsers: $users,
            startDate:       $startDate->toDateString(),
            endDate:         $endDate->toDateString(),
            periodLabel:     $periodLabel,
        );

        return Excel::download($export, $filename);
    }

    /**
     * Buka halaman print-friendly → user pilih "Save as PDF" di dialog print.
     */
    public function printView(Request $request)
    {
        $this->authorizeExport();

        [$startDate, $endDate, $periodLabel, , $users] = $this->resolveFilters($request, 'pdf');

        $reports = [];
        foreach ($users as $user) {
            $entries = DailyTaskEntry::with(['weeklyTarget.monthlyTarget'])
                ->where('user_id', $user->id)
                ->whereBetween('task_date', [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ])
                ->orderBy('task_date')
                ->get();

            if ($entries->isNotEmpty()) {
                $reports[] = ['user' => $user, 'entries' => $entries];
            }
        }

        return view('export.pdf', compact('reports', 'periodLabel'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveFilters(Request $request, string $ext): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $selectedUserId  = $request->integer('user_id', 0);
        $reportableUsers = $this->getReportableUsers();
        $users           = $selectedUserId
            ? $reportableUsers->where('id', $selectedUserId)
            : $reportableUsers;

        $periodLabel = $startDate->isSameDay($endDate)
            ? $startDate->isoFormat('D MMMM YYYY')
            : $startDate->isoFormat('D MMMM YYYY') . ' – ' . $endDate->isoFormat('D MMMM YYYY');

        $safeName = $selectedUserId
            ? 'staff_' . $selectedUserId
            : 'semua_staff';

        $filename = 'laporan_kpi_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd')
                  . '_' . $safeName . '.' . $ext;

        return [$startDate, $endDate, $periodLabel, $filename, $users];
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
