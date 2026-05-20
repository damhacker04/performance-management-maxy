<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            // Status verifikasi: pending = belum direview, approved = disetujui,
            // revision = dikembalikan untuk direvisi staff, rejected = ditolak permanen
            $table->enum('verification_status', ['pending', 'approved', 'revision', 'rejected'])
                  ->default('pending')
                  ->after('notes');

            // Leader yang memverifikasi (approve/revision/reject)
            $table->foreignId('verified_by')
                  ->nullable()
                  ->after('verification_status')
                  ->constrained('users')
                  ->nullOnDelete();

            // Kapan diverifikasi (approve)
            $table->timestamp('verified_at')->nullable()->after('verified_by');

            // Catatan dari leader saat revision atau reject
            $table->text('rejection_note')->nullable()->after('verified_at');

            // Kapan dikembalikan (revision/reject) — untuk hitung revision window
            $table->timestamp('reviewed_at')->nullable()->after('rejection_note');
        });
    }

    public function down(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'verification_status',
                'verified_by',
                'verified_at',
                'rejection_note',
                'reviewed_at',
            ]);
        });
    }
};
