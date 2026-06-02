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
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'          => $data['name'],
                    'password'      => Hash::make('maxy2026'),
                    'role'          => $data['role'],
                    'department'    => strtolower($data['department'] ?? ''), // Gunakan format lowercase untuk logic
                    'division'      => $data['division'],
                    'is_management' => $data['is_management'] ?? false,
                ]
            );
        }
    }
}
