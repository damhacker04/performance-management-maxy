<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Http\Request;

class MonthlyTargetController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Filter periode — wajib, default ke bulan & tahun berjalan
        $filterMonth = (int) $request->get('month', now()->month);
        $filterYear  = (int) $request->get('year',  now()->year);

        // Validasi: bulan 1–12, tahun 2024–sekarang+1
        $filterMonth = max(1, min(12, $filterMonth));
        $filterYear  = max(2024, min(now()->year + 1, $filterYear));

        $targets = MonthlyTarget::with(['user', 'weeklyTargets', 'weeklyTargets.dailyTaskEntries'])
            ->where('month', $filterMonth)
            ->where('year',  $filterYear)
            ->when(
                $user->role === 'leader' || $user->role === 'staff',
                fn($q) => $q->where('department', $user->department)
            )
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15)
            ->withQueryString();

        // Leader: group by bulan (untuk tampilan accordion bulan, sudah difilter periode)
        $leaderGrouped = null;
        if ($user->role === 'leader') {
            $leaderGrouped = $targets->getCollection()
                ->groupBy(fn($t) => $t->year . '-' . str_pad($t->month, 2, '0', STR_PAD_LEFT))
                ->sortKeysDesc();
        }

        return view('monthly-targets.index', compact(
            'targets', 'leaderGrouped', 'filterMonth', 'filterYear'
        ));
    }


    /**
     * Halaman perantara: daftar staf yang punya target di bulan tertentu.
     * Dipanggil dari index ketika leader klik sebuah bulan.
     */
    public function staffListForMonth(int $year, int $month)
    {
        $user = auth()->user();

        // Ambil semua monthly target di bulan ini, untuk dept leader
        $monthlyTargets = MonthlyTarget::with([
            'assignedStaff',
            'weeklyTargets.dailyTaskEntries',
        ])
        ->where('month', $month)
        ->where('year', $year)
        ->when(!in_array($user->role, ['c_level', 'super_admin']),
            fn($q) => $q->where('department', $user->department)
        )
        ->whereNotNull('assigned_to')
        ->get();

        // Group by assigned staff
        $byStaff = $monthlyTargets->groupBy('assigned_to')->map(function ($staffTargets) {
            $staff       = $staffTargets->first()->assignedStaff;
            $totalEntries = $staffTargets->sum(fn($mt) => $mt->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count()));
            $doneEntries  = $staffTargets->sum(fn($mt) => $mt->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count()));
            return [
                'staff'        => $staff,
                'targets'      => $staffTargets,
                'targetCount'  => $staffTargets->count(),
                'totalEntries' => $totalEntries,
                'doneEntries'  => $doneEntries,
                'progress'     => $totalEntries > 0 ? round($doneEntries / $totalEntries * 100) : 0,
            ];
        })->sortBy(fn($s) => $s['staff']?->name ?? '');

        $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
        $monthLabel = $monthNames[$month] . ' ' . $year;
        $isCurrentMonth = $month == now()->month && $year == now()->year;

        return view('monthly-targets.month-staff-list', compact(
            'byStaff', 'monthLabel', 'month', 'year', 'isCurrentMonth', 'user'
        ));
    }

    public function create()
    {
        $user = auth()->user();

        // Daftar staf yang bisa di-assign (sesuai dept leader/c-level)
        if (in_array($user->role, ['c_level', 'super_admin'])) {
            $staffList = \App\Models\User::where('role', 'staff')
                ->where('is_active', true)
                ->orderBy('department')
                ->orderBy('name')
                ->get()
                ->groupBy('department');
        } else {
            $staffList = \App\Models\User::where('role', 'staff')
                ->where('department', $user->department)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->groupBy('department');
        }

        // KPI aktif untuk ditampilkan sebagai acuan
        $kpiRefs = \App\Models\KpiTarget::whereNotNull('department')
            ->where('is_active', true)
            ->when(!in_array($user->role, ['c_level', 'super_admin']), fn($q) =>
                $q->where('department', $user->department)
            )
            ->orderBy('department')
            ->get()
            ->groupBy('department');

        return view('monthly-targets.create', compact('staffList', 'kpiRefs'));
    }

    public function store(Request $request)
    {
        $user  = auth()->user();
        $rules = [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'month'         => 'required|integer|min:1|max:12',
            'year'          => 'required|integer|min:2024|max:2030',
            'assigned_to'   => 'nullable|exists:users,id',
            'kpi_target_id' => 'nullable|exists:kpi_targets,id',
        ];

        // C-Level dan Super Admin wajib pilih departemen dari dropdown
        if (in_array($user->role, ['c_level', 'super_admin'])) {
            $deptKeys = implode(',', array_keys(\App\Models\User::DEPARTMENTS));
            $rules['department'] = 'required|string|in:' . $deptKeys;
        }

        $validated = $request->validate($rules);

        $department = in_array($user->role, ['c_level', 'super_admin'])
            ? $validated['department']
            : ($user->department ?? 'ceo_office');

        MonthlyTarget::create([
            'user_id'       => $user->id,           // Leader pembuat
            'assigned_to'   => $validated['assigned_to'] ?? null,  // Staf pemilik
            'kpi_target_id' => $validated['kpi_target_id'] ?? null,
            'department'    => $department,
            'title'         => $validated['title'],
            'description'   => $validated['description'] ?? null,
            'month'         => $validated['month'],
            'year'          => $validated['year'],
        ]);

        return redirect()->route('monthly-targets.index')
            ->with('success', 'Target bulanan berhasil disimpan.');
    }

    public function show(MonthlyTarget $monthlyTarget)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['c_level', 'super_admin'])) {
            abort_if($monthlyTarget->department !== $user->department, 403, 'Anda tidak memiliki akses ke target departemen ini.');
        }

        $monthlyTarget->load([
            'user',
            'weeklyTargets'                        => fn($q) => $q->orderBy('week_number')->orderBy('id'),
            'weeklyTargets.assignee',
            'weeklyTargets.dailyTaskEntries.user',
        ]);

        // ── Statistik laporan per weekly target ───────────────────────────────
        $entriesByWeek = $monthlyTarget->weeklyTargets
            ->mapWithKeys(fn($wt) => [
                $wt->id => [
                    'total'          => $wt->dailyTaskEntries->count(),
                    'done'           => $wt->dailyTaskEntries->where('status', 'selesai')->count(),
                    'pending_review' => $wt->dailyTaskEntries
                                          ->where('status', 'selesai')
                                          ->where('verification_status', 'pending')
                                          ->count(),
                ],
            ]);

        // ── Group weekly targets PER ORANG (untuk accordion) ─────────────────
        // Key = user_id (int) atau 'umum' (null assigned_to)
        $byPerson = $monthlyTarget->weeklyTargets
            ->groupBy(fn($wt) => $wt->assigned_to ?? 'umum');

        // Ambil data user untuk semua assignee, diurutkan abjad
        $assigneeIds = $monthlyTarget->weeklyTargets
            ->pluck('assigned_to')
            ->filter()
            ->unique()
            ->values();

        $assignees = \App\Models\User::whereIn('id', $assigneeIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        // Urutkan byPerson: abjad berdasarkan nama, 'umum' selalu di paling bawah
        $byPersonSorted = collect();
        foreach ($assignees as $uid => $person) {
            if ($byPerson->has($uid)) {
                $byPersonSorted->put($uid, $byPerson->get($uid));
            }
        }
        if ($byPerson->has('umum')) {
            $byPersonSorted->put('umum', $byPerson->get('umum'));
        }

        // ── Sibling monthly targets (bulan & tahun sama) untuk dropdown dept ─
        // Hanya diisi untuk C-Level & Super Admin
        $siblingMonthlyTargets = collect();
        if (in_array($user->role, ['c_level', 'super_admin'])) {
            $siblingMonthlyTargets = MonthlyTarget::where('month', $monthlyTarget->month)
                ->where('year', $monthlyTarget->year)
                ->where('id', '!=', $monthlyTarget->id)
                ->orderBy('department')
                ->get();
        }

        // ── Laporan per weekly target digroup per user (untuk C-Level view) ──
        $leaderEntriesByWeek = [];
        if (in_array($user->role, ['c_level', 'super_admin'])) {
            foreach ($monthlyTarget->weeklyTargets as $wt) {
                $leaderEntriesByWeek[$wt->id] = $wt->dailyTaskEntries->groupBy('user_id');
            }
        }

        return view('monthly-targets.show', compact(
            'monthlyTarget',
            'entriesByWeek',
            'byPersonSorted',
            'assignees',
            'siblingMonthlyTargets',
            'leaderEntriesByWeek'
        ));
    }

    /**
     * [DEPRECATED — redirects ke period.staff-targets]
     * Kept untuk backward compat agar link lama tidak 404.
     */
    public function staffMonthlyTargets(User $staff)
    {
        // Redirect ke bulan sekarang sebagai konteks default
        return redirect()->route('period.staff-targets', [
            'year'  => now()->year,
            'month' => now()->month,
            'staff' => $staff->id,
        ]);
    }

    /**
     * [BARU — Level 3] Daftar monthly target staf untuk bulan tertentu.
     * URL: /monthly-targets/period/{year}/{month}/staff/{staff}
     */
    public function staffTargetsForPeriod(int $year, int $month, User $staff)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['c_level', 'super_admin'])) {
            abort_if($staff->department !== $user->department, 403, 'Akses ditolak.');
        }

        $monthlyTargets = MonthlyTarget::with([
            'weeklyTargets',
            'weeklyTargets.dailyTaskEntries',
            'kpiTarget',
        ])
        ->where('assigned_to', $staff->id)
        ->where('year',  $year)
        ->where('month', $month)
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->get();

        $monthlyTargets->each(function ($mt) {
            $mt->total_entries = $mt->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->count());
            $mt->done_entries  = $mt->weeklyTargets->sum(fn($wt) => $wt->dailyTaskEntries->where('status','selesai')->count());
            $mt->progress_pct  = $mt->total_entries > 0
                ? round($mt->done_entries / $mt->total_entries * 100) : 0;
            $mt->weekly_count  = $mt->weeklyTargets->count();
        });

        $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
        $monthLabel    = $monthNames[$month] . ' ' . $year;
        $isCurrentMonth = $month == now()->month && $year == now()->year;

        return view('monthly-targets.staff-monthly-targets', compact(
            'staff', 'monthlyTargets', 'monthNames', 'user',
            'year', 'month', 'monthLabel', 'isCurrentMonth'
        ));
    }

    /**
     * [BARU — Level 4] Weekly targets staf untuk monthly target + period context.
     * URL: /monthly-targets/period/{year}/{month}/staff/{staff}/{monthlyTarget}
     */
    public function showStaffInPeriod(int $year, int $month, User $staff, MonthlyTarget $monthlyTarget)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['c_level', 'super_admin'])) {
            abort_if($monthlyTarget->department !== $user->department, 403);
        }

        // Delegasi ke logika showStaff, tambah period context
        $assignee = (string) $staff->id;

        $monthlyTarget->load([
            'user',
            'weeklyTargets' => fn($q) => $q->where('assigned_to', $staff->id)->orderBy('week_number')->orderBy('id'),
            'weeklyTargets.assignee',
            'weeklyTargets.dailyTaskEntries.user',
        ]);

        $entriesByWeek = $monthlyTarget->weeklyTargets
            ->mapWithKeys(fn($wt) => [
                $wt->id => [
                    'total'          => $wt->dailyTaskEntries->count(),
                    'done'           => $wt->dailyTaskEntries->where('status','selesai')->count(),
                    'pending_review' => $wt->dailyTaskEntries
                                          ->where('status','selesai')
                                          ->where('verification_status','pending')
                                          ->count(),
                ],
            ]);

        $personTargets = $monthlyTarget->weeklyTargets;
        $person   = $staff;
        $pName    = $staff->name;
        $pDiv     = $staff->division ?? $staff->department;
        $personKey = $assignee;

        $avatarColors = ['#1B4FD8','#6D28D9','#0E7490','#065F46','#9A3412','#1D4ED8','#7C3AED','#047857'];
        $initials = collect(explode(' ', $pName))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
        $colorIdx = abs(crc32($pName) % count($avatarColors));
        $bgColor  = $avatarColors[$colorIdx];

        $pTotalEntry = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['total'] ?? 0));
        $pDoneEntry  = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['done']  ?? 0));
        $pProgress   = $pTotalEntry > 0 ? round($pDoneEntry / $pTotalEntry * 100) : 0;
        $weekRanges  = \App\Models\WeeklyTarget::WEEK_RANGES;

        // Back URL: naik satu level ke period.staff-targets
        $backUrl = route('period.staff-targets', ['year' => $year, 'month' => $month, 'staff' => $staff->id]);

        return view('monthly-targets.show-staff', compact(
            'monthlyTarget', 'personTargets', 'entriesByWeek', 'person', 'pName', 'pDiv', 'personKey',
            'initials', 'bgColor', 'pTotalEntry', 'pDoneEntry', 'pProgress', 'weekRanges',
            'year', 'month', 'backUrl'
        ));
    }

    public function showStaff(MonthlyTarget $monthlyTarget, $assignee)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['c_level', 'super_admin'])) {
            abort_if($monthlyTarget->department !== $user->department, 403, 'Anda tidak memiliki akses ke target departemen ini.');
        }

        $monthlyTarget->load([
            'user',
            'weeklyTargets' => fn($q) => $q->where('assigned_to', $assignee === 'umum' ? null : $assignee)->orderBy('week_number')->orderBy('id'),
            'weeklyTargets.assignee',
            'weeklyTargets.dailyTaskEntries.user',
        ]);

        $entriesByWeek = $monthlyTarget->weeklyTargets
            ->mapWithKeys(fn($wt) => [
                $wt->id => [
                    'total'          => $wt->dailyTaskEntries->count(),
                    'done'           => $wt->dailyTaskEntries->where('status', 'selesai')->count(),
                    'pending_review' => $wt->dailyTaskEntries
                                          ->where('status', 'selesai')
                                          ->where('verification_status', 'pending')
                                          ->count(),
                ],
            ]);

        $personTargets = $monthlyTarget->weeklyTargets;

        if ($assignee !== 'umum') {
            $person = \App\Models\User::findOrFail($assignee);
            $pName = $person->name;
            $pDiv = $person->division ?? $person->department;
            $initials = collect(explode(' ', $pName))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
        } else {
            $person = null;
            $pName = 'Target Umum (Seluruh Tim)';
            $pDiv = '';
            $initials = '🏢';
        }
        $personKey = $assignee;

        // Hitung persentase progress untuk banner atas
        $pTotalEntry  = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['total'] ?? 0));
        $pDoneEntry   = $personTargets->sum(fn($wt) => ($entriesByWeek[$wt->id]['done']  ?? 0));
        $pProgress    = $pTotalEntry > 0 ? round($pDoneEntry / $pTotalEntry * 100) : 0;
        
        $weekRanges = []; // Biar ga undefined di view if needed

        return view('monthly-targets.show-staff', compact(
            'monthlyTarget', 'personTargets', 'entriesByWeek', 'person', 'pName', 'pDiv', 'personKey', 'initials',
            'pTotalEntry', 'pDoneEntry', 'pProgress', 'weekRanges'
        ));
    }


    public function edit(MonthlyTarget $monthlyTarget)
    {
        $this->authorizeEdit($monthlyTarget);
        return view('monthly-targets.edit', compact('monthlyTarget'));
    }

    public function update(Request $request, MonthlyTarget $monthlyTarget)
    {
        $this->authorizeEdit($monthlyTarget);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'month'       => 'required|integer|min:1|max:12',
            'year'        => 'required|integer|min:2024|max:2030',
        ]);

        $monthlyTarget->update($validated);

        return redirect()->route('monthly-targets.index')
            ->with('success', 'Target bulanan berhasil diperbarui.');
    }

    public function destroy(MonthlyTarget $monthlyTarget)
    {
        $this->authorizeEdit($monthlyTarget);

        $monthlyTarget->delete();

        return redirect()->route('monthly-targets.index')
            ->with('success', 'Target bulanan berhasil dihapus.');
    }

    private function authorizeEdit(MonthlyTarget $monthlyTarget)
    {
        $user = auth()->user();
        if (in_array($user->role, ['c_level', 'super_admin'])) {
            return;
        }

        abort_if($monthlyTarget->department !== $user->department, 403, 'Anda tidak memiliki akses untuk mengubah target departemen ini.');
    }
}
