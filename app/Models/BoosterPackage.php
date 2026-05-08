<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BoosterPackage extends Model
{
    protected $fillable = [
        'booster_business_id',
        'tier',
        'name',
        'slug',
        'description',
        'image_url',
        'is_active',
        'price',
        'deposit',
        'monthly_installment',
        'loan_term',
        'interest_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'deposit' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'loan_term' => 'integer',
        'interest_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (BoosterPackage $package) {
            if (!$package->slug) {
                $package->slug = Str::slug($package->name . '-' . $package->tier);
            }

            if (!$package->price) {
                $package->price = $package->total_items_cost;
            }

            if (!$package->monthly_installment && $package->loan_term > 0) {
                $principal = max(0, (float) $package->price - (float) $package->deposit);
                $interest = $principal * ((float) $package->interest_rate / 100);
                $package->monthly_installment = round(($principal + $interest) / $package->loan_term, 2);
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BoosterBusiness::class, 'booster_business_id');
    }

    public function tierItems(): HasMany
    {
        return $this->hasMany(BoosterTierItem::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(BoosterItem::class, 'booster_tier_items')
            ->withPivot('quantity', 'is_delivered')
            ->withTimestamps();
    }

    public function getTotalItemsCostAttribute(): float
    {
        return $this->tierItems->sum(fn (BoosterTierItem $tierItem) => $tierItem->line_total);
    }
}
