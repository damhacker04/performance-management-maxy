<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeeklyTargetController extends Controller
{
    /**
     * Daftar weekly target di bawah satu monthly target.
     */
    public function index(MonthlyTarget $monthlyTarget)
    {
        $this->authorizeMonthly($monthlyTarget);

        $weeklyTargets = $monthlyTarget->weeklyTargets()->get();

        return view('weekly-targets.index', compact('monthlyTarget', 'weeklyTargets'));
    }

    /**
     * Form buat weekly target baru di bawah monthly target.
     */
    public function create(MonthlyTarget $monthlyTarget)
    {
        $this->authorizeMonthly($monthlyTarget);

        return view('weekly-targets.create', compact('monthlyTarget'));
    }

    public function store(Request $request, MonthlyTarget $monthlyTarget)
    {
        $this->authorizeMonthly($monthlyTarget);

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'week_number'   => 'required|integer|min:1|max:5',
            'target_type'   => ['required', Rule::in(['quantitative', 'qualitative'])],
            'target_value'  => 'nullable|required_if:target_type,quantitative|numeric|min:0',
            'target_unit'   => 'nullable|required_if:target_type,quantitative|string|max:50',
        ], [
            'target_value.required_if'   => 'Nilai target wajib diisi untuk tipe kuantitatif.',
            'target_unit.required_if'    => 'Satuan wajib diisi untuk tipe kuantitatif.',
        ]);

        WeeklyTarget::create([
            'monthly_target_id' => $monthlyTarget->id,
            'user_id'           => auth()->id(),
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
            'target_type'       => $validated['target_type'],
            'target_value'      => $validated['target_type'] === 'quantitative' ? $validated['target_value'] : null,
            'target_unit'       => $validated['target_type'] === 'quantitative' ? $validated['target_unit'] : null,
            'week_number'       => $validated['week_number'],
            'month'             => $monthlyTarget->month,
            'year'              => $monthlyTarget->year,
        ]);

        return redirect()->route('weekly-targets.index', $monthlyTarget)
            ->with('success', 'Target mingguan berhasil disimpan.');
    }

    /**
     * Drill-down: detail weekly target + semua daily task staff yang terkait.
     */
    public function show(WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $weeklyTarget->load('monthlyTarget');

        $dailyTasks = $weeklyTarget->dailyTaskEntries()
            ->with('user')
            ->orderByDesc('task_date')
            ->orderByDesc('created_at')
            ->get();

        // Summary per status
        $summary = [
            'total'        => $dailyTasks->count(),
            'selesai'      => $dailyTasks->where('status', 'selesai')->count(),
            'dalam_proses' => $dailyTasks->where('status', 'dalam_proses')->count(),
            'terhambat'    => $dailyTasks->where('status', 'terhambat')->count(),
            'belum_mulai'  => $dailyTasks->where('status', 'belum_mulai')->count(),
        ];

        // Group by staff supaya leader bisa lihat kontribusi per orang
        $byStaff = $dailyTasks->groupBy('user_id');

        return view('weekly-targets.show', compact('weeklyTarget', 'dailyTasks', 'summary', 'byStaff'));
    }

    public function edit(WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $monthlyTarget = $weeklyTarget->monthlyTarget;

        return view('weekly-targets.edit', compact('weeklyTarget', 'monthlyTarget'));
    }

    public function update(Request $request, WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'week_number'   => 'required|integer|min:1|max:5',
            'target_type'   => ['required', Rule::in(['quantitative', 'qualitative'])],
            'target_value'  => 'nullable|required_if:target_type,quantitative|numeric|min:0',
            'target_unit'   => 'nullable|required_if:target_type,quantitative|string|max:50',
        ], [
            'target_value.required_if'   => 'Nilai target wajib diisi untuk tipe kuantitatif.',
            'target_unit.required_if'    => 'Satuan wajib diisi untuk tipe kuantitatif.',
        ]);

        $weeklyTarget->update([
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'target_type'  => $validated['target_type'],
            'target_value' => $validated['target_type'] === 'quantitative' ? $validated['target_value'] : null,
            'target_unit'  => $validated['target_type'] === 'quantitative' ? $validated['target_unit'] : null,
            'week_number'  => $validated['week_number'],
        ]);

        return redirect()->route('weekly-targets.index', $weeklyTarget->monthlyTarget)
            ->with('success', 'Target mingguan berhasil diperbarui.');
    }

    public function destroy(WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $monthlyTargetId = $weeklyTarget->monthly_target_id;
        $weeklyTarget->delete();

        return redirect()->route('weekly-targets.index', $monthlyTargetId)
            ->with('success', 'Target mingguan berhasil dihapus.');
    }

    /**
     * Pastikan leader hanya bisa akses monthly target miliknya.
     * C-Level boleh akses semuanya.
     */
    private function authorizeMonthly(MonthlyTarget $monthlyTarget): void
    {
        $user = auth()->user();
        if ($user->role === 'c_level') return;
        if ($user->role === 'leader' && $monthlyTarget->user_id === $user->id) return;

        abort(403, 'Anda tidak memiliki akses ke target ini.');
    }

    private function authorizeWeekly(WeeklyTarget $weeklyTarget): void
    {
        $this->authorizeMonthly($weeklyTarget->monthlyTarget);
    }
}
