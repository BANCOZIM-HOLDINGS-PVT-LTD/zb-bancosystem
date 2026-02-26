<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$output = "=== QUPA ADMIN CREDENTIALS ===" . PHP_EOL . PHP_EOL;

$mgmt = [
    ['name' => 'Luckson', 'email' => 'luckson@qupa.co.zw'],
    ['name' => 'Lorraine', 'email' => 'lorraine@qupa.co.zw'],
];

foreach ($mgmt as $admin) {
    $password = Str::random(12);
    $user = User::updateOrCreate(
        ['email' => $admin['email']],
        ['name' => $admin['name'], 'password' => Hash::make($password), 'designation' => User::DESIGNATION_QUPA_MANAGEMENT, 'branch_id' => null]
    );
    $user->setRole(User::ROLE_QUPA_ADMIN);
    $output .= "MANAGEMENT: {$admin['name']} | {$admin['email']} | Password: {$password}" . PHP_EOL;
}

$output .= PHP_EOL;
$branches = Branch::all();
foreach ($branches as $branch) {
    $slug = strtolower(str_replace(' ', '_', $branch->name));
    $loP = Str::random(12); $bmP = Str::random(12);
    $loE = "lo.{$slug}@qupa.co.zw"; $bmE = "bm.{$slug}@qupa.co.zw";
    $lo = User::updateOrCreate(['email' => $loE], ['name' => "{$branch->name} Loan Officer", 'password' => Hash::make($loP), 'designation' => User::DESIGNATION_LOAN_OFFICER, 'branch_id' => $branch->id]);
    $lo->setRole(User::ROLE_QUPA_ADMIN);
    $bm = User::updateOrCreate(['email' => $bmE], ['name' => "{$branch->name} Branch Manager", 'password' => Hash::make($bmP), 'designation' => User::DESIGNATION_BRANCH_MANAGER, 'branch_id' => $branch->id]);
    $bm->setRole(User::ROLE_QUPA_ADMIN);
    $output .= "[{$branch->name}] LO: {$loE} | Password: {$loP}" . PHP_EOL;
    $output .= "[{$branch->name}] BM: {$bmE} | Password: {$bmP}" . PHP_EOL;
}

$output .= PHP_EOL . "Total: 2 Management + {$branches->count()} LO + {$branches->count()} BM" . PHP_EOL;
file_put_contents('qupa_credentials_final.php', "<?php\n// Generated: " . date('Y-m-d H:i:s') . "\n// " . str_replace(PHP_EOL, "\n// ", trim($output)) . "\n");
echo $output;
