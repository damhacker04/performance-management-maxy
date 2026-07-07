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

            [
                'name'          => 'Admin HR',
                'email'         => 'adminhr.maxy.academy@gmail.com',
                'department'    => null,
                'division'      => 'Human Capital - Admin',
                'role'          => 'super_admin',
                'is_management' => true,
            ],

            [
                'name'          => 'Ko Isaac',
                'email'         => 'isaac.maxy.academy@gmail.com',
                'department'    => null,
                'division'      => 'CEO',
                'role'          => 'c_level',
                'is_management' => true,
            ],

            [
                'name'          => 'Ika',
                'email'         => 'ika.maxy.academy@gmail.com',
                'department'    => 'Operational',
                'division'      => 'Head of Operational',
                'role'          => 'leader',
                'is_management' => true,
            ],

            [
                'name'          => 'Leader Operational',
                'email'         => 'leader.operational@maxy.academy',
                'department'    => 'Operational',
                'division'      => 'Head of Operational',
                'role'          => 'leader',
                'is_management' => true,
            ],

            [
                'name'          => 'Rangga',
                'email'         => 'rangga.maxy.academy@gmail.com',
                'department'    => 'Sales',
                'division'      => 'Head of Sales',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Maya',
                'email'         => 'maya.maxy.academy@gmail.com',
                'department'    => 'Marketing',
                'division'      => 'Head of Marketing',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Yoga',
                'email'         => 'yoga.maxy.academy@gmail.com',
                'department'    => 'Product / IT',
                'division'      => 'Head of Product/IT',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Hesti',
                'email'         => 'hesti.maxy.academy@gmail.com',
                'department'    => 'HR',
                'division'      => 'Head of HR',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Fajar',
                'email'         => 'fajar.maxy.academy@gmail.com',
                'department'    => 'Finance',
                'division'      => 'Head of Finance',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Gita',
                'email'         => 'gita.maxy.academy@gmail.com',
                'department'    => 'General Affairs',
                'division'      => 'Head of GA',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Caca',
                'email'         => 'caca.maxy.academy@gmail.com',
                'department'    => 'Creative',
                'division'      => 'Head of Creative',
                'role'          => 'leader',
                'is_management' => true,
            ],
            [
                'name'          => 'Sasa',
                'email'         => 'sasa.maxy.academy@gmail.com',
                'department'    => 'Customer Support',
                'division'      => 'Head of Customer Support',
                'role'          => 'leader',
                'is_management' => true,
            ],

            [
                'name'          => 'Alifia',
                'email'         => 'alifia.maxy.academy@gmail.com',
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

            // ── CEO Office (intern pembantu CEO — dept datar, tanpa leader) ──
            // Ditugaskan & di-track langsung oleh Ko Isaac / management.
            [
                'name'          => 'Elroy Pemerena Karosekali',
                'email'         => 'elroy.maxy.academy@gmail.com',
                'department'    => 'ceo_office',
                'division'      => 'CEO Office Intern',
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Kartika Saraswati',
                'email'         => 'kartika.maxy.academy@gmail.com',
                'department'    => 'ceo_office',
                'division'      => 'CEO Office Intern',
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Ghufron Bagaskara',
                'email'         => 'ghufron.maxy.academy@gmail.com',
                'department'    => 'ceo_office',
                'division'      => 'CEO Office Intern',
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Olivia Abigail Silitonga',
                'email'         => 'olivia.maxy.academy@gmail.com',
                'department'    => 'ceo_office',
                'division'      => 'CEO Office Intern',
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

            // ── Sales (impor Data Karyawan 2026) — belum ada leader ──────────
            [
                'name'          => 'Sydney Yuanita',
                'email'         => 'yua.maxy.academy@gmail.com',
                'department'    => 'sales',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Ruky Dwi Jayanti',
                'email'         => 'rukydwi.maxy.academy@gmail.com',
                'department'    => 'sales',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Wempi Darwis Napitupulu',
                'email'         => 'wempi.maxy.academy@gmail.com',
                'department'    => 'sales',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Maria Felicia Widyawati',
                'email'         => 'feli.maxy.academy@gmail.com',
                'department'    => 'sales',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Mirfan Afandi',
                'email'         => 'mirfan.maxy.academy@gmail.com',
                'department'    => 'sales',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],

            // ── Marketing (impor Data Karyawan 2026) — leader: Joseph (di atas) ─
            [
                'name'          => 'Vincent Susanto',
                'email'         => 'vincent.maxy.academy@gmail.com',
                'department'    => 'marketing',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Muhammad Khalid Ar Rasyid',
                'email'         => 'khalid.maxy.academy@gmail.com',
                'department'    => 'marketing',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Kania Rachmayanti Satiyawira',
                'email'         => 'kania.maxy.academy@gmail.com',
                'department'    => 'marketing',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Kemal Bregas Dewantoro',
                'email'         => 'kemal.maxy.academy@gmail.com',
                'department'    => 'marketing',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Lingga Canastain Aulia',
                'email'         => 'lingga.maxy.academy@gmail.com',
                'department'    => 'marketing',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],

            // ── Univ Partnership (impor Data Karyawan 2026) — leader: Jessica (di atas) ─
            [
                'name'          => 'Andira Putri Farahdila',
                'email'         => 'andira.maxy.academy@gmail.com',
                'department'    => 'univ_partnership',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],

            // ── Peserta laporan harian yang belum ada di roster HR ───────────
            // Email kantor belum diketahui → pakai pola sementara (bisa dikoreksi).
            [
                'name'          => 'Matthew',
                'email'         => 'matthew.maxy.academy@gmail.com',
                'department'    => 'sales',
                'division'      => null,
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Jessica Maria P. Waworuntu',
                'email'         => 'jessicamaria.maxy.academy@gmail.com',
                'department'    => 'ceo_office',
                'division'      => 'CEO Office Intern',
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Bryan Austin Lontoh',
                'email'         => 'bryan.maxy.academy@gmail.com',
                'department'    => 'ceo_office',
                'division'      => 'CEO Office Intern',
                'role'          => 'staff',
                'is_management' => false,
            ],
        ];

        // Departemen yang login MANUAL (email+password) diaktifkan untuk testing.
        // Karyawan Gmail-nya diberi password default 'maxy2026' selain tetap bisa
        // login via Google. Tambahkan dept ke sini saat mau diuji manual.
        $manualLoginDepts = ['product_it', 'ceo_office', 'operational', 'sales', 'marketing', 'univ_partnership'];

        // Akun spesifik (per-email) yang juga diberi login manual 'maxy2026'.
        // Dipakai untuk C-Level (department null → tak tercakup $manualLoginDepts)
        // dan manager/leader asli agar bisa diuji login di staging & main.
        $manualLoginEmails = [
            'isaac.munandar@gmail.com',       // CEO
            'tang.torodeveloper@gmail.com',   // CTO
            'hello.linkdataku.id@gmail.com',  // Manager Ops
            'jessica.maxy.academy@gmail.com', // Manager Univ Partnership
            'joseph.maxy.academy@gmail.com',  // SPV Marcom
        ];

        $isProduction = app()->isProduction();

        $demoEmails = ['leader.operational@maxy.academy'];

        foreach ($users as $data) {

            $isDemo = str_contains($data['name'], 'Dummy')
                || str_contains($data['name'], 'Testing')
                || in_array($data['email'], $demoEmails, true);
            if ($isProduction && $isDemo) {
                continue;
            }

            $deptKey = null;
            if (! empty($data['department'])) {
                $deptKey = array_search($data['department'], User::DEPARTMENTS, true)
                    ?: strtolower($data['department']);
            }

            $user = User::firstOrNew(['email' => $data['email']]);
            $user->fill([
                'name'          => $data['name'],
                'role'          => $data['role'],
                'department'    => $deptKey,
                'division'      => $data['division'],
                'is_management' => $data['is_management'] ?? false,
            ]);

            if (! $user->exists) {

                $user->password = Hash::make('maxy2026');
            }

            $user->save();
        }
    }
}
