<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;

class StaffTargetController extends Controller
{
    /**
     * Daftar target bulanan dept staff — read-only.
     */
    public function index()
    {
        $user = auth()->user();

        $targets = MonthlyTarget::with(['weeklyTargets'])
            ->where('department', $user->department)
            ->whereHas('user', fn($q) => $q->where('role', 'leader'))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // Hitung progres laporan saya per monthly target (PHP-side agar kompatibel SQLite & MySQL)
        $myEntries = DailyTaskEntry::where('user_id', $user->id)
            ->get(['monthly_target_id', 'status'])
            ->groupBy('monthly_target_id');

        $myCounts = $myEntries->map(fn($entries) => [
            'total' => $entries->count(),
            'done'  => $entries->where('status', 'selesai')->count(),
        ]);

        return view('staff-targets.index', compact('targets', 'myCounts'));
    }

    /**
     * Detail satu monthly target: breakdown per weekly target + laporan saya.
     */
    public function show(MonthlyTarget $monthlyTarget)
    {
        $user = auth()->user();

        // Staff hanya boleh lihat target dept-nya sendiri
        abort_if($monthlyTarget->department !== $user->department, 403,
            'Anda tidak memiliki akses untuk melihat target ini.');

        // Pastikan target dibuat oleh leader, bukan C-Level
        abort_if($monthlyTarget->user?->role !== 'leader', 403,
            'Target ini bukan target dari Leader Anda.');

        $monthlyTarget->load(['weeklyTargets' => function($q) use ($user) {
            $q->where(function($query) use ($user) {
                $query->whereNull('assigned_to')
                      ->orWhere('assigned_to', $user->id);
            })->orderBy('week_number');
        }]);

        // Semua daily task saya yang terkait monthly target ini, digroup per weekly_target_id
        $dailyTasksByWeek = DailyTaskEntry::where('user_id', $user->id)
            ->where('monthly_target_id', $monthlyTarget->id)
            ->orderBy('task_date')
            ->orderBy('id')
            ->get()
            ->groupBy('weekly_target_id');

        $totalTasks = $dailyTasksByWeek->flatten()->count();
        $doneTasks  = $dailyTasksByWeek->flatten()->where('status', 'selesai')->count();

        return view('staff-targets.show', compact(
            'monthlyTarget',
            'dailyTasksByWeek',
            'totalTasks',
            'doneTasks'
        ));
    }
}
