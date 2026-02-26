<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ZB_ADMIN = 'zb_admin';
    public const ROLE_ACCOUNTING = 'accounting';
    public const ROLE_HR = 'hr';
    public const ROLE_STORES = 'stores';
    public const ROLE_PARTNER = 'partner';
    public const ROLE_QUPA_ADMIN = 'qupa_admin';

    // Qupa Admin designations
    public const DESIGNATION_LOAN_OFFICER = 'loan_officer';
    public const DESIGNATION_BRANCH_MANAGER = 'branch_manager';
    public const DESIGNATION_VLC = 'vlc';
    public const DESIGNATION_QUPA_MANAGEMENT = 'qupa_management';


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
        'whatsapp_number',
        'phone_verified',
        'phone_verified_at',
        'otp_code',
        'otp_expires_at',
        'designation',
        'branch_id',
        // SECURITY: is_admin and role are NOT mass-assignable to prevent privilege escalation
        // Use setRole() and setAdmin() methods instead for controlled role assignment
    ];

    /**
     * Attributes that should NEVER be mass-assigned
     * This is a safety net - even if accidentally added to fillable
     *
     * @var array<string>
     */
    protected $guarded = [
        'is_admin',
        'role',
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
        // Allow access based on role and panel
        if ($panel->getId() === 'admin') {
            // SECURITY: Removed email domain backdoor - access only via explicit is_admin or role
            // If you need to grant admin access via email domain, use environment variable:
            // $allowedDomain = env('ADMIN_EMAIL_DOMAIN');
            // return $this->is_admin || $this->role === self::ROLE_SUPER_ADMIN || ($allowedDomain && $this->email && str_ends_with($this->email, $allowedDomain));
            return $this->is_admin || $this->role === self::ROLE_SUPER_ADMIN;
        }

        if ($panel->getId() === 'zb_admin') {
            return $this->role === self::ROLE_ZB_ADMIN || $this->role === self::ROLE_SUPER_ADMIN || $this->role === self::ROLE_QUPA_ADMIN;
        }

        if ($panel->getId() === 'accounting') {
            return $this->role === self::ROLE_ACCOUNTING || $this->role === self::ROLE_SUPER_ADMIN;
        }

        if ($panel->getId() === 'hr') {
            return $this->role === self::ROLE_HR || $this->role === self::ROLE_SUPER_ADMIN;
        }

        if ($panel->getId() === 'stores') {
            return $this->role === self::ROLE_STORES || $this->role === self::ROLE_SUPER_ADMIN;
        }

        if ($panel->getId() === 'partner') {
            return $this->role === self::ROLE_PARTNER || $this->role === self::ROLE_SUPER_ADMIN;
        }

        return false;
    }

    /**
     * Set the user's admin status (use this instead of mass assignment)
     *
     * @param bool $isAdmin
     * @return bool
     */
    public function setAdmin(bool $isAdmin): bool
    {
        $this->is_admin = $isAdmin;
        return $this->save();
    }

    /**
     * Set the user's role (use this instead of mass assignment)
     *
     * @param string $role One of the ROLE_* constants
     * @return bool
     */
    public function setRole(string $role): bool
    {
        $validRoles = [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ZB_ADMIN,
            self::ROLE_ACCOUNTING,
            self::ROLE_HR,
            self::ROLE_STORES,
            self::ROLE_PARTNER,
            self::ROLE_QUPA_ADMIN,
        ];

        if (!in_array($role, $validRoles)) {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        $this->role = $role;
        return $this->save();
    }

    // ===== Qupa Admin Relationships =====

    /**
     * Get the branch this user is assigned to (for Qupa Admin users)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the referral links for this user (for Qupa Admin users)
     */
    public function qupaReferralLinks(): HasMany
    {
        return $this->hasMany(QupaReferralLink::class);
    }

    /**
     * Get applications assigned to this Qupa Admin user
     */
    public function qupaApplications(): HasMany
    {
        return $this->hasMany(ApplicationState::class, 'qupa_admin_id');
    }

    // ===== Qupa Admin Designation Helpers =====

    public function isQupaAdmin(): bool
    {
        return $this->role === self::ROLE_QUPA_ADMIN;
    }

    public function isLoanOfficer(): bool
    {
        return $this->isQupaAdmin() && $this->designation === self::DESIGNATION_LOAN_OFFICER;
    }

    public function isBranchManager(): bool
    {
        return $this->isQupaAdmin() && $this->designation === self::DESIGNATION_BRANCH_MANAGER;
    }

    public function isVlc(): bool
    {
        return $this->isQupaAdmin() && $this->designation === self::DESIGNATION_VLC;
    }

    public function isQupaManagement(): bool
    {
        return $this->isQupaAdmin() && $this->designation === self::DESIGNATION_QUPA_MANAGEMENT;
    }

    /**
     * Get the human-readable designation label
     */
    public function getDesignationLabelAttribute(): string
    {
        return match ($this->designation) {
            self::DESIGNATION_LOAN_OFFICER => 'Loan Officer',
            self::DESIGNATION_BRANCH_MANAGER => 'Branch Manager',
            self::DESIGNATION_VLC => 'VLC',
            self::DESIGNATION_QUPA_MANAGEMENT => 'Qupa Management',
            default => $this->designation ?? '',
        };
    }

    /**
     * Generate a referral link for this Qupa Admin user
     */
    public function generateQupaReferralLink(): QupaReferralLink
    {
        return QupaReferralLink::generateForUser($this);
    }
}
