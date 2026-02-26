<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QupaReferralLink extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'url',
        'is_active',
        'click_count',
        'conversion_count',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the Qupa Admin user who owns this referral link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a click on this link
     */
    public function recordClick(): void
    {
        $this->increment('click_count');
        $metadata = $this->metadata ?? [];
        $metadata['last_clicked_at'] = now()->toISOString();
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Record a conversion (application submitted)
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
     * Generate a new referral link for a Qupa Admin user
     */
    public static function generateForUser(User $user): self
    {
        $code = 'qupa_' . strtolower(Str::random(10));
        $url = url('/apply?qref=' . $code);

        return static::create([
            'user_id' => $user->id,
            'code' => $code,
            'url' => $url,
            'is_active' => true,
        ]);
    }
}
