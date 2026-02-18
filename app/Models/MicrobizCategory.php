<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicrobizCategory extends Model
{
    protected $fillable = [
        'name',
        'emoji',
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
}
