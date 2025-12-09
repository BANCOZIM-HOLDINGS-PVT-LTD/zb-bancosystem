<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PortalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'ZB Admin',
                'email' => 'zbadmin@bancosystem.fly.dev',
                'role' => User::ROLE_ZB_ADMIN,
            ],
            [
                'name' => 'Accounting',
                'email' => 'accounts@bancosystem.fly.dev',
                'role' => User::ROLE_ACCOUNTING,
            ],
            [
                'name' => 'Stores',
                'email' => 'stores@bancosystem.fly.dev',
                'role' => User::ROLE_STORES,
            ],
            [
                'name' => 'HR',
                'email' => 'hr@bancosystem.fly.dev',
                'role' => User::ROLE_HR,
            ],
            [
                'name' => 'Partners',
                'email' => 'partners@bancosystem.fly.dev',
                'role' => User::ROLE_PARTNER,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => $userData['role'],
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
