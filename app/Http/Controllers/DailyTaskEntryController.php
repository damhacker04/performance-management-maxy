<?php

namespace App\Http\Controllers;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

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

        // ── LAZY AUTO-REJECT ──────────────────────────────────────────────────
        // Jika laporan masih status 'revision' tapi window 10 jam sudah habis,
        // otomatis ubah ke 'rejected' dan kirim notifikasi ke leader.
        if ($dailyTask->verification_status === 'revision' && !$dailyTask->canBeRevised()) {
            $dailyTask->load('user');
            $dailyTask->update([
                'verification_status' => 'rejected',
                'rejection_note'      => ($dailyTask->rejection_note ?? '') .
                                         ' [Otomatis ditolak: staff tidak merevisi dalam 10 jam]',
            ]);
            NotificationHelper::autoRejected($dailyTask);
            $dailyTask->refresh();
        }
        // ─────────────────────────────────────────────────────────────────────

        $dailyTask->load(['weeklyTarget.monthlyTarget', 'monthlyTarget', 'user', 'verifiedBy']);

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

        $user    = auth()->user();
        $isSales = $user->department === 'sales';

        $validated = $request->validate([
            'weekly_target_id'  => 'nullable|exists:weekly_targets,id',
            'task_description'  => 'required|string',
            'priority'          => ['required', Rule::in(array_keys(DailyTaskEntry::PRIORITIES))],
            'duration_value'    => 'required|integer|min:1|max:1440',
            'duration_unit'     => ['required', Rule::in(['menit', 'jam'])],
            'status'            => ['required', Rule::in(array_keys(DailyTaskEntry::STATUSES))],
            'notes'             => 'required|string|min:5',
            'proof_url'         => $isSales ? 'required_without:proof_file|nullable|url' : 'nullable|url',
            'proof_file'        => $isSales ? 'required_without:proof_url|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120' : 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'notes.required'              => 'Catatan wajib diisi untuk semua status.',
            'notes.min'                   => 'Catatan minimal 5 karakter — jelaskan konteks/progress task.',
            'proof_url.required_without'  => 'Departemen Sales wajib mengisi link atau upload file bukti.',
            'proof_file.required_without' => 'Departemen Sales wajib mengisi link atau upload file bukti.',
            'proof_url.url'               => 'Format link tidak valid. Pastikan diawali dengan http:// atau https://',
            'proof_file.mimes'            => 'File harus berformat JPG, PNG, atau PDF.',
            'proof_file.max'              => 'Ukuran file maksimal 5MB.',
        ]);

        $durationMinutes = $validated['duration_unit'] === 'jam'
            ? $validated['duration_value'] * 60
            : $validated['duration_value'];

        if ($durationMinutes > 1440) {
            return back()->withInput()
                ->withErrors(['duration_value' => 'Durasi maksimal 24 jam (1440 menit).']);
        }

        // Resolve target context
        $weeklyTarget    = !empty($validated['weekly_target_id'])
            ? WeeklyTarget::find($validated['weekly_target_id'])
            : null;
        $monthlyTargetId = $weeklyTarget?->monthly_target_id;

        // Handle upload file bukti (jika ada file baru, hapus yang lama)
        $proofFilePath = $dailyTask->proof_file; // pertahankan file lama jika tidak ada upload baru
        if ($request->hasFile('proof_file')) {
            // Hapus file lama
            if ($dailyTask->proof_file) {
                Storage::disk('public')->delete($dailyTask->proof_file);
            }
            $proofFilePath = $request->file('proof_file')->store('proofs', 'public');
        }

        $wasRevision = $dailyTask->verification_status === 'revision';

        $updateData = [
            'monthly_target_id' => $monthlyTargetId,
            'weekly_target_id'  => $weeklyTarget?->id,
            'task_description'  => $validated['task_description'],
            'priority'          => $validated['priority'],
            'duration_minutes'  => $durationMinutes,
            'status'            => $validated['status'],
            'notes'             => $validated['notes'],
            'proof_url'         => $validated['proof_url'] ?? $dailyTask->proof_url,
            'proof_file'        => $proofFilePath,
        ];

        // Jika laporan sedang revision dan staff menyimpan → kembalikan ke pending
        // + simpan respons staff ke entry terakhir revision_history
        if ($wasRevision) {
            $updateData['verification_status'] = 'pending';

            // Tambahkan respons staff ke entri revisi terakhir
            $history = $dailyTask->revision_history ?? [];
            if (!empty($history)) {
                $lastIdx = count($history) - 1;
                $history[$lastIdx]['staff_response']    = $validated['notes'] ?? null;
                $history[$lastIdx]['staff_responded_at'] = now()->toDateTimeString();
                $history[$lastIdx]['staff_name']        = $user->name;
            }
            $updateData['revision_history'] = $history;
        }

        $dailyTask->update($updateData);

        // Kirim notifikasi ke leader bahwa revisi sudah dikirim
        if ($wasRevision) {
            NotificationHelper::revisionSubmitted($dailyTask, $user);
        }

        $message = $wasRevision
            ? '📨 Revisi berhasil dikirim. Laporan sedang menunggu review leader.'
            : 'Laporan berhasil diperbarui.';

        return redirect()->route('daily-tasks.show', $dailyTask)
            ->with('success', $message);
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

        // Laporan approved tidak bisa diedit sama sekali
        if ($dailyTask->verification_status === 'approved') {
            abort(403, 'Laporan yang sudah diverifikasi tidak bisa diubah.');
        }

        // Laporan yang dikembalikan leader untuk direvisi BOLEH diedit,
        // meskipun statusnya sudah 'selesai' — ini pengecualian khusus revisi
        if ($dailyTask->verification_status === 'revision') {
            if (!$dailyTask->canBeRevised()) {
                abort(403, 'Masa revisi 10 jam sudah berakhir. Laporan tidak bisa diubah lagi.');
            }
            return; // Izinkan edit
        }

        // Laporan yang sudah ditandai selesai (dan bukan revision) bersifat final
        if ($dailyTask->status === 'selesai') {
            abort(403, 'Laporan yang sudah ditandai selesai bersifat final dan tidak bisa diubah.');
        }
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isSales = $user->department === 'sales';

        $validated = $request->validate([
            'weekly_target_id'  => 'nullable|exists:weekly_targets,id',
            'task_description'  => 'required|string',
            'priority'          => ['required', Rule::in(array_keys(DailyTaskEntry::PRIORITIES))],
            'duration_value'    => 'required|integer|min:1|max:1440',
            'duration_unit'     => ['required', Rule::in(['menit', 'jam'])],
            'status'            => ['required', Rule::in(array_keys(DailyTaskEntry::STATUSES))],
            'notes'             => 'required|string|min:5',
            // Bukti laporan: wajib untuk sales (salah satu harus diisi), opsional untuk lainnya
            'proof_url'         => $isSales ? 'required_without:proof_file|nullable|url' : 'nullable|url',
            'proof_file'        => $isSales ? 'required_without:proof_url|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120' : 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'notes.required'         => 'Catatan wajib diisi untuk semua status.',
            'notes.min'              => 'Catatan minimal 5 karakter — jelaskan konteks/progress task.',
            'proof_url.required_without' => 'Departemen Sales wajib mengisi link atau upload file bukti.',
            'proof_file.required_without'=> 'Departemen Sales wajib mengisi link atau upload file bukti.',
            'proof_url.url'          => 'Format link tidak valid. Pastikan diawali dengan http:// atau https://',
            'proof_file.mimes'       => 'File harus berformat JPG, PNG, atau PDF.',
            'proof_file.max'         => 'Ukuran file maksimal 5MB.',
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

        // Handle upload file bukti
        $proofFilePath = null;
        if ($request->hasFile('proof_file')) {
            $proofFilePath = $request->file('proof_file')->store('proofs', 'public');
        }

        $entry = DailyTaskEntry::create([
            'user_id'           => auth()->id(),
            'monthly_target_id' => $monthlyTargetId,
            'weekly_target_id'  => $weeklyTarget?->id,
            'task_description'  => $validated['task_description'],
            'priority'          => $validated['priority'],
            'duration_minutes'  => $durationMinutes,
            'status'            => $validated['status'],
            'notes'             => $validated['notes'],
            'task_date'         => now()->toDateString(),
            'proof_url'         => $validated['proof_url'] ?? null,
            'proof_file'        => $proofFilePath,
        ]);

        return redirect()->route('daily-tasks.show', $entry)
            ->with('success', '✅ Laporan berhasil dikirim!');
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
        // Hanya update kolom 'status' — JANGAN sentuh verification_status
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

    // ─── APPROVAL METHODS ─────────────────────────────────────────────────────

    /**
     * Leader menyetujui laporan → approved (terkunci permanen).
     */
    public function approve(DailyTaskEntry $dailyTask)
    {
        $this->authorizeReview($dailyTask);

        $dailyTask->update([
            'verification_status' => 'approved',
            'verified_by'         => auth()->id(),
            'verified_at'         => now(),
            'rejection_note'      => null,
        ]);

        return redirect()->route('daily-tasks.show', $dailyTask)
            ->with('success', '✅ Laporan berhasil diverifikasi dan disetujui.');
    }

    /**
     * Leader mengembalikan laporan untuk direvisi → revision.
     * Staff masih bisa edit (dalam 48 jam), tapi field kunci tidak berubah.
     */
    public function sendToRevision(DailyTaskEntry $dailyTask, Request $request)
    {
        $this->authorizeReview($dailyTask);

        $request->validate([
            'rejection_note' => 'required|string|min:10',
        ], [
            'rejection_note.required' => 'Catatan revisi wajib diisi agar staff tahu apa yang harus diperbaiki.',
            'rejection_note.min'      => 'Catatan revisi minimal 10 karakter.',
        ]);

        // Append catatan baru ke histori revisi (tidak menimpa catatan lama)
        $history = $dailyTask->revision_history ?? [];
        $history[] = [
            'note'  => $request->rejection_note,
            'by'    => auth()->user()->name,
            'by_id' => auth()->id(),
            'at'    => now()->toDateTimeString(),
        ];

        $dailyTask->update([
            'verification_status' => 'revision',
            'verified_by'         => auth()->id(),
            'verified_at'         => null,
            'rejection_note'      => $request->rejection_note, // catatan terbaru (untuk notif sederhana)
            'reviewed_at'         => now(),
            'revision_history'    => $history,
        ]);

        // Kirim notifikasi ke staff bahwa laporan perlu direvisi
        NotificationHelper::revisionRequested($dailyTask, auth()->user());

        return redirect()->route('daily-tasks.show', $dailyTask)
            ->with('info', '↩ Laporan dikembalikan ke staff untuk direvisi.');
    }

    /**
     * Leader menolak laporan secara permanen → rejected.
     * Staff TIDAK bisa revisi.
     */
    public function reject(DailyTaskEntry $dailyTask, Request $request)
    {
        $this->authorizeReview($dailyTask);

        $request->validate([
            'rejection_note' => 'required|string|min:10',
        ], [
            'rejection_note.required' => 'Alasan penolakan wajib diisi.',
            'rejection_note.min'      => 'Alasan penolakan minimal 10 karakter.',
        ]);

        $dailyTask->update([
            'verification_status' => 'rejected',
            'verified_by'         => auth()->id(),
            'verified_at'         => null,
            'rejection_note'      => $request->rejection_note,
            'reviewed_at'         => now(),
        ]);

        return redirect()->route('daily-tasks.show', $dailyTask)
            ->with('error', '❌ Laporan telah ditolak secara permanen.');
    }

    /**
     * Guard untuk review (approve/revision/reject):
     * - Hanya leader atau c_level.
     * - Leader hanya bisa review laporan staff se-departemen (atau laporan sendiri tidak berlaku).
     * - Laporan yang sudah approved tidak bisa di-review ulang.
     */
    private function authorizeReview(DailyTaskEntry $dailyTask): void
    {
        $user = auth()->user();

        if (!in_array($user->role, ['leader', 'c_level'])) {
            abort(403, 'Hanya Leader atau C-Level yang dapat memverifikasi laporan.');
        }

        // Leader hanya bisa review laporan dari dept-nya sendiri (kecuali C-Level)
        if ($user->role === 'leader') {
            $dailyTask->load('user');
            if ($dailyTask->user->department !== $user->department) {
                abort(403, 'Anda hanya dapat memverifikasi laporan dari departemen Anda.');
            }
        }

        if ($dailyTask->verification_status === 'approved') {
            abort(403, 'Laporan yang sudah disetujui tidak dapat diubah statusnya.');
        }
    }
}
