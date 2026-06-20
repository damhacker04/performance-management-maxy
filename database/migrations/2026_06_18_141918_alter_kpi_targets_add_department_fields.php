<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            // Jadikan user_id nullable (data lama dipertahankan)
            $table->foreignId('user_id')->nullable()->change();

            // Tambah kolom departemen (primary identifier untuk KPI baru)
            $table->string('department')->nullable()->after('user_id');

            // Tambah metadata
            $table->foreignId('set_by')->nullable()->constrained('users')->onDelete('set null')->after('year');
            $table->boolean('is_active')->default(true)->after('set_by');
            $table->text('notes')->nullable()->after('is_active');
        });

        // Migrate data lama: isi department dari user.department (SQLite-compatible)
        DB::statement("
            UPDATE kpi_targets
            SET department = (
                SELECT department FROM users WHERE users.id = kpi_targets.user_id
            )
            WHERE department IS NULL AND user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropColumn(['department', 'set_by', 'is_active', 'notes']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
