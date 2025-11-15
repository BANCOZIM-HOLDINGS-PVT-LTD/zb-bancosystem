<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@bancosystem.fly.dev'],
            [
                'name' => 'System Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'phone' => '+263771111111',
                'national_id' => '63-111111-A-11',
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@bancosystem.fly.dev');
        $this->command->info('Password: admin123');
    }
}

