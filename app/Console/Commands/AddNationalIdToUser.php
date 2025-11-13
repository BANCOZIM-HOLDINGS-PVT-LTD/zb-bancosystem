<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddNationalIdToUser extends Command
{
    protected $signature = 'user:add-national-id {email} {national_id} {phone}';
    protected $description = 'Add National ID and phone number to an existing user';

    public function handle()
    {
        $email = $this->argument('email');
        $nationalId = $this->argument('national_id');
        $phone = $this->argument('phone');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $user->national_id = $nationalId;
        $user->phone = $phone;
        $user->phone_verified = false;
        $user->save();

        $this->info("User updated successfully!");
        $this->info("Name: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("National ID: {$user->national_id}");
        $this->info("Phone: {$user->phone}");

        return 0;
    }
}