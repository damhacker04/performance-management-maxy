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
