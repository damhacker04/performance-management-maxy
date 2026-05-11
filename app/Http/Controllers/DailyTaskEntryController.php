<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyTaskEntryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $entries = DailyTaskEntry::with(['monthlyTarget', 'weeklyTarget'])
            ->where('user_id', $user->id)
            ->orderByDesc('task_date')
            ->orderByDesc('id')
            ->get();

        return view('daily-tasks.index', compact('entries'));
    }

    public function create()
    {
        $user = auth()->user();

        // Ambil weekly targets bulan ini untuk department user
        // Weekly target → monthly target → department
        $weeklyTargets = WeeklyTarget::with('monthlyTarget')
            ->whereHas('monthlyTarget', fn($q) =>
                $q->where('department', $user->department)
                  ->where('month', now()->month)
                  ->where('year', now()->year)
            )
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->orderBy('week_number')
            ->get();

        return view('daily-tasks.create', compact('weeklyTargets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'weekly_target_id'  => 'required|exists:weekly_targets,id',
            'task_description'  => 'required|string',
            'priority'          => ['required', Rule::in(array_keys(DailyTaskEntry::PRIORITIES))],
            'duration_value'    => 'required|integer|min:1|max:1440',
            'duration_unit'     => ['required', Rule::in(['menit', 'jam'])],
            'status'            => ['required', Rule::in(array_keys(DailyTaskEntry::STATUSES))],
            'percent_done'      => 'required|integer|min:0|max:100',
            'notes'             => 'nullable|string|required_if:status,terhambat',
        ], [
            'notes.required_if' => 'Catatan wajib diisi jika status Terhambat.',
        ]);

        // Konversi durasi -> menit
        $durationMinutes = $validated['duration_unit'] === 'jam'
            ? $validated['duration_value'] * 60
            : $validated['duration_value'];

        if ($durationMinutes > 1440) {
            return back()
                ->withInput()
                ->withErrors(['duration_value' => 'Durasi maksimal 24 jam (1440 menit).']);
        }

        // Derive monthly_target_id dari weekly target's parent
        $weeklyTarget = WeeklyTarget::findOrFail($validated['weekly_target_id']);

        DailyTaskEntry::create([
            'user_id'           => auth()->id(),
            'monthly_target_id' => $weeklyTarget->monthly_target_id,
            'weekly_target_id'  => $weeklyTarget->id,
            'task_description'  => $validated['task_description'],
            'priority'          => $validated['priority'],
            'duration_minutes'  => $durationMinutes,
            'status'            => $validated['status'],
            'percent_done'      => $validated['percent_done'],
            'notes'             => $validated['notes'] ?? null,
            'task_date'         => now()->toDateString(), // selalu hari ini
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Laporan tugas berhasil disimpan.');
    }

    /**
     * Tandai entry sebagai selesai (dipanggil dari checkbox di dashboard).
     */
    public function complete(DailyTaskEntry $dailyTask)
    {
        // Pastikan hanya pemilik entry yang bisa mengubah
        if ($dailyTask->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        $dailyTask->update([
            'status'       => 'selesai',
            'percent_done' => 100,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Tugas ditandai selesai.');
    }
}
