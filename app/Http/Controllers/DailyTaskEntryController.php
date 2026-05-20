<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyTaskEntryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $entries = DailyTaskEntry::with(['monthlyTarget', 'weeklyTarget'])
            ->where('user_id', $user->id)
            ->orderByDesc('task_date')
            ->orderByDesc('id')
            ->get();

        return view('daily-tasks.index', compact('entries'));
    }

    public function show(DailyTaskEntry $dailyTask)
    {
        $user = auth()->user();

        // Pemilik: boleh lihat laporan sendiri
        // Leader: boleh lihat laporan staff se-departemen
        // C-Level: boleh lihat semua laporan
        $canView = $dailyTask->user_id === $user->id
            || $user->role === 'c_level'
            || ($user->role === 'leader' && $dailyTask->user->department === $user->department);

        if (!$canView) {
            abort(403, 'Anda tidak memiliki akses untuk melihat laporan ini.');
        }

        $dailyTask->load(['weeklyTarget.monthlyTarget', 'monthlyTarget', 'user']);

        return view('daily-tasks.show', compact('dailyTask'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        // Ambil weekly targets yang relevan untuk user ini:
        // - Staff: weekly target dari monthly target (leader) untuk dept-nya
        // - Leader: HANYA weekly target dari monthly target yang dibuat C-Level untuk dept-nya
        // - C-Level: semua weekly target bulan ini lintas department
        $weeklyTargets = WeeklyTarget::with('monthlyTarget')
            ->where(function ($q) use ($user) {
                $q->whereHas('monthlyTarget', function ($mq) use ($user) {
                    $mq->where('month', now()->month)
                       ->where('year', now()->year);
                    if (!empty($user->department)) {
                        $mq->where('department', $user->department);
                    }
                    // Leader hanya lihat weekly target dari monthly yang dibuat C-Level
                    if ($user->role === 'leader') {
                        $mq->whereHas('user', fn($uq) => $uq->where('role', 'c_level'));
                    }
                    // Staff hanya lihat weekly target dari monthly yang dibuat Leader
                    if ($user->role === 'staff') {
                        $mq->whereHas('user', fn($uq) => $uq->where('role', 'leader'));
                    }
                });
            })
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->orderBy('week_number')
            ->get();

        // "Lanjutkan dari kemarin" — task milik user yang masih dalam_proses/terhambat,
        // dari hari sebelum hari ini (bukan task hari ini yang masih bisa di-edit langsung)
        $continuableTasks = DailyTaskEntry::with('weeklyTarget')
            ->where('user_id', $user->id)
            ->whereIn('status', ['dalam_proses', 'terhambat'])
            ->whereDate('task_date', '<', today())
            ->orderByDesc('task_date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Jika user pilih lanjut dari task tertentu → pre-fill form
        $continueFrom = null;
        if ($request->filled('continue_from')) {
            $continueFrom = DailyTaskEntry::where('user_id', $user->id)
                ->where('id', $request->continue_from)
                ->whereIn('status', ['dalam_proses', 'terhambat'])
                ->first();
        }

        // Pre-select weekly target jika datang dari halaman staff-targets
        $preSelectedWeeklyId = $request->filled('weekly_target_id')
            ? (int) $request->weekly_target_id
            : null;

        return view('daily-tasks.create', compact('weeklyTargets', 'continuableTasks', 'continueFrom', 'preSelectedWeeklyId'));
    }

    /**
     * Form edit — hanya boleh untuk laporan hari ini yang belum di-mark selesai.
     */
    public function edit(DailyTaskEntry $dailyTask)
    {
        $this->authorizeEdit($dailyTask);

        $user = auth()->user();

        $weeklyTargets = WeeklyTarget::with('monthlyTarget')
            ->where(function ($q) use ($user) {
                $q->whereHas('monthlyTarget', function ($mq) use ($user) {
                    $mq->where('month', now()->month)
                       ->where('year', now()->year);
                    if (!empty($user->department)) {
                        $mq->where('department', $user->department);
                    }
                    // Leader hanya lihat weekly target dari monthly yang dibuat C-Level
                    if ($user->role === 'leader') {
                        $mq->whereHas('user', fn($uq) => $uq->where('role', 'c_level'));
                    }
                    // Staff hanya lihat weekly target dari monthly yang dibuat Leader
                    if ($user->role === 'staff') {
                        $mq->whereHas('user', fn($uq) => $uq->where('role', 'leader'));
                    }
                });
            })
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->orderBy('week_number')
            ->get();

        return view('daily-tasks.edit', compact('dailyTask', 'weeklyTargets'));
    }

    public function update(Request $request, DailyTaskEntry $dailyTask)
    {
        $this->authorizeEdit($dailyTask);

        $validated = $request->validate([
            'weekly_target_id'  => 'nullable|exists:weekly_targets,id',
            'task_description'  => 'required|string',
            'priority'          => ['required', Rule::in(array_keys(DailyTaskEntry::PRIORITIES))],
            'duration_value'    => 'required|integer|min:1|max:1440',
            'duration_unit'     => ['required', Rule::in(['menit', 'jam'])],
            'status'            => ['required', Rule::in(array_keys(DailyTaskEntry::STATUSES))],
            'notes'             => 'required|string|min:5',
        ], [
            'notes.required' => 'Catatan wajib diisi untuk semua status.',
            'notes.min'      => 'Catatan minimal 5 karakter — jelaskan konteks/progress task.',
        ]);

        $durationMinutes = $validated['duration_unit'] === 'jam'
            ? $validated['duration_value'] * 60
            : $validated['duration_value'];

        if ($durationMinutes > 1440) {
            return back()->withInput()
                ->withErrors(['duration_value' => 'Durasi maksimal 24 jam (1440 menit).']);
        }

        // Resolve target context — kalau "Other" (weekly_target_id null), tidak ada parent monthly
        $weeklyTarget    = !empty($validated['weekly_target_id'])
            ? WeeklyTarget::find($validated['weekly_target_id'])
            : null;
        $monthlyTargetId = $weeklyTarget?->monthly_target_id;

        $dailyTask->update([
            'monthly_target_id' => $monthlyTargetId,
            'weekly_target_id'  => $weeklyTarget?->id,
            'task_description'  => $validated['task_description'],
            'priority'          => $validated['priority'],
            'duration_minutes'  => $durationMinutes,
            'status'            => $validated['status'],
            'notes'             => $validated['notes'],
            // task_date TIDAK diubah — tetap tanggal asli submit
        ]);

        return redirect()->route('daily-tasks.show', $dailyTask)
            ->with('success', 'Laporan berhasil diperbarui.');
    }

    /**
     * Guard untuk edit/update:
     * - Harus pemilik entry.
     * - Status belum 'selesai' (task selesai bersifat final/historis).
     *
     * Constraint "hanya bisa edit hari yang sama" DIHILANGKAN sesuai notul rapat 12 Mei 2026.
     * Alasan: pekerjaan operasional bisa berlangsung berhari-hari. Staff perlu bisa
     * mengupdate catatan & status task 'Terhambat' / 'Dalam Proses' yang masih ongoing.
     */
    private function authorizeEdit(DailyTaskEntry $dailyTask): void
    {
        if ($dailyTask->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        if ($dailyTask->status === 'selesai') {
            abort(403, 'Laporan yang sudah ditandai selesai bersifat final dan tidak bisa diubah.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'weekly_target_id'  => 'nullable|exists:weekly_targets,id',
            'task_description'  => 'required|string',
            'priority'          => ['required', Rule::in(array_keys(DailyTaskEntry::PRIORITIES))],
            'duration_value'    => 'required|integer|min:1|max:1440',
            'duration_unit'     => ['required', Rule::in(['menit', 'jam'])],
            'status'            => ['required', Rule::in(array_keys(DailyTaskEntry::STATUSES))],
            'notes'             => 'required|string|min:5',
        ], [
            'notes.required' => 'Catatan wajib diisi untuk semua status.',
            'notes.min'      => 'Catatan minimal 5 karakter — jelaskan konteks/progress task.',
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

        // "Other" support: weekly_target_id boleh kosong (task ad-hoc dari CEO/CTO).
        // Kalau ada weekly target, derive monthly dari parent-nya.
        $weeklyTarget    = !empty($validated['weekly_target_id'])
            ? WeeklyTarget::find($validated['weekly_target_id'])
            : null;
        $monthlyTargetId = $weeklyTarget?->monthly_target_id;

        DailyTaskEntry::create([
            'user_id'           => auth()->id(),
            'monthly_target_id' => $monthlyTargetId,
            'weekly_target_id'  => $weeklyTarget?->id,
            'task_description'  => $validated['task_description'],
            'priority'          => $validated['priority'],
            'duration_minutes'  => $durationMinutes,
            'status'            => $validated['status'],
            'notes'             => $validated['notes'],
            'task_date'         => now()->toDateString(), // selalu hari ini
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Laporan tugas berhasil disimpan.');
    }

    /**
     * Tandai entry sebagai selesai.
     *
     * - Jika catatan SUDAH ada → langsung update status ke 'selesai', redirect ke show.
     * - Jika catatan BELUM ada → arahkan ke form edit agar staff isi catatan dulu.
     *   (Sesuai aturan rapat 12 Mei 2026: semua status 'selesai' wajib punya catatan.)
     */
    public function complete(DailyTaskEntry $dailyTask)
    {
        if ($dailyTask->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        if ($dailyTask->status === 'selesai') {
            return redirect()->route('daily-tasks.show', $dailyTask)
                ->with('info', 'Tugas ini sudah ditandai selesai sebelumnya.');
        }

        // Catatan sudah ada → langsung selesaikan tanpa perlu ke form edit
        if (!empty($dailyTask->notes)) {
            $dailyTask->update(['status' => 'selesai']);

            return redirect()->route('daily-tasks.show', $dailyTask)
                ->with('success', '✅ Tugas berhasil ditandai selesai!');
        }

        // Catatan belum ada → wajib isi dulu sebelum bisa selesaikan
        return redirect()
            ->route('daily-tasks.edit', $dailyTask)
            ->with('complete_mode', true)
            ->with('info', 'Isi catatan penyelesaian terlebih dahulu, lalu simpan untuk menandai tugas selesai.');
    }
}
