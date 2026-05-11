<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seed default users via migration (runs reliably on every deploy
 * because Railway nixpacks start command may skip db:seed).
 *
 * Uses raw DB::table with upsert-style logic so it's idempotent —
 * existing users get their password reset to the default test password,
 * missing users get created.
 */
return new class extends Migration
{
    public function up(): void
    {
        $users = [
            ['name' => 'Ko Isaac',          'email' => 'isaac.maxy.academy@gmail.com',       'role' => 'c_level', 'department' => null],
            ['name' => 'Bu Ika',            'email' => 'ika.maxy.academy@gmail.com',         'role' => 'leader',  'department' => 'sales'],
            ['name' => 'Leader Marketing',  'email' => 'marketing.maxy.academy@gmail.com',   'role' => 'leader',  'department' => 'marketing'],
            ['name' => 'Leader Product',    'email' => 'product.maxy.academy@gmail.com',     'role' => 'leader',  'department' => 'product_it'],
            ['name' => 'Leader Ops',        'email' => 'operational.maxy.academy@gmail.com', 'role' => 'leader',  'department' => 'operational'],
            ['name' => 'Fanny',             'email' => 'fanny.maxy.academy@gmail.com',       'role' => 'staff',   'department' => 'operational'],
            ['name' => 'Staff Sales 1',     'email' => 'sales1.maxy.academy@gmail.com',      'role' => 'staff',   'department' => 'sales'],
            ['name' => 'Staff Sales 2',     'email' => 'sales2.maxy.academy@gmail.com',      'role' => 'staff',   'department' => 'sales'],
            ['name' => 'Staff Marketing',   'email' => 'marketing1.maxy.academy@gmail.com',  'role' => 'staff',   'department' => 'marketing'],
            ['name' => 'Staff Product',     'email' => 'product1.maxy.academy@gmail.com',    'role' => 'staff',   'department' => 'product_it'],
        ];

        $hash = Hash::make('maxy2026');
        $now  = now();

        foreach ($users as $data) {
            $existing = DB::table('users')->where('email', $data['email'])->first();

            if ($existing) {
                DB::table('users')->where('email', $data['email'])->update([
                    'name'       => $data['name'],
                    'password'   => $hash,
                    'role'       => $data['role'],
                    'department' => $data['department'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->insert([
                    'name'              => $data['name'],
                    'email'             => $data['email'],
                    'password'          => $hash,
                    'role'              => $data['role'],
                    'department'        => $data['department'],
                    'email_verified_at' => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // intentionally empty — do not destroy users on rollback
    }
};
