<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Halaman Target khusus C-Level.
 *
 * Alur penugasan: C-Level → Leader → Staff.
 * - C-Level HANYA menetapkan target untuk leader (lihat MonthlyTargetController@create).
 * - Di sini CEO melihat target yang ia tetapkan untuk para leader, beserta progres
 *   leader tersebut, lalu drill-down read-only ke target yang leader berikan ke staff-nya.
 */
class CeoTargetController extends Controller
{
    /**
     * Daftar target yang C-Level tetapkan untuk leader, dikelompokkan per leader.
     */
    public function index(Request $request)
    {
        $filterMonth = (int) $request->get('month', now()->month);
        $filterYear  = (int) $request->get('year', now()->year);
        $filterMonth = max(1, min(12, $filterMonth));
        $filterYear  = max(2024, min(now()->year + 1, $filterYear));

        // Target CEO→Leader: dibuat oleh eksekutif, dimiliki (assigned_to) oleh seorang leader.
        $targets = MonthlyTarget::with(['assignedStaff', 'weeklyTargets'])
            ->whereHas('user', fn ($q) => $q->whereIn('role', ['c_level', 'super_admin']))
            ->whereHas('assignedStaff', fn ($q) => $q->where('role', 'leader'))
            ->where('month', $filterMonth)
            ->where('year', $filterYear)
            ->orderBy('department')
            ->get();

        // Progres tiap target = laporan harian leader yang terkait monthly target ini.
        $entryCounts = DailyTaskEntry::whereIn('monthly_target_id', $targets->pluck('id'))
            ->get(['monthly_target_id', 'status'])
            ->groupBy('monthly_target_id')
            ->map(fn ($rows) => [
                'total' => $rows->count(),
                'done'  => $rows->where('status', 'selesai')->count(),
            ]);

        // Kelompokkan per leader (assigned_to)
        $byLeader = $targets->groupBy('assigned_to')->map(function ($rows) use ($entryCounts) {
            $leader = $rows->first()->assignedStaff;
            $total  = $rows->sum(fn ($t) => $entryCounts[$t->id]['total'] ?? 0);
            $done   = $rows->sum(fn ($t) => $entryCounts[$t->id]['done'] ?? 0);

            return [
                'leader'       => $leader,
                'targets'      => $rows,
                'target_count' => $rows->count(),
                'total'        => $total,
                'done'         => $done,
                'progress'     => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        })->sortBy(fn ($g) => $g['leader']?->name ?? '');

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $monthLabel = $monthNames[$filterMonth] . ' ' . $filterYear;

        return view('ceo.targets.index', compact(
            'byLeader', 'filterMonth', 'filterYear', 'monthLabel'
        ));
    }

    /**
     * Detail satu leader: target yang CEO berikan ke leader + target yang leader
     * berikan ke staff-nya (read-only — CEO tidak bisa menugaskan staff).
     */
    public function showLeader(Request $request, User $leader)
    {
        abort_unless($leader->role === 'leader', 404);

        $filterMonth = (int) $request->get('month', now()->month);
        $filterYear  = (int) $request->get('year', now()->year);
        $filterMonth = max(1, min(12, $filterMonth));
        $filterYear  = max(2024, min(now()->year + 1, $filterYear));

        // 1) Target yang CEO tetapkan untuk leader ini
        $leaderTargets = MonthlyTarget::with(['weeklyTargets', 'user'])
            ->whereHas('user', fn ($q) => $q->whereIn('role', ['c_level', 'super_admin']))
            ->where('assigned_to', $leader->id)
            ->where('month', $filterMonth)
            ->where('year', $filterYear)
            ->get();

        // Progres laporan leader untuk tiap target
        $leaderEntryCounts = DailyTaskEntry::where('user_id', $leader->id)
            ->whereIn('monthly_target_id', $leaderTargets->pluck('id'))
            ->get(['monthly_target_id', 'status'])
            ->groupBy('monthly_target_id')
            ->map(fn ($rows) => [
                'total' => $rows->count(),
                'done'  => $rows->where('status', 'selesai')->count(),
            ]);

        // 2) Target yang leader ini berikan ke staff-nya (dibuat oleh leader, dimiliki staff)
        $staffTargets = MonthlyTarget::with(['assignedStaff', 'weeklyTargets'])
            ->where('user_id', $leader->id)
            ->whereHas('assignedStaff', fn ($q) => $q->where('role', 'staff'))
            ->where('month', $filterMonth)
            ->where('year', $filterYear)
            ->get();

        $staffEntryCounts = DailyTaskEntry::whereIn('monthly_target_id', $staffTargets->pluck('id'))
            ->get(['monthly_target_id', 'status'])
            ->groupBy('monthly_target_id')
            ->map(fn ($rows) => [
                'total' => $rows->count(),
                'done'  => $rows->where('status', 'selesai')->count(),
            ]);

        $byStaff = $staffTargets->groupBy('assigned_to')->map(function ($rows) use ($staffEntryCounts) {
            $staff = $rows->first()->assignedStaff;
            $total = $rows->sum(fn ($t) => $staffEntryCounts[$t->id]['total'] ?? 0);
            $done  = $rows->sum(fn ($t) => $staffEntryCounts[$t->id]['done'] ?? 0);

            return [
                'staff'        => $staff,
                'targets'      => $rows,
                'target_count' => $rows->count(),
                'progress'     => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        })->sortBy(fn ($g) => $g['staff']?->name ?? '');

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $monthLabel = $monthNames[$filterMonth] . ' ' . $filterYear;

        return view('ceo.targets.leader', compact(
            'leader', 'leaderTargets', 'leaderEntryCounts', 'byStaff',
            'filterMonth', 'filterYear', 'monthLabel'
        ));
    }
}
