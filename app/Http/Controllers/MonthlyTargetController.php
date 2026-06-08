<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTarget;
use Illuminate\Http\Request;

class MonthlyTargetController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $targets = MonthlyTarget::with(['user', 'weeklyTargets', 'weeklyTargets.dailyTaskEntries'])
            ->when($user->role === 'leader' || $user->role === 'staff', fn($q) => 
                $q->where('department', $user->department)
            )
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('monthly-targets.index', compact('targets'));
    }

    public function create()
    {
        return view('monthly-targets.create');
    }

    public function store(Request $request)
    {
        $user  = auth()->user();
        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'month'       => 'required|integer|min:1|max:12',
            'year'        => 'required|integer|min:2024|max:2030',
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

        // Tentukan pemilik target (user_id)
        $targetUserId = $user->id;
        
        if ($user->role === 'super_admin') {
            // Cari Leader di departemen tersebut
            $leader = \App\Models\User::where('department', $department)
                ->where('role', 'leader')
                ->where('is_active', true)
                ->first();
                
            if ($leader) {
                $targetUserId = $leader->id;
            } else {
                // Jika tidak ada leader, fallback ke C-Level (CEO)
                $cLevel = \App\Models\User::where('role', 'c_level')->where('is_active', true)->first();
                $targetUserId = $cLevel ? $cLevel->id : $user->id;
            }
        }

        MonthlyTarget::create([
            'user_id'     => $targetUserId,
            'department'  => $department,
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'month'       => $validated['month'],
            'year'        => $validated['year'],
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
