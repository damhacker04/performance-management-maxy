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

        // Konteks: dari mana leader membuka form ini?
        // 'leader' = dari menu Target Saya (dari C-Level)
        // 'team'   = dari menu Target Tim  (yang leader buat untuk staff)
        $context = $request->input('context', null);

        // ── Target DARI C-Level (untuk leader pribadi) ────────────────────
        $cLevelTargetsQuery = MonthlyTarget::query()
            ->whereHas('user', fn($q) => $q->where('role', 'c_level'))
            ->where('month', now()->month)
            ->where('year',  now()->year)
            ->orderBy('title');

        // ── Target BUATAN LEADER (untuk tim/staff) ────────────────────────
        $teamTargetsQuery = MonthlyTarget::query()
            ->orderBy('title');

        if ($user->role === 'leader') {
            $cLevelTargetsQuery->where('department', $user->department);
            $teamTargetsQuery
                ->where('department', $user->department)
                ->where('month', now()->month)
                ->where('year',  now()->year);
        } elseif ($user->role === 'c_level') {
            $teamTargetsQuery
                ->where('month', now()->month)
                ->where('year',  now()->year);
        }

        $cLevelTargets = $cLevelTargetsQuery->get();
        $teamTargets   = $teamTargetsQuery->get();

        $preSelected = $request->filled('monthly_target_id')
            ? (int) $request->monthly_target_id
            : null;

        $preSelectedUser = $request->filled('assigned_to')
            ? (int) $request->assigned_to
            : null;

        // Tentukan departemen target (berguna untuk super_admin / c_level yang tidak punya departemen tetap)
        $targetDepartment = $user->department;
        if ($preSelected) {
            $monthlyTarget = \App\Models\MonthlyTarget::find($preSelected);
            if ($monthlyTarget) {
                $targetDepartment = $monthlyTarget->department;
            }
        }

        $staffList = \App\Models\User::when($targetDepartment, fn($q) => $q->where('department', $targetDepartment))
            ->when($user->role === 'leader', function($q) {
                $q->where('role', 'staff');
            }, function($q) {
                $q->whereIn('role', ['staff', 'leader']);
            })
            ->orderBy('name')
            ->get();

        return view('weekly-targets.create', compact(
            'cLevelTargets', 'teamTargets', 'preSelected', 'preSelectedUser', 'context', 'staffList'
        ));
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
            'assigned_to'       => 'nullable|exists:users,id',
        ], [
            'monthly_target_id.required' => 'Target bulanan wajib dipilih.',
            'target_value.required_if'   => 'Nilai target wajib diisi untuk tipe kuantitatif.',
            'target_unit.required_if'    => 'Satuan wajib diisi untuk tipe kuantitatif.',
            'assigned_to.exists'         => 'Staf yang dipilih tidak valid.',
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
            'assigned_to'       => $validated['assigned_to'] ?? null,
            'month'             => $monthlyTarget->month,
            'year'              => $monthlyTarget->year,
        ]);

        // Redirect: kembali ke ?back= jika ada (period context), else monthly-targets.show
        $backUrl = $request->query('back') ? urldecode($request->query('back')) : null;
        $redirectTo = $backUrl ?? route('monthly-targets.show', $monthlyTarget);

        return redirect($redirectTo)
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

    /**
     * [BARU — Level 5] Laporan weekly target dalam konteks period hierarchy.
     * URL: /monthly-targets/period/{year}/{month}/staff/{staff}/{monthlyTarget}/{weeklyTarget}
     * Back → period.staff-weekly (level 4)
     */
    public function showInPeriod(int $year, int $month, \App\Models\User $staff, \App\Models\MonthlyTarget $monthlyTarget, WeeklyTarget $weeklyTarget)
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

        // Back URL naik 1 level ke period.staff-weekly (level 4)
        $backUrl = route('period.staff-weekly', [
            'year'          => $year,
            'month'         => $month,
            'staff'         => $staff->id,
            'monthlyTarget' => $monthlyTarget->id,
        ]);

        return view('weekly-targets.show', compact(
            'weeklyTarget', 'dailyTasks', 'summary', 'byStaff', 'backUrl',
            'year', 'month', 'staff', 'monthlyTarget'
        ));
    }


    public function edit(WeeklyTarget $weeklyTarget)
    {
        $this->authorizeWeekly($weeklyTarget);

        $user = auth()->user();

        // ── Deteksi konteks dari monthly target yang sedang diedit ────────
        // Jika monthly target dibuat oleh c_level → ini target Saya (leader)
        // Jika dibuat oleh leader → ini target Tim (untuk staff)
        $weeklyTarget->load('monthlyTarget.user');
        $currentMonthly = $weeklyTarget->monthlyTarget;
        $isLeaderContext = $currentMonthly?->user?->role === 'c_level';

        // ── Hanya tampilkan grup yang sesuai konteks ──────────────────────
        if ($isLeaderContext) {
            // Edit target milik leader → hanya tampil target dari C-Level
            $cLevelTargetsQuery = MonthlyTarget::query()
                ->whereHas('user', fn($q) => $q->where('role', 'c_level'))
                ->where('month', $weeklyTarget->month)
                ->where('year',  $weeklyTarget->year)
                ->orderBy('title');

            if ($user->role === 'leader') {
                $cLevelTargetsQuery->where('department', $user->department);
            }

            $cLevelTargets = $cLevelTargetsQuery->get();
            $teamTargets   = collect(); // kosong — tidak ditampilkan
            $context       = 'leader';
        } else {
            // Edit target tim → hanya tampil target buatan leader (untuk staff)
            $teamTargetsQuery = MonthlyTarget::query()
                ->where('month', $weeklyTarget->month)
                ->where('year',  $weeklyTarget->year)
                ->orderBy('title');

            if ($user->role === 'leader') {
                $teamTargetsQuery
                    ->where('department', $user->department);
            }

            $teamTargets   = $teamTargetsQuery->get();
            $cLevelTargets = collect(); // kosong — tidak ditampilkan
            $context       = 'team';
        }

        $targetDepartment = $currentMonthly ? $currentMonthly->department : $user->department;

        $staffList = \App\Models\User::where('department', $targetDepartment)
            ->when($user->role === 'leader', function($q) {
                $q->where('role', 'staff');
            }, function($q) {
                $q->whereIn('role', ['staff', 'leader']);
            })
            ->orderBy('name')
            ->get();

        return view('weekly-targets.edit', compact('weeklyTarget', 'cLevelTargets', 'teamTargets', 'context', 'staffList'));
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
            'assigned_to'       => 'nullable|exists:users,id',
        ], [
            'monthly_target_id.required' => 'Target bulanan wajib dipilih.',
            'target_value.required_if'   => 'Nilai target wajib diisi untuk tipe kuantitatif.',
            'target_unit.required_if'    => 'Satuan wajib diisi untuk tipe kuantitatif.',
            'assigned_to.exists'         => 'Staf yang dipilih tidak valid.',
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
            'assigned_to'       => $validated['assigned_to'] ?? null,
        ]);

        // Redirect: kembali ke ?back= jika ada (period context), else monthly-targets.show
        $backUrl = $request->query('back') ? urldecode($request->query('back')) : null;
        $redirectTo = $backUrl ?? route('monthly-targets.show', $monthlyTarget);

        return redirect($redirectTo)
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
        if (in_array($user->role, ['c_level', 'super_admin'])) return;
        if ($user->role === 'leader' && $monthlyTarget->department === $user->department) return;

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
        if (in_array($user->role, ['c_level', 'super_admin'])) return;

        if ($weeklyTarget->monthly_target_id) {
            $this->authorizeMonthly($weeklyTarget->monthlyTarget);
            return;
        }

        // "Other" weekly target — pemilik = user_id
        if ($weeklyTarget->user_id === $user->id) return;

        abort(403, 'Anda tidak memiliki akses ke target mingguan ini.');
    }
}
