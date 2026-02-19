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
        'transport_method',
        'courier',
        'ts_code',
        'tc_code',
    ];

    /**
     * Get the auto-generated description listing all items in the package.
     */
    public function getGeneratedDescriptionAttribute(): string
    {
        $items = $this->items;
        $description = "This " . ($this->tier_label ?? 'package') . " involves the following:\n";
        
        foreach ($items as $item) {
            $qty = $item->pivot->quantity ?? 1;
            $description .= "-" . ($item->product_code ? "[{$item->product_code}] " : "") . "{$item->name} (x{$qty})\n";
        }

        return $description;
    }

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
            ->withPivot('quantity', 'is_delivered')
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
     * TS Cost: Transport from Source
     * Small Truck = $20, InDrive = $5, None = $0
     */
    public function getTsCostAttribute(): float
    {
        return match ($this->transport_method) {
            'small_truck' => 20.00,
            'indrive' => 5.00,
            default => 0.00,
        };
    }

    /**
     * TC Cost: Transport from Courier = 10% of total cost of delivered items.
     */
    public function getTcCostAttribute(): float
    {
        $deliveredTotal = $this->tierItems()
            ->where('is_delivered', true)
            ->get()
            ->sum(function ($tierItem) {
                return ($tierItem->item->unit_cost ?? 0) * $tierItem->quantity;
            });

        return round($deliveredTotal * 0.10, 2);
    }

    /**
     * Total transport cost = TS + TC
     */
    public function getTotalTransportCostAttribute(): float
    {
        return $this->ts_cost + $this->tc_cost;
    }

    /**
     * Get the total cost of all items in this package (before transport).
     */
    public function getTotalItemsCostAttribute(): float
    {
        return $this->tierItems->sum(function ($tierItem) {
            return ($tierItem->item->unit_cost ?? 0) * $tierItem->quantity;
        });
    }

    /**
     * Grand total = items cost + transport cost
     */
    public function getGrandTotalAttribute(): float
    {
        return $this->total_items_cost + $this->total_transport_cost;
    }

    /**
     * Scope: filter by tier
     */
    public function scopeTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }
}
