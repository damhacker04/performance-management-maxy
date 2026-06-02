<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Buat akun Super Admin (HR) untuk panel manajemen pengguna & target.
     * Email: adminhr.maxy.academy@gmail.com
     * Login via Google OAuth — tidak menggunakan password.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'adminhr.maxy.academy@gmail.com'],
            [
                'name'          => 'Admin HR',
                'role'          => 'super_admin',
                'department'    => null,
                'division'      => 'Human Capital - Admin',
                'is_management' => true,
                'password'      => null, // Login via Google, tidak pakai password
            ]
        );
    }
}
