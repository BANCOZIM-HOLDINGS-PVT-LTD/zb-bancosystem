<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'national_id',
        'phone',
        'phone_verified',
        'phone_verified_at',
        'otp_code',
        'otp_expires_at',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Generate and store OTP code for phone verification
     */
    public function generateOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10), // OTP expires in 10 minutes
        ]);

        return $otp;
    }

    /**
     * Verify the OTP code
     */
    public function verifyOtp(string $otp): bool
    {
        if ($this->otp_code === $otp && $this->otp_expires_at && $this->otp_expires_at->isFuture()) {
            $this->update([
                'phone_verified' => true,
                'phone_verified_at' => now(),
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Determine if the user can access the Filament admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access if user is marked as admin
        if ($this->is_admin) {
            return true;
        }

        // Allow access if user has an admin email domain
        if ($this->email && str_ends_with($this->email, '@bancosystem.fly.dev')) {
            return true;
        }

        return false;
    }
}
