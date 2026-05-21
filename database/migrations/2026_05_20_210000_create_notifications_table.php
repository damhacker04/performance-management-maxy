<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Penerima notifikasi
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Tipe notifikasi:
            // revision_requested = leader minta staff revisi
            // revision_submitted = staff sudah merevisi (notif ke leader)
            // auto_rejected      = auto-reject karena timeout (notif ke leader)
            // not_submitted      = staff tidak kumpul laporan hari ini (notif ke leader)
            $table->string('type');

            // Judul & isi notifikasi
            $table->string('title');
            $table->text('body');

            // ID laporan terkait (jika ada)
            $table->unsignedBigInteger('related_id')->nullable();

            // NULL = belum dibaca, ada nilai = sudah dibaca
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
