<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KpiTarget;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ── Query KPI ───────────────────────────────────────────────────────
        if (in_array($user->role, ['c_level', 'super_admin']) || $user->is_management) {
            // C-Level/Admin HR: semua KPI departemen (baru) + legacy per-staf
            $kpiByDept = KpiTarget::whereNotNull('department')
                ->orderBy('department')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->groupBy('department');
        } else {
            // Leader: KPI departemen sendiri saja
            $kpiByDept = KpiTarget::whereNotNull('department')
                ->where('department', $user->department)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->groupBy('department');
        }

        // Daftar staf per dept (untuk tampilan C-Level)
        $staffs = User::with('kpiTargets')
            ->where('is_active', true)
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $groupedStaffs = $staffs->groupBy('department');

        return view('kpi', compact('kpiByDept', 'groupedStaffs'));
    }

    public function create()
    {
        $user = auth()->user();

        // Hanya C-Level, Super Admin, dan Admin HR (is_management) yang bisa tambah KPI
        if (!in_array($user->role, ['c_level', 'super_admin']) && !$user->is_management) {
            abort(403, 'Tidak memiliki akses untuk menambah KPI.');
        }

        $departments = User::DEPARTMENTS;

        return view('kpi.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['c_level', 'super_admin']) && !$user->is_management) {
            abort(403);
        }

        $validated = $request->validate([
            'department'   => 'required|string|in:' . implode(',', array_keys(User::DEPARTMENTS)),
            'kpi_name'     => 'required|string|max:255',
            'target_value' => 'required|numeric|min:0',
            'unit'         => 'required|string|max:100',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2024|max:2030',
            'notes'        => 'nullable|string|max:1000',
        ]);

        KpiTarget::create([
            ...$validated,
            'set_by'    => $user->id,
            'is_active' => true,
        ]);

        return redirect()->route('kpi')
            ->with('success', 'KPI berhasil ditambahkan.');
    }

    public function edit(KpiTarget $kpiTarget)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['c_level', 'super_admin']) && !$user->is_management) {
            abort(403);
        }

        $departments = User::DEPARTMENTS;

        return view('kpi.edit', compact('kpiTarget', 'departments'));
    }

    public function update(Request $request, KpiTarget $kpiTarget)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['c_level', 'super_admin']) && !$user->is_management) {
            abort(403);
        }

        $validated = $request->validate([
            'department'   => 'required|string|in:' . implode(',', array_keys(User::DEPARTMENTS)),
            'kpi_name'     => 'required|string|max:255',
            'target_value' => 'required|numeric|min:0',
            'unit'         => 'required|string|max:100',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2024|max:2030',
            'notes'        => 'nullable|string|max:1000',
            'is_active'    => 'boolean',
        ]);

        $kpiTarget->update($validated);

        return redirect()->route('kpi')
            ->with('success', 'KPI berhasil diperbarui.');
    }

    public function destroy(KpiTarget $kpiTarget)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['c_level', 'super_admin']) && !$user->is_management) {
            abort(403);
        }

        // Soft-delete: nonaktifkan saja, tidak hapus data
        $kpiTarget->update(['is_active' => false]);

        return redirect()->route('kpi')
            ->with('success', 'KPI berhasil dinonaktifkan.');
    }
}
