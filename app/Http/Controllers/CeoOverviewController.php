<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Dashboard monitoring untuk C-Level: ringkasan progress staf lintas departemen.
 * Menggantikan tampilan lama page target untuk C-Level; drill-down memakai
 * route period yang sudah ada (period.staff-list / period.staff-targets).
 */
class CeoOverviewController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        $month = max(1, min(12, $month));
        $year  = max(2024, min(now()->year + 1, $year));

        $staff = User::where('role', 'staff')
            ->where('is_active', true)
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $entries = DailyTaskEntry::whereMonth('task_date', $month)
            ->whereYear('task_date', $year)
            ->whereIn('user_id', $staff->pluck('id'))
            ->get(['id', 'user_id', 'status', 'verification_status']);

        $byUser = $entries->groupBy('user_id');

        // Statistik per staf
        $staffStats = $staff->map(function ($s) use ($byUser) {
            $e     = $byUser->get($s->id, collect());
            $total = $e->count();
            $done  = $e->where('status', 'selesai')->count();

            return [
                'staff'    => $s,
                'total'    => $total,
                'done'     => $done,
                'progress' => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        });

        // Agregasi per departemen
        $byDept = $staffStats
            ->groupBy(fn ($r) => $r['staff']->department ?: 'lainnya')
            ->map(function ($rows, $dept) {
                $total = $rows->sum('total');
                $done  = $rows->sum('done');

                return [
                    'department'  => $dept,
                    'label'       => User::DEPARTMENTS[$dept] ?? ucfirst(str_replace('_', ' ', (string) $dept)),
                    'staff_count' => $rows->count(),
                    'total'       => $total,
                    'done'        => $done,
                    'progress'    => $total > 0 ? (int) round($done / $total * 100) : 0,
                ];
            })
            ->sortByDesc('progress')
            ->values();

        $totalStaff    = $staff->count();
        $sumTotal      = $staffStats->sum('total');
        $sumDone       = $staffStats->sum('done');
        $avgProgress   = $sumTotal > 0 ? (int) round($sumDone / $sumTotal * 100) : 0;
        $pendingReview = $entries->where('verification_status', 'pending')->count();

        // Staf perlu perhatian: sudah ada aktivitas tapi progress < 50%
        $needAttention = $staffStats
            ->filter(fn ($r) => $r['total'] > 0 && $r['progress'] < 50)
            ->sortBy('progress')
            ->take(8)
            ->values();

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $monthLabel = $monthNames[$month].' '.$year;

        return view('ceo.overview', compact(
            'byDept', 'totalStaff', 'avgProgress', 'pendingReview', 'needAttention',
            'month', 'year', 'monthLabel'
        ));
    }
}
