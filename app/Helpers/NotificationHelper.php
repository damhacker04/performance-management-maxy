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
     * Kirim notifikasi ke LEADER: staff tidak mengumpulkan laporan hari ini.
     * Dipanggil secara manual atau dari scheduler.
     */
    public static function notSubmitted(User $staff, User $leader): void
    {
        AppNotification::create([
            'user_id'    => $leader->id,
            'type'       => AppNotification::TYPE_NOT_SUBMITTED,
            'title'      => $staff->name . ' Belum Kumpul Laporan',
            'body'       => $staff->name . ' belum mengumpulkan laporan tugas hari ini (' .
                            now()->isoFormat('D MMMM YYYY') . ').',
            'related_id' => null,
            'meta'       => [
                'staff_name'  => $staff->name,
                'staff_id'    => $staff->id,
                'date'        => today()->toDateString(),
                'department'  => $staff->department,
            ],
        ]);
    }
}
