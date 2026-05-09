<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use Illuminate\Http\Request;

class DailyTaskEntryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $entries = DailyTaskEntry::with('monthlyTarget')
            ->where('user_id', $user->id)
            ->orderByDesc('task_date')
            ->get();

        return view('daily-tasks.index', compact('entries'));
    }

    public function create()
    {
        $user = auth()->user();

        $targets = MonthlyTarget::where('department', $user->department)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('daily-tasks.create', compact('targets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'monthly_target_id' => 'required|exists:monthly_targets,id',
            'task_description'  => 'required|string',
            'duration_minutes'  => 'required|integer|min:1|max:1440',
            'status'            => 'required|in:selesai,dalam_proses,terhambat',
            'notes'             => 'nullable|string',
            'task_date'         => 'required|date|before_or_equal:today',
        ]);

        DailyTaskEntry::create([
            'user_id'           => auth()->id(),
            'monthly_target_id' => $validated['monthly_target_id'],
            'task_description'  => $validated['task_description'],
            'duration_minutes'  => $validated['duration_minutes'],
            'status'            => $validated['status'],
            'notes'             => $validated['notes'] ?? null,
            'task_date'         => $validated['task_date'],
        ]);

        return redirect()->route('daily-tasks.index')
            ->with('success', 'Laporan tugas berhasil disimpan.');
    }
}
