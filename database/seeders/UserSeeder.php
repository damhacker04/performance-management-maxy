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
                'name'          => 'Alifia Aulia Putri',
                'email'         => 'alifia.maxy.academy@gmail.com',
                'department'    => 'Operational', 
                'division'      => 'General Affair',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Brigitha Prameswari',
                'email'         => 'brigithap.maxy.academy@gmail.com',
                'department'    => 'Operational', 
                'division'      => 'Corporate Legal',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Indah Surroiyah',
                'email'         => 'indah.maxy.academy@gmail.com',
                'department'    => 'Operational', 
                'division'      => 'Finance',
                'role'          => 'staff', 
                'is_management' => false,
            ],
            [
                'name'          => 'Dwi Ismawanti',
                'email'         => 'dwiisma.maxy.academy@gmail.com',
                'department'    => 'Operational',
                'division'      => 'PA of Manager Ops',
                'role'          => 'staff',
                'is_management' => false,
            ],
            [
                'name'          => 'Moh Kadafi',
                'email'         => 'mohkadafi4@gmail.com', // email kantor belum ada; pakai pribadi
                'department'    => 'operational',
                'division'      => null,
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

            // ── Leadership asli (C-Level & Manager/Leader per dept) ──────────
            [
                'name'          => 'Isaac Munandar',
                'email'         => 'isaac.munandar@gmail.com',
                'department'    => null,
                'division'      => 'CEO',
                'role'          => 'c_level',
                'is_management' => true,
            ],
            [
                'name'          => 'CTO',
                'email'         => 'tang.torodeveloper@gmail.com',
                'department'    => null,
                'division'      => 'CTO',
                'role'          => 'c_level',
                'is_management' => true,
            ],
            [
                'name'          => 'Manager Operational',
                'email'         => 'hello.linkdataku.id@gmail.com',
                'department'    => 'operational',
                'division'      => 'Manager Operational',
                'role'          => 'leader',
                'is_management' => false,
            ],
            [
                'name'          => 'Jessica Charisma Perdana',
                'email'         => 'jessica.maxy.academy@gmail.com',
                'department'    => 'univ_partnership',
                'division'      => 'Manager Univ Partnership',
                'role'          => 'leader',
                'is_management' => false,
            ],
            [
                'name'          => 'Joseph Christian Seraf Sasongko',
                'email'         => 'joseph.maxy.academy@gmail.com',
                'department'    => 'marketing',
                'division'      => 'SPV Marcom',
                'role'          => 'leader',
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
                // Beda orang dengan Sydney Yuanita (roster HR / yua.maxy.academy).
                'name'          => 'Sydney Rosalind',
                'email'         => 'sydneyrosalind.maxy.academy@gmail.com',
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

        foreach ($users as $data) {
            // Akun whitelist asli (Gmail) default TANPA password → login via Google
            // (dipaksa buat password saat pertama login). Dummy @maxy.academy, dept
            // di $manualLoginDepts, & email di $manualLoginEmails diberi password
            // 'maxy2026' untuk testing manual.
            $isGmail     = str_ends_with($data['email'], '@gmail.com');
            $manualLogin = ! $isGmail
                || in_array(strtolower($data['department'] ?? ''), $manualLoginDepts, true)
                || in_array($data['email'], $manualLoginEmails, true);

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
