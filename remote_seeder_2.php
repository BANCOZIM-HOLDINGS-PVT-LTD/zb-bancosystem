<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$out = "=== QUPA ADMIN CREDENTIALS ===\n";

$qupaManagement = [
    ['name' => 'Luckson', 'email' => 'luckson@qupa.co.zw'],
    ['name' => 'Lorraine', 'email' => 'lorraine@qupa.co.zw'],
];

foreach ($qupaManagement as $admin) {
    $password = Illuminate\Support\Str::random(12);
    $user = App\Models\User::updateOrCreate(
        ['email' => $admin['email']],
        [
            'name' => $admin['name'],
            'password' => Illuminate\Support\Facades\Hash::make($password),
            'designation' => App\Models\User::DESIGNATION_QUPA_MANAGEMENT,
            'branch_id' => null,
        ]
    );
    $user->setRole(App\Models\User::ROLE_QUPA_ADMIN);
    $out .= "MANAGEMENT: {$admin['name']} | {$admin['email']} | $password\n";
}

$branches = App\Models\Branch::all();
foreach ($branches as $branch) {
    $branchSlug = strtolower(str_replace(' ', '_', $branch->name));
    
    $loPassword = Illuminate\Support\Str::random(12);
    $loEmail = "lo.{$branchSlug}@qupa.co.zw";
    $loUser = App\Models\User::updateOrCreate(
        ['email' => $loEmail],
        [
            'name' => "{$branch->name} Loan Officer",
            'password' => Illuminate\Support\Facades\Hash::make($loPassword),
            'designation' => App\Models\User::DESIGNATION_LOAN_OFFICER,
            'branch_id' => $branch->id,
        ]
    );
    $loUser->setRole(App\Models\User::ROLE_QUPA_ADMIN);
    $out .= "LOan Officer [{->name}]: {$loEmail} | $loPassword\n";

    $bmPassword = Illuminate\Support\Str::random(12);
    $bmEmail = "bm.{$branchSlug}@qupa.co.zw";
    $bmUser = App\Models\User::updateOrCreate(
        ['email' => $bmEmail],
        [
            'name' => "{$branch->name} Branch Manager",
            'password' => Illuminate\Support\Facades\Hash::make($bmPassword),
            'designation' => App\Models\User::DESIGNATION_BRANCH_MANAGER,
            'branch_id' => $branch->id,
        ]
    );
    $bmUser->setRole(App\Models\User::ROLE_QUPA_ADMIN);
    $out .= "Branch Manager [{->name}]: {$bmEmail} | $bmPassword\n";
}

file_put_contents('public/temp_passwords.txt', $out);
