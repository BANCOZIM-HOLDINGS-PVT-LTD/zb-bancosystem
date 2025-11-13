<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentReferralLink extends Model
{
    protected $fillable = [
        'agent_id',
        'code',
        'url',
        'campaign_name',
        'is_active',
        'click_count',
        'conversion_count',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Check if link is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if link is usable
     */
    public function getIsUsableAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        return $this->click_count > 0 
            ? round(($this->conversion_count / $this->click_count) * 100, 2) 
            : 0;
    }

    /**
     * Record a click
     */
    public function recordClick(): void
    {
        $this->increment('click_count');
        
        $metadata = $this->metadata ?? [];
        $metadata['last_clicked_at'] = now()->toISOString();
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Record a conversion
     */
    public function recordConversion(): void
    {
        $this->increment('conversion_count');
        
        $metadata = $this->metadata ?? [];
        $metadata['last_conversion_at'] = now()->toISOString();
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Scope for active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for non-expired links
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for usable links
     */
    public function scopeUsable($query)
    {
        return $query->active()->notExpired();
    }
}
