<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            // URL bukti laporan (link ke Google Sheets, Drive, CRM, dll.)
            $table->string('proof_url')->nullable()->after('notes');

            // Path file yang diupload (image/PDF, disimpan di storage/app/public/proofs/)
            $table->string('proof_file')->nullable()->after('proof_url');
        });
    }

    public function down(): void
    {
        Schema::table('daily_task_entries', function (Blueprint $table) {
            $table->dropColumn(['proof_url', 'proof_file']);
        });
    }
};
