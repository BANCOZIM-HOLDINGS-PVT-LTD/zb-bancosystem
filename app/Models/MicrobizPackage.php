<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MicrobizPackage extends Model
{
    protected $fillable = [
        'product_id',
        'tier',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * The MicroBiz business this package belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the package product line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PackageProduct::class);
    }

    /**
     * Get the inventory products included in this package.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'package_products')
            ->withPivot(['quantity', 'unit_cost'])
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
            default => ucfirst($this->tier),
        };
    }

    /**
     * Get the total cost of all items in this package.
     */
    public function getTotalItemsCostAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return ($item->unit_cost ?? 0) * $item->quantity;
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
