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
            ['name' => 'Bu Ika',           'email' => 'ika.maxy.academy@gmail.com',          'role' => 'leader', 'department' => 'sales',        'is_management' => true],
            ['name' => 'Leader Marketing', 'email' => 'marketing.maxy.academy@gmail.com',     'role' => 'leader', 'department' => 'marketing',    'is_management' => false],
            ['name' => 'Leader Product',   'email' => 'product.maxy.academy@gmail.com',       'role' => 'leader', 'department' => 'product_it',   'is_management' => false],
            ['name' => 'Leader Ops',       'email' => 'operational.maxy.academy@gmail.com',   'role' => 'leader', 'department' => 'operational',  'is_management' => false],
            // Staff
            ['name' => 'Fanny',            'email' => 'fanny.maxy.academy@gmail.com',         'role' => 'staff',  'department' => 'operational',  'is_management' => true],
            ['name' => 'Staff Sales 1',    'email' => 'sales1.maxy.academy@gmail.com',         'role' => 'staff',  'department' => 'sales',        'is_management' => false],
            ['name' => 'Staff Sales 2',    'email' => 'sales2.maxy.academy@gmail.com',         'role' => 'staff',  'department' => 'sales',        'is_management' => false],
            ['name' => 'Staff Marketing',  'email' => 'marketing1.maxy.academy@gmail.com',    'role' => 'staff',  'department' => 'marketing',    'is_management' => false],
            ['name' => 'Staff Product',    'email' => 'product1.maxy.academy@gmail.com',      'role' => 'staff',  'department' => 'product_it',   'is_management' => false],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'          => $data['name'],
                    'password'      => Hash::make('maxy2026'),
                    'role'          => $data['role'],
                    'department'    => $data['department'],
                    'is_management' => $data['is_management'] ?? false,
                ]
            );
        }
    }
}
