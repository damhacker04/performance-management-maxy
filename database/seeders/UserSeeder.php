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
            // C-Level
            [
                'name'       => 'Ko Isaac',
                'email'      => 'isaac.maxy.academy@gmail.com',
                'role'       => 'c_level',
                'department' => null,
            ],
            // Leaders
            ['name' => 'Bu Ika',           'email' => 'ika.maxy.academy@gmail.com',          'role' => 'leader', 'department' => 'sales'],
            ['name' => 'Leader Marketing', 'email' => 'marketing.maxy.academy@gmail.com',     'role' => 'leader', 'department' => 'marketing'],
            ['name' => 'Leader Product',   'email' => 'product.maxy.academy@gmail.com',       'role' => 'leader', 'department' => 'product_it'],
            ['name' => 'Leader Ops',       'email' => 'operational.maxy.academy@gmail.com',   'role' => 'leader', 'department' => 'operational'],
            // Staff
            ['name' => 'Fanny',            'email' => 'fanny.maxy.academy@gmail.com',         'role' => 'staff',  'department' => 'operational'],
            ['name' => 'Staff Sales 1',    'email' => 'sales1.maxy.academy@gmail.com',         'role' => 'staff',  'department' => 'sales'],
            ['name' => 'Staff Sales 2',    'email' => 'sales2.maxy.academy@gmail.com',         'role' => 'staff',  'department' => 'sales'],
            ['name' => 'Staff Marketing',  'email' => 'marketing1.maxy.academy@gmail.com',    'role' => 'staff',  'department' => 'marketing'],
            ['name' => 'Staff Product',    'email' => 'product1.maxy.academy@gmail.com',      'role' => 'staff',  'department' => 'product_it'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'       => $data['name'],
                    'password'   => Hash::make('maxy2026'),
                    'role'       => $data['role'],
                    'department' => $data['department'],
                ]
            );
        }
    }
}
