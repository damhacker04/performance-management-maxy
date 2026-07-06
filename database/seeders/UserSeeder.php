<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Super Admin
            [
                'name'          => 'Admin HR',
                'email'         => 'adminhr.maxy.academy@gmail.com',
                'department'    => null,
                'division'      => 'Human Capital - Admin',
                'role'          => 'super_admin',
                'is_management' => true,
            ],
            // Management / C-Level
            [
                'name'          => 'Ko Isaac',
                'email'         => 'isaac.maxy.academy@gmail.com',
                'department'    => null,
                'division'      => 'CEO',
                'role'          => 'c_level',
                'is_management' => true,
            ],
            // Leader
            [
                'name'          => 'Ika', 
                'email'         => 'ika.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Head of Operational',
                'role'          => 'leader',
                'is_management' => true,
            ],
            // Staff
            [
                'name'          => 'Alifia', 
                'email'         => 'alifia.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'General Affair',
                'role'          => 'staff', 
                'is_management' => false,
            ],
      [
        'name'          => 'Ghufron',
        'email'         => 'ghufron.maxy.academy@gmail.com',
        'department'    => 'Operational',
        'division'      => 'General Affair',
        'role'          => 'staff',
        'is_management' => false,
      ],
            [
                'name'          => 'Brigitha', 
                'email'         => 'brigithap.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Corporate Legal',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Indah', 
                'email'         => 'indah.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Finance',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Dwi Isma', 
                'email'         => 'dwiisma.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'PA of Manager Ops',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Anisa', 
                'email'         => 'anisasukmawati.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Talent Placement',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Eka', 
                'email'         => 'eka.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Talent Placement',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Fanny', 
                'email'         => 'fanny.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Human Capital',
                'role'          => 'staff', 
                'is_management' => true,
            ],
            [
                'name'          => 'Kaesar Adam', 
                'email'         => 'kaesaradam.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Operational Intern',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Dafy', 
                'email'         => 'dafy.maxy.academy@gmail.com', 
                'department'    => 'Operational', 
                'division'      => 'Office Boy',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Leader Operational Dummy', 
                'email'         => 'leader.operational@maxy.academy', 
                'department'    => 'Operational', 
                'division'      => 'Operational',
                'role'          => 'leader', 
                'is_management' => true,
            ],
            [
                'name'          => 'C-Level Dummy', 
                'email'         => 'c_level@maxy.academy', 
                'department'    => null, 
                'division'      => 'Management',
                'role'          => 'c_level', 
                'is_management' => true,
            ],
            [
                'name'          => 'Staff Testing', 
                'email'         => 'staff.testing@maxy.academy', 
                'department'    => 'Operational', 
                'division'      => 'Operational',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Super Admin Dummy', 
                'email'         => 'superadmin@maxy.academy', 
                'department'    => null, 
                'division'      => 'Admin',
                'role'          => 'super_admin', 
                'is_management' => true,
            ],
            [
                'name'          => 'Staff Dummy',
                'email'         => 'staff@maxy.academy',
                'department'    => 'Operational',
                'division'      => 'Operational',
                'role'          => 'staff',
                'is_management' => false,
            ],

            // ── Product / IT (impor Data Karyawan 2026) ──────────────────────
            // Leader: Stefen Laksana. Sisanya staff. Email kantor Gmail → login via Google.
            [
                'name'          => 'Stefen Laksana',
                'email'         => 'stefen.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => 'Head of Product / IT',
                'role'          => 'leader',
                'is_management' => false,
            ],
            [
                'name'          => 'Nathanael Abellito Leo',
                'email'         => 'nathanleo.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Wahyudi',
                'email'         => 'wahyudi.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Nabila Melsyana',
                'email'         => 'nabila.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Yanuarin Salwa Afranita',
                'email'         => 'salwa.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Syabina Nur Pajriyanti',
                'email'         => 'ina.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Luvena Cornelia',
                'email'         => 'luve.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Johan Kevin Kenneth Hutagalung',
                'email'         => 'johan.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Rian Ahmad Sugita',
                'email'         => 'rian.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Gama Anom Multi Riyadi',
                'email'         => 'gamaanom.maxy.academy@gmail.com',
                'department'    => 'product_it',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
        ];

        // Departemen yang login MANUAL (email+password) diaktifkan untuk testing.
        // Karyawan Gmail-nya diberi password default 'maxy2026' selain tetap bisa
        // login via Google. Tambahkan dept ke sini saat mau diuji manual.
        $manualLoginDepts = ['product_it'];

        foreach ($users as $data) {
            // Akun whitelist asli (Gmail) default TANPA password → login via Google
            // (dipaksa buat password saat pertama login). Dummy @maxy.academy & dept
            // di $manualLoginDepts diberi password 'maxy2026' untuk testing manual.
            $isGmail     = str_ends_with($data['email'], '@gmail.com');
            $manualLogin = ! $isGmail || in_array($data['department'] ?? '', $manualLoginDepts, true);

            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'          => $data['name'],
                    'password'      => $manualLogin ? Hash::make('maxy2026') : null,
                    'role'          => $data['role'],
                    'department'    => strtolower($data['department'] ?? ''), // Gunakan format lowercase untuk logic
                    'division'      => $data['division'],
                    'is_management' => $data['is_management'] ?? false,
                ]
            );
        }
    }
}
