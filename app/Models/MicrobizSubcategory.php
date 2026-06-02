<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicrobizSubcategory extends Model
{
    protected $fillable = [
        'microbiz_category_id',
        'supplier_id',
        'name',
        'description',
        'image_url',
        'gender_category',
    ];

    /**
     * Get the parent category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MicrobizCategory::class, 'microbiz_category_id');
    }

    /**
     * Get the supplier for this business subcategory.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the items belonging to this subcategory.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MicrobizItem::class);
    }

    /**
     * Get the tier packages for this subcategory.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(MicrobizPackage::class);
    }

    /**
     * Human-readable label for the audience tag.
     */
    public function getGenderCategoryLabelAttribute(): string
    {
        return match ($this->gender_category) {
            'fcc' => 'Female Centric (FCC)',
            'mcc' => 'Male Centric (MCC)',
            default => 'General',
        };
    }

    /**
     * Scope: filter by audience tag (fcc / mcc).
     */
    public function scopeGender($query, ?string $value)
    {
        return $query->where('gender_category', $value);
    }

    /**
     * Total number of items.
     */
    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Total number of tiers.
     */
    public function getTierCountAttribute(): int
    {
        return $this->packages()->count();
    }
}
