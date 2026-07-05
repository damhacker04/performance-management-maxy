<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;

class LeaderTargetController extends Controller
{
    /**
     * Daftar target bulanan untuk LEADER — yang dibuat oleh C-Level untuk dept-nya.
     * Read-only view, mirip StaffTargetController tapi untuk leader.
     */
    public function index()
    {
        $user = auth()->user();

        // Hanya tampilkan monthly target yang DIBUAT oleh C-Level untuk dept leader ini
        $targets = MonthlyTarget::with(['weeklyTargets'])
            ->where('department', $user->department)
            ->whereHas('user', fn($q) => $q->whereIn('role', ['c_level', 'super_admin']))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // Hitung progres laporan saya per monthly target
        $myEntries = DailyTaskEntry::where('user_id', $user->id)
            ->get(['monthly_target_id', 'status', 'user_id'])
            ->groupBy('monthly_target_id');

        $myCounts = $myEntries->map(fn($entries) => [
            'total' => $entries->count(),
            'done'  => $entries->where('status', 'selesai')->count(),
        ]);

        return view('leader-targets.index', compact('targets', 'myCounts'));
    }

    /**
     * Detail satu monthly target: breakdown per weekly + laporan saya sebagai leader.
     */
    public function show(MonthlyTarget $monthlyTarget)
    {
        $user = auth()->user();

        // Hanya boleh lihat target dept-nya sendiri yang dibuat C-Level
        if (!$user->isExecutive()) {
            abort_if(
                $monthlyTarget->department !== $user->department,
                403,
                'Anda tidak memiliki akses untuk melihat target ini.'
            );
        }

        // Pastikan yang buat adalah C-Level atau Super Admin (keduanya setara secara akses)
        abort_if(
            !in_array($monthlyTarget->user?->role, ['c_level', 'super_admin']),
            403,
            'Target ini bukan target dari C-Level.'
        );

        $monthlyTarget->load(['weeklyTargets' => fn($q) => $q->orderBy('week_number')]);

        // Semua daily task saya (leader) yang terkait monthly target ini, digroup per weekly_target_id
        $dailyTasksByWeek = DailyTaskEntry::where('user_id', $user->id)
            ->where('monthly_target_id', $monthlyTarget->id)
            ->orderBy('task_date')
            ->orderBy('id')
            ->get()
            ->groupBy('weekly_target_id');

        $totalTasks = $dailyTasksByWeek->flatten()->count();
        $doneTasks  = $dailyTasksByWeek->flatten()->where('status', 'selesai')->count();

        return view('leader-targets.show', compact(
            'monthlyTarget',
            'dailyTasksByWeek',
            'totalTasks',
            'doneTasks'
        ));
    }
}
