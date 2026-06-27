<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu Daily Task Entry hanya boleh punya SATU evaluasi AI.
 * Tanpa unique ini, EvaluateDailyTaskJob (yang punya pola check-then-create)
 * bisa membuat baris ganda saat ter-dispatch/retry secara konkuren.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Bersihkan duplikat lama: sisakan baris dengan id terbesar per entry.
        $duplicateIds = DB::table('ai_evaluations')
            ->select('id')
            ->whereNotIn('id', function ($q) {
                $q->from('ai_evaluations')
                    ->selectRaw('MAX(id)')
                    ->groupBy('daily_task_entry_id');
            })
            ->pluck('id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('ai_evaluations')->whereIn('id', $duplicateIds)->delete();
        }

        // 2. Tambahkan unique constraint.
        Schema::table('ai_evaluations', function (Blueprint $table) {
            $table->unique('daily_task_entry_id', 'ai_evaluations_entry_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ai_evaluations', function (Blueprint $table) {
            $table->dropUnique('ai_evaluations_entry_unique');
        });
    }
};
