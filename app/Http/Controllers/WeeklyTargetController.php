<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeeklyTargetController extends Controller
{
    /**
     * Redirect ke monthly-targets — weekly target dikelola dari dalam halaman monthly target.
     * Halaman index standalone tidak dipakai agar tidak membingungkan user.
     */
    public function index(Request $request)
    {
        return redirect()->route('monthly-targets.index');
    }

    /**
     * Form buat weekly target baru.
     * Bisa pre-select monthly via query ?monthly_target_id=X.
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        $monthliesQuery = MonthlyTarget::query()
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->orderBy('title');

        if ($user->role === 'leader') {
            $monthliesQuery->where('department', $user->department);
        }

        $monthlyTargets = $monthliesQuery->get();

        $preSelected = $request->filled('monthly_target_id')
            ? (int) $request->monthly_target_id
            : null;

        return view('weekly-targets.create', compact('monthlyTargets', 'preSelected'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'monthly_target_id' => 'required|exists:monthly_targets,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'week_number'       => 'required|integer|min:1|max:5',
            'target_type'       => ['required', Rule::in(['quantitative', 'qualitative'])],
            'target_value'      => 'nullable|required_if:target_type,quantitative|numeric|min:0',
            'target_unit'       => 'nullable|required_if:target_type,quantitative|string|max:50',
        ], [
            'monthly_target_id.required' => 'Target bulanan wajib dipilih.',
            'target_value.required_if'   => 'Nilai target wajib diisi untuk tipe kuantitatif.',
            'target_unit.required_if'    => 'Satuan wajib diisi untuk tipe kuantitatif.',
        ]);

        // Authorize: leader hanya boleh ke monthly miliknya
        $monthlyTarget = MonthlyTarget::findOrFail($validated['monthly_target_id']);
        $this->authorizeMonthly($monthlyTarget);

        WeeklyTarget::create([
            'monthly_target_id' => $monthlyTarget->id,
            'category'          => 'planned',
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

        return redirect()->route('monthly-targets.show', $monthlyTarget)
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

        $summary = [
            'total'        => $dailyTasks->count(),
            'selesai'      => $dailyTasks->where('status', 'selesai')->count(),
            'dalam_proses' => $dailyTasks->where('status', 'dalam_proses')->count(),
            'terhambat'    => $dailyTasks->where('status', 'terhambat')->count(),
            'belum_mulai'  => $dailyTasks->where('status', 'belum_mulai')->count(),
        ];

        $byStaff = $dailyTasks->groupBy('user_id');

        return view('weekly-targets.show', compact('weeklyTarget', 'dailyTasks', 'summary', 'byStaff'));
    }

    public function edit(WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $user = auth()->user();

        // Untuk dropdown monthly target di form edit
        $monthliesQuery = MonthlyTarget::query()
            ->where('month', $weeklyTarget->month)
            ->where('year', $weeklyTarget->year)
            ->orderBy('title');

        if ($user->role === 'leader') {
            $monthliesQuery->where('department', $user->department);
        }

        $monthlyTargets = $monthliesQuery->get();

        return view('weekly-targets.edit', compact('weeklyTarget', 'monthlyTargets'));
    }

    public function update(Request $request, WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $validated = $request->validate([
            'monthly_target_id' => 'required|exists:monthly_targets,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'week_number'       => 'required|integer|min:1|max:5',
            'target_type'       => ['required', Rule::in(['quantitative', 'qualitative'])],
            'target_value'      => 'nullable|required_if:target_type,quantitative|numeric|min:0',
            'target_unit'       => 'nullable|required_if:target_type,quantitative|string|max:50',
        ], [
            'monthly_target_id.required' => 'Target bulanan wajib dipilih.',
            'target_value.required_if'   => 'Nilai target wajib diisi untuk tipe kuantitatif.',
            'target_unit.required_if'    => 'Satuan wajib diisi untuk tipe kuantitatif.',
        ]);

        $monthlyTarget = MonthlyTarget::findOrFail($validated['monthly_target_id']);
        $this->authorizeMonthly($monthlyTarget);

        $weeklyTarget->update([
            'monthly_target_id' => $monthlyTarget->id,
            'category'          => 'planned',
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
            'target_type'       => $validated['target_type'],
            'target_value'      => $validated['target_type'] === 'quantitative' ? $validated['target_value'] : null,
            'target_unit'       => $validated['target_type'] === 'quantitative' ? $validated['target_unit'] : null,
            'week_number'       => $validated['week_number'],
        ]);

        return redirect()->route('monthly-targets.show', $monthlyTarget)
            ->with('success', 'Target mingguan berhasil diperbarui.');
    }

    public function destroy(WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $monthlyTargetId = $weeklyTarget->monthly_target_id;
        $weeklyTarget->delete();

        if ($monthlyTargetId) {
            return redirect()->route('monthly-targets.show', $monthlyTargetId)
                ->with('success', 'Target mingguan berhasil dihapus.');
        }

        return redirect()->route('monthly-targets.index')
            ->with('success', 'Target mingguan berhasil dihapus.');
    }

    /**
     * Leader hanya boleh akses monthly target miliknya.
     * C-Level boleh akses semua.
     */
    private function authorizeMonthly(MonthlyTarget $monthlyTarget): void
    {
        $user = auth()->user();
        if ($user->role === 'c_level') return;
        if ($user->role === 'leader' && $monthlyTarget->user_id === $user->id) return;

        abort(403, 'Anda tidak memiliki akses ke target ini.');
    }

    /**
     * Untuk weekly target:
     * - Jika linked ke monthly: cek via monthly's ownership.
     * - Jika "Other" (monthly_target_id null): cek pembuatnya.
     */
    private function authorizeWeekly(WeeklyTarget $weeklyTarget): void
    {
        $user = auth()->user();
        if ($user->role === 'c_level') return;

        if ($weeklyTarget->monthly_target_id) {
            $this->authorizeMonthly($weeklyTarget->monthlyTarget);
            return;
        }

        // "Other" weekly target — pemilik = user_id
        if ($weeklyTarget->user_id === $user->id) return;

        abort(403, 'Anda tidak memiliki akses ke target mingguan ini.');
    }
}
