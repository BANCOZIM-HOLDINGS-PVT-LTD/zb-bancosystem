<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            ['name' => 'Harare Main', 'code' => 'HARARE_MAIN'],
            ['name' => 'Borrowdale', 'code' => 'BORROWDALE'],
            ['name' => 'Eastgate', 'code' => 'EASTGATE'],
            ['name' => 'Kwame Nkrumah', 'code' => 'KWAME_NKRUMAH'],
            ['name' => 'Bulawayo', 'code' => 'BULAWAYO'],
            ['name' => 'Gweru', 'code' => 'GWERU'],
            ['name' => 'Mutare', 'code' => 'MUTARE'],
            ['name' => 'Masvingo', 'code' => 'MASVINGO'],
            ['name' => 'Chinhoyi', 'code' => 'CHINHOYI'],
            ['name' => 'Marondera', 'code' => 'MARONDERA'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }

        $this->command->info('Seeded ' . count($branches) . ' branches.');
    }
}
