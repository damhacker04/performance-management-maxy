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

        // Hanya tampilkan target dari leader (bukan C-Level/super_admin)
        // Dan filter weekly targets agar hanya menampilkan yang relevan untuk user ini
        $targets = MonthlyTarget::with(['weeklyTargets' => function($q) use ($user) {
            // Hanya muat weekly target yang null (untuk semua) ATAU yang di-assign khusus ke user ini
            $q->where(function($query) use ($user) {
                $query->whereNull('assigned_to')
                      ->orWhere('assigned_to', $user->id);
            })->orderBy('week_number');
        }])
            ->where('department', $user->department)
            ->whereHas('user', fn($q) => $q->whereIn('role', ['leader', 'super_admin']))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // Setelah filter, hapus monthly target yang tidak punya weekly target relevan
        // (artinya semua weekly target-nya di-assign ke orang lain, bukan user ini)
        $targets = $targets->filter(fn($t) => $t->weeklyTargets->isNotEmpty());

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

        // Staff hanya boleh lihat target dept-nya sendiri (kecuali c_level / super_admin)
        if (!in_array($user->role, ['c_level', 'super_admin'])) {
            // Cek apakah staff ini di-assign ke salah satu weekly target di dalam monthly target ini
            $hasAssigned = $monthlyTarget->weeklyTargets()->where('assigned_to', $user->id)->exists();

            if (!$hasAssigned) {
                abort_if($monthlyTarget->department !== $user->department, 403,
                    'Anda tidak memiliki akses untuk melihat target ini.');
            }
        }

        // Target boleh dibuat oleh leader ATAU super_admin
        // (super_admin bisa membuat dan assign target langsung ke staff)
        $allowedCreatorRoles = ['leader', 'super_admin'];
        abort_if(!in_array($monthlyTarget->user?->role, $allowedCreatorRoles), 403,
            'Target ini tidak dapat diakses.');

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
