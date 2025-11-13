<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Team extends Model
{

    protected $fillable = [
        'name',
        'code',
        'description',
        'team_leader_id',
        'status',
        'team_commission_rate',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'team_commission_rate' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->code)) {
                $team->code = 'TEAM' . strtoupper(Str::random(4));
            }
        });
    }

    /**
     * Get the team leader
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'team_leader_id');
    }

    /**
     * Get team members
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_teams')
            ->withPivot(['joined_at', 'left_at', 'role', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get active team members
     */
    public function activeAgents(): BelongsToMany
    {
        return $this->agents()->wherePivot('is_active', true);
    }

    /**
     * Get team supervisors
     */
    public function supervisors(): BelongsToMany
    {
        return $this->activeAgents()->wherePivot('role', 'supervisor');
    }

    /**
     * Check if team is active
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get total team members count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->activeAgents()->count();
    }

    /**
     * Get team performance metrics
     */
    public function getPerformanceMetricsAttribute(): array
    {
        $agents = $this->activeAgents;
        
        return [
            'total_applications' => $agents->sum('total_applications'),
            'approved_applications' => $agents->sum('approved_applications'),
            'total_commission' => $agents->sum('total_commission_earned'),
            'pending_commission' => $agents->sum('pending_commission'),
            'average_conversion_rate' => $agents->avg('conversion_rate'),
        ];
    }

    /**
     * Scope for active teams
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Search teams by name or code
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }
}
