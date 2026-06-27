<?php

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Aturan baru: C-Level hanya menargetkan Leader, tidak langsung ke staff.
 *
 * Migrasi data legacy: monthly target yang dibuat C-Level tapi di-assign ke
 * STAFF dipetakan ulang ke LEADER departemen target tersebut. Bila departemen
 * tidak punya leader aktif → assigned_to di-set null (jadi target umum dept).
 *
 * Catatan: pada `migrate:fresh` migrasi ini berjalan sebelum seeder (belum ada
 * user) sehingga menjadi no-op — data baru sudah otomatis mengikuti aturan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cLevelIds = User::where('role', 'c_level')->pluck('id');
        if ($cLevelIds->isEmpty()) {
            return;
        }

        $staffIds = User::where('role', 'staff')->pluck('id');
        if ($staffIds->isEmpty()) {
            return;
        }

        $targets = MonthlyTarget::whereIn('user_id', $cLevelIds)
            ->whereIn('assigned_to', $staffIds)
            ->get();

        // Cache leader per departemen agar tidak query berulang.
        $leaderByDept = [];

        foreach ($targets as $mt) {
            $dept = $mt->department;

            if (! array_key_exists($dept, $leaderByDept)) {
                $leaderByDept[$dept] = User::where('role', 'leader')
                    ->where('department', $dept)
                    ->where('is_active', true)
                    ->orderByDesc('is_management')
                    ->orderBy('id')
                    ->value('id');
            }

            $mt->assigned_to = $leaderByDept[$dept]; // null bila dept tanpa leader
            $mt->save();
        }
    }

    public function down(): void
    {
        // Remap historis tidak dapat dikembalikan secara akurat — no-op.
    }
};
