<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah flag is_management ke tabel users.
 *
 * Solusi efisien agar Bu Ika (leader) dan Fanny (staff) tetap punya
 * dashboard sesuai role masing-masing, namun juga bisa akses fitur Export.
 *
 * Akses export: c_level ATAU is_management = true
 * Keputusan: Rapat 17 Mei 2026
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_management')->default(false)->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_management');
        });
    }
};
