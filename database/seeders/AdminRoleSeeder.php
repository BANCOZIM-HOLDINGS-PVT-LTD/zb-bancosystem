<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = 'password123'; // Default password for all test admins

        $admins = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@microbiz.co.zw',
                'role' => 'ROLE_SUPER_ADMIN',
            ],
            [
                'name' => 'ZB Admin',
                'email' => 'zb@microbiz.co.zw',
                'role' => 'ROLE_ZB_ADMIN',
            ],
            [
                'name' => 'Accounting Admin',
                'email' => 'accounting@microbiz.co.zw',
                'role' => 'ROLE_ACCOUNTING',
            ],
            [
                'name' => 'HR Admin',
                'email' => 'hr@microbiz.co.zw',
                'role' => 'ROLE_HR',
            ],
            [
                'name' => 'Stores Admin',
                'email' => 'stores@microbiz.co.zw',
                'role' => 'ROLE_STORES',
            ],
            [
                'name' => 'Partner Admin',
                'email' => 'partner@microbiz.co.zw',
                'role' => 'ROLE_PARTNER',
            ],
        ];

        foreach ($admins as $admin) {
            $user = User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'role' => $admin['role'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );
            
            Log::info("Created/Updated admin: {$admin['role']} - {$admin['email']}");
        }
    }
}
