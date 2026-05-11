<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        // Hanya target yang berjalan di bulan ini (sesuai dept user)
        $targets = MonthlyTarget::where('department', $user->department)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->orderByDesc('created_at')
            ->get();

        return view('daily-tasks.create', compact('targets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'monthly_target_id' => 'required|exists:monthly_targets,id',
            'task_description'  => 'required|string',
            'duration_value'    => 'required|integer|min:1|max:1440',
            'duration_unit'     => ['required', Rule::in(['menit', 'jam'])],
            'status'            => ['required', Rule::in(['selesai', 'dalam_proses', 'terhambat'])],
            'notes'             => 'nullable|string|required_if:status,terhambat',
        ], [
            'notes.required_if' => 'Catatan wajib diisi jika status Terhambat.',
        ]);

        // Konversi durasi -> menit
        $durationMinutes = $validated['duration_unit'] === 'jam'
            ? $validated['duration_value'] * 60
            : $validated['duration_value'];

        if ($durationMinutes > 1440) {
            return back()
                ->withInput()
                ->withErrors(['duration_value' => 'Durasi maksimal 24 jam (1440 menit).']);
        }

        DailyTaskEntry::create([
            'user_id'           => auth()->id(),
            'monthly_target_id' => $validated['monthly_target_id'],
            'task_description'  => $validated['task_description'],
            'duration_minutes'  => $durationMinutes,
            'status'            => $validated['status'],
            'notes'             => $validated['notes'] ?? null,
            'task_date'         => now()->toDateString(), // selalu hari ini
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Laporan tugas berhasil disimpan.');
    }

    /**
     * Tandai entry sebagai selesai (dipanggil dari checkbox di dashboard).
     */
    public function complete(DailyTaskEntry $dailyTask)
    {
        // Pastikan hanya pemilik entry yang bisa mengubah
        if ($dailyTask->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        $dailyTask->update(['status' => 'selesai']);

        return redirect()->route('dashboard')
            ->with('success', 'Tugas ditandai selesai.');
    }
}
