<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class InitialUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // Management / C-Level
            [
                'name' => 'Ika', 
                'email' => 'ika.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'Head of Operational',
                'role' => 'leader', // Diubah menjadi Leader sesuai hierarki Manager of Operational
                'is_management' => true
            ],
            // Staff
            [
                'name' => 'Alifia', 
                'email' => 'alifia.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'General Affair',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Brigitha', 
                'email' => 'brigithap.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'Corporate Legal',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Indah', 
                'email' => 'indah.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'Finance',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Dwi Isma', 
                'email' => 'dwiisma.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'PA of Manager Ops',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Anisa', 
                'email' => 'anisasukmawati.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'Talent Placement',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Eka', 
                'email' => 'eka.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'Talent Placement',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Fanny', 
                'email' => 'fanny.maxy.academy@gmail.com', 
                'department' => 'Operational', 
                'division' => 'Human Capital',
                'role' => 'staff', 
                'is_management' => true
            ],
            [
                'name' => 'Kaesar Adam', 
                'email' => 'kaesaradam.maxy.academy@gmail.com', // Tetap tanpa titik agar Google Auth berhasil
                'department' => 'Operational', 
                'division' => 'Operational Intern',
                'role' => 'staff', 
                'is_management' => false
            ],
            [
                'name' => 'Dafy', 
                'email' => 'dafy.maxy.academy@gmail.com', // Placeholder email
                'department' => 'Operational', 
                'division' => 'Office Boy',
                'role' => 'staff', 
                'is_management' => false
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
