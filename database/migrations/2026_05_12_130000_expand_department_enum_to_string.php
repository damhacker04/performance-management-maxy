<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Expand department dari enum 5 nilai menjadi string fleksibel.
 *
 * Konteks (Rapat 12 Mei 2026 + analisis Google Form / Excel HR):
 * - Sistem awalnya hanya support 4-5 dept; data riil Maxy ada 9 dept aktif.
 * - Daripada enum yang kaku, gunakan string + validasi via User::DEPARTMENTS
 *   sebagai single source of truth di level aplikasi.
 *
 * Strategy:
 * - MySQL: ALTER COLUMN TYPE → VARCHAR (drop ENUM constraint).
 * - SQLite: ENUM disimpan sebagai TEXT, tidak perlu perubahan struktur.
 *   Tapi Laravel masih menyimpan info kolom — biarkan untuk SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Drop enum constraint, ganti dengan VARCHAR(50) nullable
            DB::statement("ALTER TABLE users MODIFY COLUMN department VARCHAR(50) NULL");
        }
        // SQLite: ENUM tersimpan sebagai TEXT, tidak ada constraint — skip.
        // PostgreSQL: tidak digunakan di project ini.
    }

    public function down(): void
    {
        // Tidak rollback ke enum sempit — risk data loss kalau sudah ada user
        // dengan department di luar 5 nilai original.
    }
};
