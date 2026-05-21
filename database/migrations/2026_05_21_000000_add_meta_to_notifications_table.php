<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Kolom JSON untuk menyimpan data diff catatan revisi
            // dan metadata lain yang diperlukan card dashboard
            $table->text('meta')->nullable()->after('related_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
