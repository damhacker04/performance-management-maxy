<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\BackdateRequest;
use App\Models\User;
use Illuminate\Http\Request;

class BackdateRequestController extends Controller
{
    /**
     * Form pengajuan backdating untuk staf.
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // Bangun daftar tanggal yang bisa dipilih (kemarin s/d 3 hari ke belakang)
        // kecuali yang sudah punya permintaan approved/pending
        $existingDates = BackdateRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('requested_date', '>=', today()->subDays(3))
            ->pluck('requested_date')
            ->map(fn($d) => $d->toDateString())
            ->toArray();

        $availableDates = [];
        for ($i = 1; $i <= 3; $i++) {
            $date = today()->subDays($i);
            if (!in_array($date->toDateString(), $existingDates)) {
                $availableDates[] = $date;
            }
        }

        return view('backdate-requests.create', compact('availableDates'));
    }

    /**
     * Simpan permintaan backdating baru.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'requested_date' => [
                'required',
                'date',
                function ($attr, $value, $fail) use ($user) {
                    $date = \Carbon\Carbon::parse($value);
                    // Tanggal harus di masa lalu (kemarin atau lebih lama)
                    if (!$date->isPast() || $date->isToday()) {
                        return $fail('Hanya bisa meminta backdating untuk hari kemarin atau sebelumnya.');
                    }
                    // Maksimal 3 hari ke belakang
                    if ($date->lt(today()->subDays(3))) {
                        return $fail('Backdating hanya diizinkan maksimal 3 hari ke belakang.');
                    }
                    // Belum ada permintaan pending/approved untuk tanggal ini
                    $exists = BackdateRequest::where('user_id', $user->id)
                        ->whereDate('requested_date', $date->toDateString())
                        ->whereIn('status', ['pending', 'approved'])
                        ->exists();
                    if ($exists) {
                        return $fail('Kamu sudah punya permintaan aktif untuk tanggal ini.');
                    }
                },
            ],
            'reason' => 'required|string|min:10|max:500',
        ], [
            'requested_date.required' => 'Tanggal wajib dipilih.',
            'reason.required'         => 'Alasan wajib diisi.',
            'reason.min'              => 'Alasan minimal 10 karakter — jelaskan kenapa kamu butuh backdating.',
        ]);

        $req = BackdateRequest::create([
            'user_id'        => $user->id,
            'requested_date' => $validated['requested_date'],
            'reason'         => $validated['reason'],
            'status'         => 'pending',
        ]);

        // Kirim notifikasi ke leader
        NotificationHelper::backdateRequested($req->load('user'));

        return redirect()->route('daily-tasks.create')
            ->with('success', '📨 Permintaan backdating berhasil dikirim! Tunggu konfirmasi dari leader.');
    }

    /**
     * Daftar permintaan backdating pending — untuk leader/super_admin/c_level.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = BackdateRequest::with(['user', 'reviewer'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at');

        // Leader hanya lihat dari departemennya
        if ($user->role === 'leader') {
            $query->whereHas('user', fn($q) =>
                $q->where('department', $user->department)
            );
        }

        $filter = $request->query('filter', 'pending');
        if (in_array($filter, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filter);
        }

        $requests       = $query->get();
        $pendingCount   = BackdateRequest::when($user->role === 'leader', fn($q) =>
            $q->whereHas('user', fn($uq) => $uq->where('department', $user->department))
        )->where('status', 'pending')->count();

        return view('backdate-requests.index', compact('requests', 'filter', 'pendingCount'));
    }

    /**
     * Leader menyetujui permintaan backdating.
     */
    public function approve(BackdateRequest $backdateRequest)
    {
        $this->authorizeReview($backdateRequest);

        if ($backdateRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses sebelumnya.');
        }

        $backdateRequest->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Generate token approval (berlaku 24 jam)
        $backdateRequest->generateApprovalToken();

        // Kirim notifikasi ke staf
        NotificationHelper::backdateReviewed($backdateRequest->load('user', 'reviewer'));

        return back()->with('success', '✅ Permintaan disetujui. Staf bisa mengisi laporan dalam 24 jam.');
    }

    /**
     * Leader menolak permintaan backdating.
     */
    public function reject(BackdateRequest $backdateRequest, Request $request)
    {
        $this->authorizeReview($backdateRequest);

        if ($backdateRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'rejection_note' => 'required|string|min:5',
        ], [
            'rejection_note.required' => 'Alasan penolakan wajib diisi.',
            'rejection_note.min'      => 'Alasan minimal 5 karakter.',
        ]);

        $backdateRequest->update([
            'status'         => 'rejected',
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
            'rejection_note' => $request->rejection_note,
        ]);

        // Kirim notifikasi ke staf
        NotificationHelper::backdateReviewed($backdateRequest->load('user', 'reviewer'));

        return back()->with('info', 'Permintaan backdating telah ditolak.');
    }

    /**
     * Guard: hanya leader (dept sama), c_level, atau super_admin.
     */
    private function authorizeReview(BackdateRequest $req): void
    {
        $user = auth()->user();

        if (!in_array($user->role, ['leader', 'c_level', 'super_admin'])) {
            abort(403);
        }

        if ($user->role === 'leader') {
            $req->load('user');
            if ($req->user->department !== $user->department) {
                abort(403, 'Anda hanya bisa mereview permintaan dari departemen Anda.');
            }
        }
    }
}
