<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QupaAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates:
     * - 2 Qupa Management users (Luckson, Lorraine)
     * - 1 Loan Officer per branch (10 total)
     * - 1 Branch Manager per branch (10 total)
     */
    public function run(): void
    {
        // --- Qupa Management ---
        $qupaManagement = [
            ['name' => 'Luckson', 'email' => 'luckson@qupa.co.zw'],
            ['name' => 'Lorraine', 'email' => 'lorraine@qupa.co.zw'],
        ];

        foreach ($qupaManagement as $admin) {
            $password = Str::random(12);

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

            $this->command->info("Qupa Management: {$admin['name']} ({$admin['email']}) — Password: {$password}");
        }

        // --- Loan Officers & Branch Managers (1 of each per branch) ---
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Run BranchSeeder first.');
            return;
        }

        foreach ($branches as $branch) {
            $branchSlug = strtolower(str_replace(' ', '_', $branch->name));

            // Loan Officer
            $loPassword = Str::random(12);
            $loEmail = "lo.{$branchSlug}@qupa.co.zw";
            $loUser = User::updateOrCreate(
                ['email' => $loEmail],
                [
                    'name' => "{$branch->name} Loan Officer",
                    'password' => Hash::make($loPassword),
                    'designation' => User::DESIGNATION_LOAN_OFFICER,
                    'branch_id' => $branch->id,
                ]
            );
            $loUser->setRole(User::ROLE_QUPA_ADMIN);

            $this->command->info("Loan Officer [{$branch->name}]: {$loEmail} — Password: {$loPassword}");

            // Branch Manager
            $bmPassword = Str::random(12);
            $bmEmail = "bm.{$branchSlug}@qupa.co.zw";
            $bmUser = User::updateOrCreate(
                ['email' => $bmEmail],
                [
                    'name' => "{$branch->name} Branch Manager",
                    'password' => Hash::make($bmPassword),
                    'designation' => User::DESIGNATION_BRANCH_MANAGER,
                    'branch_id' => $branch->id,
                ]
            );
            $bmUser->setRole(User::ROLE_QUPA_ADMIN);

            $this->command->info("Branch Manager [{$branch->name}]: {$bmEmail} — Password: {$bmPassword}");
        }

        $this->command->info('Seeded Qupa Admin users: 2 Management + ' . $branches->count() . ' Loan Officers + ' . $branches->count() . ' Branch Managers');
    }
}
