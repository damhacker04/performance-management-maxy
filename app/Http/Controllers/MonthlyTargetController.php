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
            ->when($user->role === 'leader', fn($q) => $q->where('user_id', $user->id))
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
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'month'       => 'required|integer|min:1|max:12',
            'year'        => 'required|integer|min:2024|max:2030',
        ]);

        $user = auth()->user();

        MonthlyTarget::create([
            'user_id'     => $user->id,
            'department'  => $user->department ?? 'ceo_office',
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'month'       => $validated['month'],
            'year'        => $validated['year'],
        ]);

        return redirect()->route('monthly-targets.index')
            ->with('success', 'Target bulanan berhasil disimpan.');
    }

    public function show(MonthlyTarget $monthlyTarget)
    {
        $monthlyTarget->load([
            'user',
            'weeklyTargets' => fn($q) => $q->orderBy('week_number'),
            'weeklyTargets.dailyTaskEntries.user',
        ]);

        // Statistik laporan per weekly target
        $entriesByWeek = $monthlyTarget->weeklyTargets
            ->mapWithKeys(fn($wt) => [
                $wt->id => [
                    'total' => $wt->dailyTaskEntries->count(),
                    'done'  => $wt->dailyTaskEntries->where('status', 'selesai')->count(),
                ],
            ]);

        // Untuk C-Level: laporan leader per weekly target, digroup [weekly_id][user_id]
        // Data sudah ter-eager load via dailyTaskEntries.user — tidak ada query tambahan
        $leaderEntriesByWeek = [];
        if (auth()->user()->role === 'c_level') {
            foreach ($monthlyTarget->weeklyTargets as $wt) {
                $leaderEntriesByWeek[$wt->id] = $wt->dailyTaskEntries
                    ->groupBy('user_id');
            }
        }

        return view('monthly-targets.show', compact('monthlyTarget', 'entriesByWeek', 'leaderEntriesByWeek'));
    }

    public function edit(MonthlyTarget $monthlyTarget)
    {
        return view('monthly-targets.edit', compact('monthlyTarget'));
    }

    public function update(Request $request, MonthlyTarget $monthlyTarget)
    {
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
        $monthlyTarget->delete();

        return redirect()->route('monthly-targets.index')
            ->with('success', 'Target bulanan berhasil dihapus.');
    }
}
