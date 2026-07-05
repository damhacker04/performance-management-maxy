<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiTargetRequest;
use App\Http\Requests\StoreKpiActualRequest;
use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use App\Services\KpiAiAnalyzerService;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // KPI L2 (Dept Benchmark) — CRUD yang sudah ada
    // ═══════════════════════════════════════════════════════════

    public function index()
    {
        return view('kpi', $this->buildIndexData());
    }

    /**
     * Bangun data index KPI (dipakai ulang oleh Admin\AdminKpiController).
     */
    protected function buildIndexData(): array
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

        return compact('kpiByDept', 'groupedStaffs');
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
        $validated['aggregation'] = $validated['aggregation'] ?? 'sum';

        // Milestone: tak pakai angka target — pakai konvensi 100 / '%' (actual = progress 0–100).
        if ($validated['aggregation'] === 'milestone') {
            $validated['target_value'] = 100;
            $validated['unit'] = '%';
        }

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

        // Jenis KPI dikunci setelah dibuat (cegah data L3/actual jadi tak konsisten).
        unset($validated['aggregation']);

        // Pertahankan konvensi milestone (100 / '%').
        if ($kpiTarget->isMilestone()) {
            $validated['target_value'] = 100;
            $validated['unit'] = '%';
        }

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
        // KPI L2 yang tersedia sebagai parent — HANYA jenis yang punya pecahan staf
        // (sum & average). Shared & milestone diukur di level dept, tanpa KPI staf.
        $kpiDepts = KpiTarget::level2()->where('is_active', true)
            ->whereIn('aggregation', ['sum', 'average'])
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
            'aggregation' => $parent->aggregation,  // L3 mewarisi jenis dari parent
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
        // KPI L3 yang sudah diassign ke staf (sum/average — per staf)
        $kpiStaffs = KpiTarget::level3()
            ->where('is_active', true)
            ->with('staff')
            ->orderBy('department')
            ->get();

        // KPI level departemen (shared/milestone) — realisasi diinput di level dept.
        $kpiDeptLevel = KpiTarget::level2()
            ->where('is_active', true)
            ->whereIn('aggregation', ['shared', 'milestone'])
            ->orderBy('department')
            ->get();

        $months = range(1, 12);
        $years = range(2024, now()->year + 1);

        return view('kpi.actuals.create', compact('kpiStaffs', 'kpiDeptLevel', 'months', 'years'));
    }

    /** Simpan KPI Actual */
    public function storeActual(StoreKpiActualRequest $request)
    {
        $user = auth()->user();

        $validated = $request->validated();

        $kpiTarget = KpiTarget::findOrFail($validated['kpi_target_id']);

        // KPI level dept (shared/milestone) tak punya staf → staff_id null.
        $staffId = $kpiTarget->isDeptLevel() ? null : ($validated['staff_id'] ?? null);

        // Milestone: nilai = progress, dijaga di rentang 0–100.
        $value = (float) $validated['actual_value'];
        if ($kpiTarget->isMilestone()) {
            $value = min(100, max(0, $value));
        }

        KpiActual::updateOrCreate(
            [
                'kpi_target_id' => $validated['kpi_target_id'],
                'staff_id' => $staffId,
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'department' => $kpiTarget->department,
                'actual_value' => $value,
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

        // Milestone: progress dijaga di rentang 0–100.
        $value = (float) $validated['actual_value'];
        if ($kpiActual->kpiTarget?->isMilestone()) {
            $value = min(100, max(0, $value));
        }

        $kpiActual->update([
            'actual_value' => $value,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('kpi.actuals.index', [
            'month' => $kpiActual->month,
            'year' => $kpiActual->year,
        ])->with('success', 'KPI Actual berhasil diperbarui.');
    }

    // ═══════════════════════════════════════════════════════════
    // AI Auto-Detect KPI Realisasi
    // ═══════════════════════════════════════════════════════════

    /**
     * Analisis laporan harian staf menggunakan AI untuk mengisi realisasi KPI.
     * Dipanggil via AJAX dari halaman KPI.
     */
    public function analyzeWithAi(Request $request, KpiAiAnalyzerService $analyzer)
    {
        $validated = $request->validate([
            'kpi_target_id' => 'required|exists:kpi_targets,id',
            'month'         => 'required|integer|min:1|max:12',
            'year'          => 'required|integer|min:2024',
        ]);

        $kpi   = KpiTarget::with('staff')->findOrFail($validated['kpi_target_id']);
        $month = (int) $validated['month'];
        $year  = (int) $validated['year'];

        // Tentukan mode: per-staf (L3 sum/average) atau level-dept (L2 shared/milestone).
        if ($kpi->kpi_level === 3 && $kpi->hasStaffBreakdown()) {
            $mode    = 'staff';
            $staffId = $kpi->user_id;
            $result  = $analyzer->analyzeForStaff($kpi, $month, $year);
        } elseif ($kpi->kpi_level === 2 && $kpi->isDeptLevel()) {
            $mode    = 'dept';
            $staffId = null;
            $result  = $analyzer->analyzeForDept($kpi, $month, $year);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'KPI ini tidak bisa dianalisis AI.',
            ], 422);
        }

        // Milestone: nilai adalah progress 0–100.
        $value = (float) $result['actual_value'];
        if ($kpi->isMilestone()) {
            $value = min(100, max(0, $value));
        }

        $actual = KpiActual::updateOrCreate(
            [
                'kpi_target_id' => $kpi->id,
                'staff_id'      => $staffId,
                'month'         => $month,
                'year'          => $year,
            ],
            [
                'department'    => $kpi->department,
                'actual_value'  => $value,
                'source'        => 'auto_detected',
                'notes'         => $result['reasoning'],
                'created_by'    => auth()->id(),
            ]
        );

        // Milestone: actual sudah %. Lainnya: actual/target*100.
        $pct = $kpi->isMilestone()
            ? round(min(100, max(0, $value)), 1)
            : ($kpi->target_value > 0 ? round($value / $kpi->target_value * 100, 1) : 0);

        return response()->json([
            'success'          => true,
            'mode'             => $mode,
            'kpi_id'           => $kpi->id,
            'actual_value'     => $value,
            'target_value'     => (float) $kpi->target_value,
            'unit'             => $kpi->unit,
            'percentage'       => $pct,
            'reasoning'        => $result['reasoning'],
            'reports_analyzed' => $result['reports_analyzed'],
            'kpi_actual_id'    => $actual->id,
        ]);
    }
}
