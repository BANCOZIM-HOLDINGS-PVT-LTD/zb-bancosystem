<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoosterBusiness extends Model
{
    protected $fillable = [
        'booster_category_id',
        'name',
        'description',
        'image_url',
    ];

    /**
     * Get the parent category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BoosterCategory::class, 'booster_category_id');
    }

    /**
     * Get the tiers for this business.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(BoosterTier::class);
    }
}
