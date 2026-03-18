<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StableQupaAdminSeeder extends Seeder
{
    /**
     * Run the database seeds with stable passwords for testing/demo.
     */
    public function run(): void
    {
        $password = 'QupaAdmin2026!'; // Stable password

        // --- Qupa Management ---
        $qupaManagement = [
            ['name' => 'Luckson', 'email' => 'luckson@qupa.co.zw'],
            ['name' => 'Lorraine', 'email' => 'lorraine@qupa.co.zw'],
        ];

        foreach ($qupaManagement as $admin) {
            $user = User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($password),
                    'designation' => User::DESIGNATION_QUPA_MANAGEMENT,
                    'branch_id' => null,
                ]
            );
            $user->setRole(User::ROLE_QUPA_ADMIN);
            $this->command->info("Management: {$admin['email']}");
        }

        // --- Per Branch Users ---
        $branches = Branch::all();

        foreach ($branches as $branch) {
            $branchSlug = strtolower(str_replace(' ', '_', $branch->name));

            // Loan Officer
            $loEmail = "lo.{$branchSlug}@qupa.co.zw";
            $loUser = User::updateOrCreate(
                ['email' => $loEmail],
                [
                    'name' => "{$branch->name} Loan Officer",
                    'password' => Hash::make($password),
                    'designation' => User::DESIGNATION_LOAN_OFFICER,
                    'branch_id' => $branch->id,
                ]
            );
            $loUser->setRole(User::ROLE_QUPA_ADMIN);

            // Branch Manager
            $bmEmail = "bm.{$branchSlug}@qupa.co.zw";
            $bmUser = User::updateOrCreate(
                ['email' => $bmEmail],
                [
                    'name' => "{$branch->name} Branch Manager",
                    'password' => Hash::make($password),
                    'designation' => User::DESIGNATION_BRANCH_MANAGER,
                    'branch_id' => $branch->id,
                ]
            );
            $bmUser->setRole(User::ROLE_QUPA_ADMIN);
            
            $this->command->info("Branch [{$branch->name}]: bm.{$branchSlug}@qupa.co.zw & lo.{$branchSlug}@qupa.co.zw");
        }

        $this->command->info("All users seeded with password: {$password}");
    }
}
