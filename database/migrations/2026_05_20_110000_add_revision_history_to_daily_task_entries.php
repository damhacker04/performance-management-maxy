<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            // Menyimpan histori semua catatan revisi sebagai JSON array
            // Format: [{"note":"...", "by":"Bu Ika", "by_id":2, "at":"2026-05-20 11:00"}]
            $table->json('revision_history')->nullable()->after('rejection_note');
        });
    }

    public function down(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            $table->dropColumn('revision_history');
        });
    }
};
