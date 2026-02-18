<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MicrobizPackage extends Model
{
    protected $fillable = [
        'microbiz_subcategory_id',
        'tier',
        'name',
        'description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * The business subcategory this package/tier belongs to.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(MicrobizSubcategory::class, 'microbiz_subcategory_id');
    }

    /**
     * Get the tier line items.
     */
    public function tierItems(): HasMany
    {
        return $this->hasMany(MicrobizTierItem::class);
    }

    /**
     * Get the MicroBiz items included in this package.
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(MicrobizItem::class, 'microbiz_tier_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get the tier label for display.
     */
    public function getTierLabelAttribute(): string
    {
        return match ($this->tier) {
            'lite' => 'Lite Package',
            'standard' => 'Standard Package',
            'full_house' => 'Full House Package',
            'gold' => 'Gold Package',
            default => ucfirst(str_replace('_', ' ', $this->tier)) . ' Package',
        };
    }

    /**
     * Get the total cost of all items in this package.
     */
    public function getTotalItemsCostAttribute(): float
    {
        return $this->tierItems->sum(function ($tierItem) {
            return ($tierItem->item->unit_cost ?? 0) * $tierItem->quantity;
        });
    }

    /**
     * Scope: filter by tier
     */
    public function scopeTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }
}
