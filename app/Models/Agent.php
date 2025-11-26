<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Agent extends Authenticatable implements FilamentUser
{

    protected $fillable = [
        'agent_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'national_id',
        'status',
        'type',
        'address',
        'city',
        'region',
        'date_of_birth',
        'hire_date',
        'bank_account',
        'bank_name',
        'commission_rate',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'commission_rate' => 'decimal:2',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agent) {
            if (empty($agent->agent_code)) {
                $agent->agent_code = 'AGT' . strtoupper(Str::random(6));
            }
        });
    }

    /**
     * Get the agent's name (required by Filament)
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the agent's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the agent's display name with code
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name . ' (' . $this->agent_code . ')';
    }

    /**
     * Check if agent is active
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get agent's teams
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'agent_teams')
            ->withPivot(['joined_at', 'left_at', 'role', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get agent's active teams
     */
    public function activeTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('is_active', true);
    }

    /**
     * Get agent's commissions
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get agent's performance records
     */
    public function performances(): HasMany
    {
        return $this->hasMany(AgentPerformance::class);
    }

    /**
     * Get agent's referral links
     */
    public function referralLinks(): HasMany
    {
        return $this->hasMany(AgentReferralLink::class);
    }

    /**
     * Get agent's applications (through referral tracking)
     */
    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationState::class, 'agent_id');
    }

    /**
     * Get agent's campaigns
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'agent_campaign')
            ->withPivot([
                'applications_count',
                'sales_total',
                'conversions_count',
                'individual_metrics'
            ])
            ->withTimestamps();
    }

    /**
     * Get current month performance
     */
    public function getCurrentMonthPerformance()
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->performances()
            ->where('period_start', '>=', $startOfMonth)
            ->where('period_end', '<=', $endOfMonth)
            ->first();
    }

    /**
     * Calculate total commission earned
     */
    public function getTotalCommissionEarnedAttribute(): float
    {
        return $this->commissions()
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Calculate pending commission
     */
    public function getPendingCommissionAttribute(): float
    {
        return $this->commissions()
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');
    }

    /**
     * Get total applications submitted
     */
    public function getTotalApplicationsAttribute(): int
    {
        return $this->applications()->count();
    }

    /**
     * Get approved applications count
     */
    public function getApprovedApplicationsAttribute(): int
    {
        return $this->applications()
            ->whereJsonContains('metadata->admin_status', 'approved')
            ->count();
    }

    /**
     * Calculate conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        $total = $this->total_applications;
        $approved = $this->approved_applications;

        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    }

    /**
     * Generate referral link
     */
    public function generateReferralLink(string $campaignName = null): AgentReferralLink
    {
        $code = 'ref_' . $this->agent_code . '_' . Str::random(8);
        $url = url('/apply?ref=' . $code);

        return $this->referralLinks()->create([
            'code' => $code,
            'url' => $url,
            'campaign_name' => $campaignName,
            'is_active' => true,
        ]);
    }

    /**
     * Scope for active agents
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for agents by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for agents in specific region
     */
    public function scopeInRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Search agents by name, code, or email
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('agent_code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Get top performing agents
     */
    public static function getTopPerformers(int $limit = 10)
    {
        return static::active()
            ->withCount(['applications', 'commissions'])
            ->orderBy('applications_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Determine if the agent can access the Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'agent') {
            return $this->status === 'active';
        }

        return false;
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    /**
     * Get the password for the agent
     */
    public function getAuthPassword()
    {
        return $this->agent_code; // Using agent_code as password for simple auth
    }

    /**
     * Get the column name for the "remember me" token
     */
    public function getRememberTokenName()
    {
        return null; // Agents don't use remember tokens
    }

    /**
     * Get the name attribute for Filament
     */
    public function getFilamentName(): string
    {
        return $this->name ?: 'Agent ' . $this->agent_code;
    }
}
