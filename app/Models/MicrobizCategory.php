<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class MicrobizCategory extends Model
{
    protected $fillable = [
        'name',
        'emoji',
        'domain',
    ];

    /**
     * Get the subcategories (businesses) for this category.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(MicrobizSubcategory::class);
    }

    /**
     * Total number of subcategories.
     */
    public function getSubcategoryCountAttribute(): int
    {
        return $this->subcategories()->count();
    }

    /**
     * Scope: only MicroBiz domain categories.
     */
    public function scopeMicrobiz(Builder $query): Builder
    {
        return $query->where('domain', 'microbiz');
    }

    /**
     * Scope: only Service domain categories.
     */
    public function scopeService(Builder $query): Builder
    {
        return $query->where('domain', 'service');
    }
}
