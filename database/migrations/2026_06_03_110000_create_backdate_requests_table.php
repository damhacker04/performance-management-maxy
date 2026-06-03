<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel untuk permintaan izin backdating laporan harian.
 *
 * Staf bisa mengajukan permintaan untuk mengisi laporan hari sebelumnya
 * (maks. 3 hari ke belakang). Leader/C-Level/Super Admin perlu menyetujui
 * sebelum staf bisa mengisi laporan untuk tanggal tersebut.
 *
 * Setelah disetujui, token approval berlaku selama 24 jam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backdate_requests', function (Blueprint $table) {
            $table->id();

            // Staf yang mengajukan
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Tanggal laporan yang diminta (maks. 3 hari ke belakang)
            $table->date('requested_date');

            // Alasan pengajuan (wajib)
            $table->text('reason');

            // Status permintaan
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            // Leader/admin yang mereview
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamp('reviewed_at')->nullable();

            // Catatan penolakan (opsional, hanya jika ditolak)
            $table->text('rejection_note')->nullable();

            // Token approval yang dikirim ke staf (uuid unik)
            // Berlaku 24 jam setelah disetujui
            $table->string('approval_token')->nullable()->unique();

            // Kapan token expired
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamps();

            // Index untuk query cepat
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'requested_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backdate_requests');
    }
};
