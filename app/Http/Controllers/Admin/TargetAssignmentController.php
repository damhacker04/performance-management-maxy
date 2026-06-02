<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Http\Request;

class TargetAssignmentController extends Controller
{
    /**
     * Halaman assign target — pilih departemen, staff, dan target.
     */
    public function index(Request $request)
    {
        $departments    = User::DEPARTMENTS;
        $selectedDept   = $request->get('department');

        $staffList      = collect();
        $monthlyTargets = collect();

        if ($selectedDept) {
            // Ambil semua staff & leader di departemen yang dipilih
            $staffList = User::where('department', $selectedDept)
                ->whereIn('role', ['staff', 'leader'])
                ->orderBy('name')
                ->get();

            // Ambil semua monthly target di departemen tersebut
            $monthlyTargets = MonthlyTarget::with('weeklyTargets')
                ->where('department', $selectedDept)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get();
        }

        return view('admin.target-assignment.index', compact(
            'departments', 'selectedDept', 'staffList', 'monthlyTargets'
        ));
    }

    /**
     * Assign monthly target ke staff tertentu (assign_to di weekly target).
     * Admin bisa assign weekly target di dalam monthly target ke staff yang dipilih.
     */
    public function assignWeekly(Request $request)
    {
        $validated = $request->validate([
            'weekly_target_id' => 'required|exists:weekly_targets,id',
            'user_id'          => 'required|exists:users,id',
        ]);

        $weeklyTarget = WeeklyTarget::findOrFail($validated['weekly_target_id']);
        $user         = User::findOrFail($validated['user_id']);

        $weeklyTarget->update(['assigned_to' => $validated['user_id']]);

        return back()->with('success', "Weekly Target berhasil di-assign ke {$user->name}.");
    }

    /**
     * Lepas assignment weekly target (kembalikan ke target umum departemen).
     */
    public function unassignWeekly(Request $request)
    {
        $validated = $request->validate([
            'weekly_target_id' => 'required|exists:weekly_targets,id',
        ]);

        $weeklyTarget = WeeklyTarget::findOrFail($validated['weekly_target_id']);
        $weeklyTarget->update(['assigned_to' => null]);

        return back()->with('success', 'Assignment weekly target berhasil dilepas (kembali ke target umum).');
    }

    // ---------------------------------------------------------------
    // Hapus data (diaktifkan sementara per keputusan 2 Juni 2026)
    // ---------------------------------------------------------------

    /**
     * Hapus monthly target beserta semua weekly target dan daily task di dalamnya.
     */
    public function destroyMonthly(MonthlyTarget $monthlyTarget)
    {
        $title = $monthlyTarget->title;
        $monthlyTarget->delete(); // cascade delete via FK

        return redirect()->route('admin.target-assignment.index')
            ->with('success', "Monthly Target \"{$title}\" dan seluruh data terkait berhasil dihapus.");
    }

    /**
     * Hapus weekly target beserta semua daily task di dalamnya.
     */
    public function destroyWeekly(WeeklyTarget $weeklyTarget)
    {
        $title = $weeklyTarget->title;
        $weeklyTarget->delete();

        return back()->with('success', "Weekly Target \"{$title}\" berhasil dihapus.");
    }

    /**
     * Hapus satu daily task entry.
     */
    public function destroyDailyTask(DailyTaskEntry $dailyTask)
    {
        $dailyTask->delete();

        return back()->with('success', 'Laporan harian berhasil dihapus.');
    }
}
