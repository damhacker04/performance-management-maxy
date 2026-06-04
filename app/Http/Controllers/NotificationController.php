<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Halaman arsip semua notifikasi milik user yang sedang login.
     */
    public function index()
    {
        $notifications = AppNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        // Tandai semua sebagai sudah dibaca saat halaman arsip dibuka
        AppNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca (dipanggil via AJAX saat klik dropdown).
     */
    public function read(AppNotification $notification, Request $request)
    {
        // Hanya bisa tandai milik sendiri
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        // Dismiss dari card dashboard → kembali ke dashboard
        if ($request->query('dismiss')) {
            return redirect()->route('dashboard')
                ->with('info', 'Notifikasi ditutup.');
        }

        // Klik "Lihat laporan" → redirect ke laporan terkait
        if ($notification->related_id) {
            // Jika notifikasi berkaitan dengan backdate
            if (in_array($notification->type, [\App\Models\AppNotification::TYPE_BACKDATE_REQUESTED, \App\Models\AppNotification::TYPE_BACKDATE_REVIEWED])) {
                // Jika disetujui, arahkan ke form tambah tugas beserta tokennya
                if (str_contains($notification->title, 'Disetujui')) {
                    $backdateReq = \App\Models\BackdateRequest::find($notification->related_id);
                    if ($backdateReq && $backdateReq->approval_token) {
                        return redirect()->route('daily-tasks.create', ['backdate_token' => $backdateReq->approval_token]);
                    }
                    return redirect()->route('daily-tasks.create');
                }
                // Jika ditolak atau minta izin (untuk leader), arahkan ke halaman daftar permintaan
                return redirect()->route('backdate-requests.index');
            }

            // Default: ke detail laporan
            return redirect()->route('daily-tasks.show', $notification->related_id);
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function readAll()
    {
        AppNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
