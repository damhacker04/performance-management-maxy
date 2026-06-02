<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan nilai 'super_admin' ke kolom role di tabel users.
 *
 * Strategy (sama dengan expand_department_enum_to_string):
 * - MySQL (Railway): ALTER COLUMN ke ENUM baru yang mencakup super_admin.
 * - SQLite (lokal): ENUM tersimpan sebagai TEXT dengan CHECK constraint.
 *   Kita patch writable_schema untuk mengubah constraint-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('staff', 'leader', 'c_level', 'super_admin') NOT NULL DEFAULT 'staff'");
        } elseif ($driver === 'sqlite') {
            // Patch SQLite writable_schema untuk update CHECK constraint
            DB::unprepared("PRAGMA writable_schema = ON");
            DB::unprepared("
                UPDATE sqlite_master
                SET sql = REPLACE(
                    sql,
                    \"'staff', 'leader', 'c_level'\",
                    \"'staff', 'leader', 'c_level', 'super_admin'\"
                )
                WHERE type = 'table' AND name = 'users'
            ");
            DB::unprepared("PRAGMA writable_schema = OFF");
            DB::unprepared("PRAGMA integrity_check");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE users SET role = 'staff' WHERE role = 'super_admin'");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('staff', 'leader', 'c_level') NOT NULL DEFAULT 'staff'");
        }
    }
};
