<?php

namespace App\Helpers;

use App\Models\AppNotification;
use App\Models\DailyTaskEntry;
use App\Models\User;

class NotificationHelper
{
    /**
     * Kirim notifikasi ke STAFF: leader minta revisi laporan.
     */
    public static function revisionRequested(DailyTaskEntry $dailyTask, User $leader): void
    {
        AppNotification::create([
            'user_id'    => $dailyTask->user_id,
            'type'       => AppNotification::TYPE_REVISION_REQUESTED,
            'title'      => 'Laporan Perlu Direvisi',
            'body'       => $leader->name . ' meminta kamu merevisi laporan "' .
                            \Str::limit($dailyTask->task_description, 50) . '".' .
                            ' Catatan: ' . $dailyTask->rejection_note .
                            ' Sisa waktu: 10 jam.',
            'related_id' => $dailyTask->id,
            'meta'       => [
                'leader_name'    => $leader->name,
                'task_date'      => $dailyTask->task_date,
                'rejection_note' => $dailyTask->rejection_note,
                'task_desc'      => \Str::limit($dailyTask->task_description, 60),
            ],
        ]);
    }

    /**
     * Kirim notifikasi ke LEADER: staff sudah menyelesaikan revisi.
     * Menyimpan diff catatan lama (dari leader) vs catatan baru (dari staff).
     */
    public static function revisionSubmitted(DailyTaskEntry $dailyTask, User $staff): void
    {
        // Ambil catatan terakhir dari leader (entry terakhir di revision_history)
        $revisionHistory = $dailyTask->revision_history ?? [];
        $lastLeaderNote  = '';
        if (!empty($revisionHistory)) {
            $lastEntry      = end($revisionHistory);
            $lastLeaderNote = $lastEntry['note'] ?? '';
        }

        // Temukan leader / c_level dari departemen yang sama
        $leaders = User::whereIn('role', ['leader', 'c_level'])
            ->where('department', $staff->department)
            ->get();

        foreach ($leaders as $leader) {
            AppNotification::create([
                'user_id'    => $leader->id,
                'type'       => AppNotification::TYPE_REVISION_SUBMITTED,
                'title'      => $staff->name . ' Sudah Merevisi Laporan',
                'body'       => $staff->name . ' telah menyelesaikan revisi laporan "' .
                                \Str::limit($dailyTask->task_description, 50) . '".' .
                                ' Silakan periksa dan verifikasi kembali.',
                'related_id' => $dailyTask->id,
                'meta'       => [
                    'staff_name'       => $staff->name,
                    'task_desc'        => \Str::limit($dailyTask->task_description, 60),
                    'task_date'        => $dailyTask->task_date,
                    // Diff catatan: permintaan leader vs jawaban staff
                    'leader_note'      => $lastLeaderNote,   // "segera selesaikan" (catatan revisi)
                    'staff_new_notes'  => $dailyTask->notes, // catatan baru staff setelah revisi
                ],
            ]);
        }
    }

    /**
     * Kirim notifikasi ke LEADER: laporan di-auto-reject karena timeout revisi.
     */
    public static function autoRejected(DailyTaskEntry $dailyTask): void
    {
        $staff   = $dailyTask->user;
        $leaders = User::whereIn('role', ['leader', 'c_level'])
            ->where('department', $staff->department ?? '')
            ->get();

        foreach ($leaders as $leader) {
            AppNotification::create([
                'user_id'    => $leader->id,
                'type'       => AppNotification::TYPE_AUTO_REJECTED,
                'title'      => 'Laporan Otomatis Ditolak',
                'body'       => $staff->name . ' tidak merevisi laporan "' .
                                \Str::limit($dailyTask->task_description, 50) . '"' .
                                ' dalam 10 jam. Laporan telah otomatis ditolak oleh sistem.',
                'related_id' => $dailyTask->id,
                'meta'       => [
                    'staff_name' => $staff->name,
                    'task_desc'  => \Str::limit($dailyTask->task_description, 60),
                    'task_date'  => $dailyTask->task_date,
                ],
            ]);
        }
    }

    /**
     * Kirim notifikasi ke LEADER: staf mengajukan permintaan backdating.
     */
    public static function backdateRequested(\App\Models\BackdateRequest $req): void
    {
        $staff = $req->user;

        // Kirim ke semua leader + c_level + super_admin di departemen yang sama
        $leaders = User::whereIn('role', ['leader', 'c_level', 'super_admin'])
            ->where(function ($q) use ($staff) {
                $q->where('department', $staff->department)
                  ->orWhereIn('role', ['c_level', 'super_admin']);
            })
            ->get()
            ->unique('id');

        foreach ($leaders as $leader) {
            AppNotification::create([
                'user_id'    => $leader->id,
                'type'       => AppNotification::TYPE_BACKDATE_REQUESTED,
                'title'      => $staff->name . ' Minta Izin Backdating',
                'body'       => $staff->name . ' mengajukan izin untuk mengisi laporan tanggal ' .
                                \Carbon\Carbon::parse($req->requested_date)->isoFormat('D MMMM YYYY') .
                                '. Alasan: ' . \Str::limit($req->reason, 80),
                'related_id' => $req->id,
                'meta'       => [
                    'staff_name'     => $staff->name,
                    'requested_date' => $req->requested_date->toDateString(),
                    'reason'         => $req->reason,
                ],
            ]);
        }
    }

    /**
     * Kirim notifikasi ke STAF: permintaan backdating sudah direview leader.
     */
    public static function backdateReviewed(\App\Models\BackdateRequest $req): void
    {
        $isApproved = $req->status === 'approved';

        AppNotification::create([
            'user_id'    => $req->user_id,
            'type'       => AppNotification::TYPE_BACKDATE_REVIEWED,
            'title'      => $isApproved
                ? '✅ Permintaan Backdating Disetujui'
                : '❌ Permintaan Backdating Ditolak',
            'body'       => $isApproved
                ? 'Kamu diizinkan mengisi laporan untuk tanggal ' .
                  \Carbon\Carbon::parse($req->requested_date)->isoFormat('D MMMM YYYY') .
                  '. Segera isi laporanmu (berlaku 24 jam).'
                : 'Permintaan backdating tanggal ' .
                  \Carbon\Carbon::parse($req->requested_date)->isoFormat('D MMMM YYYY') .
                  ' ditolak. Alasan: ' . ($req->rejection_note ?? '-'),
            'related_id' => $req->id,
            'meta'       => [
                'status'         => $req->status,
                'requested_date' => $req->requested_date->toDateString(),
                'reviewer_name'  => $req->reviewer?->name,
                'rejection_note' => $req->rejection_note,
                'approval_token' => $req->approval_token,
            ],
        ]);
    }
    /**
     * Kirim notifikasi ke STAF: Laporan disetujui leader.
     */
    public static function reportApproved(DailyTaskEntry $dailyTask, User $leader): void
    {
        AppNotification::create([
            'user_id'    => $dailyTask->user_id,
            'type'       => AppNotification::TYPE_REPORT_APPROVED,
            'title'      => '✅ Laporan Disetujui',
            'body'       => 'Laporan "' . \Str::limit($dailyTask->task_description, 50) . '" telah disetujui oleh ' . $leader->name . '.',
            'related_id' => $dailyTask->id,
            'meta'       => [
                'leader_name' => $leader->name,
                'task_desc'   => \Str::limit($dailyTask->task_description, 60),
                'task_date'   => $dailyTask->task_date,
            ],
        ]);
    }

    /**
     * Kirim notifikasi ke STAF: Laporan ditolak permanen.
     */
    public static function reportRejected(DailyTaskEntry $dailyTask, User $leader): void
    {
        AppNotification::create([
            'user_id'    => $dailyTask->user_id,
            'type'       => AppNotification::TYPE_REPORT_REJECTED,
            'title'      => '❌ Laporan Ditolak',
            'body'       => 'Laporan "' . \Str::limit($dailyTask->task_description, 50) . '" ditolak permanen oleh ' . $leader->name . '. Alasan: ' . \Str::limit($dailyTask->rejection_note, 80),
            'related_id' => $dailyTask->id,
            'meta'       => [
                'leader_name'    => $leader->name,
                'task_desc'      => \Str::limit($dailyTask->task_description, 60),
                'task_date'      => $dailyTask->task_date,
                'rejection_note' => $dailyTask->rejection_note,
            ],
        ]);
    }
}

