<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiTargetRequest;
use App\Http\Requests\StoreKpiActualRequest;
use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // KPI L2 (Dept Benchmark) — CRUD yang sudah ada
    // ═══════════════════════════════════════════════════════════

    public function index()
    {
        $user = auth()->user();

        // Query KPI L2 (dept benchmark) — group by dept
        if ($user->isExecutive() || $user->is_management) {
            $kpiByDept = KpiTarget::level2()
                ->whereNotNull('department')
                ->with(['children.staff', 'children.actuals'])
                ->orderBy('department')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->groupBy('department');
        } else {
            // Leader: dept sendiri saja
            $kpiByDept = KpiTarget::level2()
                ->where('department', $user->department)
                ->with(['children.staff', 'children.actuals'])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->groupBy('department');
        }

        // Staf per dept (untuk tombol tambah KPI L3)
        $groupedStaffs = User::where('is_active', true)
            ->whereIn('role', ['staff', 'leader'])
            ->orderBy('department')
            ->orderBy('name')
            ->get()
            ->groupBy('department');

        return view('kpi', compact('kpiByDept', 'groupedStaffs'));
    }

    public function create()
    {
        $departments = User::DEPARTMENTS;

        return view('kpi.create', compact('departments'));
    }

    public function store(KpiTargetRequest $request)
    {
        $user = auth()->user();

        $validated = $request->validated();

        KpiTarget::create([
            ...$validated,
            'kpi_level' => 2,
            'set_by' => $user->id,
            'is_active' => true,
        ]);

        return redirect()->route('kpi')->with('success', 'KPI departemen berhasil ditambahkan.');
    }

    public function edit(KpiTarget $kpiTarget)
    {
        $departments = User::DEPARTMENTS;

        return view('kpi.edit', compact('kpiTarget', 'departments'));
    }

    public function update(KpiTargetRequest $request, KpiTarget $kpiTarget)
    {
        $validated = $request->validated();

        $kpiTarget->update($validated);

        return redirect()->route('kpi')->with('success', 'KPI berhasil diperbarui.');
    }

    public function destroy(KpiTarget $kpiTarget)
    {
        $kpiTarget->update(['is_active' => false]);

        return redirect()->route('kpi')->with('success', 'KPI berhasil dinonaktifkan.');
    }

    // ═══════════════════════════════════════════════════════════
    // KPI L3 (Staff Individual) — BARU
    // ═══════════════════════════════════════════════════════════

    /** Form tambah KPI L3 per staf */
    public function createStaffKpi()
    {
        // KPI L2 yang tersedia sebagai parent
        $kpiDepts = KpiTarget::level2()->where('is_active', true)
            ->orderBy('department')
            ->orderBy('kpi_name')
            ->get();

        // Semua staf aktif (bukan c_level/super_admin)
        $staffs = User::where('is_active', true)
            ->whereNotIn('role', ['c_level', 'super_admin'])
            ->orderBy('department')
            ->orderBy('name')
            ->get()
            ->groupBy('department');

        return view('kpi.create-staff', compact('kpiDepts', 'staffs'));
    }

    /** Simpan KPI L3 per staf */
    public function storeStaffKpi(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'parent_id' => 'required|exists:kpi_targets,id',
            'user_id' => 'required|exists:users,id',
            'target_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $parent = KpiTarget::findOrFail($validated['parent_id']);
        $staff = User::findOrFail($validated['user_id']);

        KpiTarget::create([
            'parent_id' => $parent->id,
            'kpi_level' => 3,
            'user_id' => $staff->id,
            'department' => $parent->department,
            'kpi_name' => $parent->kpi_name,
            'target_value' => $validated['target_value'],
            'unit' => $parent->unit,
            'month' => $parent->month,
            'year' => $parent->year,
            'set_by' => $user->id,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('kpi')->with('success', 'KPI staf berhasil ditambahkan.');
    }

    // ═══════════════════════════════════════════════════════════
    // KPI Actual — BARU (realisasi per bulan, input C-Level/HR)
    // ═══════════════════════════════════════════════════════════

    /** Daftar KPI Actual per bulan */
    public function indexActuals(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $dept = $request->input('department');

        $query = KpiActual::with(['kpiTarget', 'staff', 'creator'])
            ->where('month', $month)
            ->where('year', $year);

        if ($dept) {
            $query->where('department', $dept);
        }

        $actuals = $query->orderBy('department')->orderBy('staff_id')->get();
        $departments = User::DEPARTMENTS;
        $months = range(1, 12);

        return view('kpi.actuals.index', compact('actuals', 'departments', 'months', 'month', 'year', 'dept'));
    }

    /** Form input KPI Actual */
    public function createActual()
    {
        // KPI L3 yang sudah diassign ke staf
        $kpiStaffs = KpiTarget::level3()
            ->where('is_active', true)
            ->with('staff')
            ->orderBy('department')
            ->get();

        $months = range(1, 12);
        $years = range(2024, now()->year + 1);

        return view('kpi.actuals.create', compact('kpiStaffs', 'months', 'years'));
    }

    /** Simpan KPI Actual */
    public function storeActual(StoreKpiActualRequest $request)
    {
        $user = auth()->user();

        $validated = $request->validated();

        $kpiTarget = KpiTarget::findOrFail($validated['kpi_target_id']);

        KpiActual::updateOrCreate(
            [
                'kpi_target_id' => $validated['kpi_target_id'],
                'staff_id' => $validated['staff_id'],
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'department' => $kpiTarget->department,
                'actual_value' => $validated['actual_value'],
                'source' => 'manual',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]
        );

        return redirect()->route('kpi.actuals.index', [
            'month' => $validated['month'],
            'year' => $validated['year'],
        ])->with('success', 'KPI Actual berhasil disimpan.');
    }

    /** Form edit KPI Actual */
    public function editActual(KpiActual $kpiActual)
    {
        $kpiActual->load(['kpiTarget', 'staff']);
        $months = range(1, 12);
        $years = range(2024, now()->year + 1);

        return view('kpi.actuals.edit', compact('kpiActual', 'months', 'years'));
    }

    /** Update KPI Actual */
    public function updateActual(Request $request, KpiActual $kpiActual)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'actual_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $kpiActual->update([
            'actual_value' => $validated['actual_value'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('kpi.actuals.index', [
            'month' => $kpiActual->month,
            'year' => $kpiActual->year,
        ])->with('success', 'KPI Actual berhasil diperbarui.');
    }
}
