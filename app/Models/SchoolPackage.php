<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SchoolPackage extends Model
{
    protected $fillable = [
        'school_business_id', 'tier', 'name', 'slug', 'description',
        'image_url', 'is_active', 'price', 'deposit',
        'monthly_installment', 'loan_term', 'interest_rate',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'price'               => 'decimal:2',
        'deposit'             => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'loan_term'           => 'integer',
        'interest_rate'       => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (SchoolPackage $package) {
            if (!$package->slug) {
                $package->slug = Str::slug($package->name . '-' . $package->tier . '-' . uniqid());
            }
        });
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->tier) {
            'essential'    => 'Essential',
            'intermediate' => 'Intermediate',
            'advanced'     => 'Advanced',
            'premium'      => 'Premium',
            default        => ucfirst($this->tier),
        };
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(SchoolBusiness::class, 'school_business_id');
    }

    public function tierItems(): HasMany
    {
        return $this->hasMany(SchoolTierItem::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(SchoolItem::class, 'school_tier_items')
            ->withPivot('quantity', 'is_delivered')
            ->withTimestamps();
    }

    public function getTotalItemsCostAttribute(): float
    {
        return $this->tierItems->sum(fn (SchoolTierItem $t) => $t->line_total);
    }
}
