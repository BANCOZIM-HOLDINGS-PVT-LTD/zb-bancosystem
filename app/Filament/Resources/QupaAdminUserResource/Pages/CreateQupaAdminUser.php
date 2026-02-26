<?php

namespace App\Filament\Resources\QupaAdminUserResource\Pages;

use App\Filament\Resources\QupaAdminUserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateQupaAdminUser extends CreateRecord
{
    protected static string $resource = QupaAdminUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate password
        $this->generatedPassword = Str::random(12);
        $data['password'] = Hash::make($this->generatedPassword);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Set the role using the secure method (bypasses guarded)
        $record->setRole(User::ROLE_QUPA_ADMIN);

        // Email credentials
        try {
            Mail::raw(
                "Hello {$record->name},\n\nYour Qupa Admin account has been created.\n\nEmail: {$record->email}\nPassword: {$this->generatedPassword}\n\nPlease log in at " . url('/zb-admin') . " to access the Qupa Admin Panel.\nYou will be able to set your own password after logging in.\n\nRegards,\nBancoSystem Admin",
                function ($message) use ($record) {
                    $message->to($record->email)
                        ->subject('Welcome to Qupa Admin — Your Login Credentials');
                }
            );

            Notification::make()
                ->title('Qupa Admin User Created')
                ->body("Password: {$this->generatedPassword}\n\nCredentials emailed to {$record->email}")
                ->success()
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('User Created (Email Failed)')
                ->body("Password: {$this->generatedPassword}\n\nCould not email credentials: {$e->getMessage()}")
                ->warning()
                ->persistent()
                ->send();
        }
    }

    private string $generatedPassword = '';
}
