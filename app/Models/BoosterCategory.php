<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoosterCategory extends Model
{
    protected $fillable = [
        'name',
        'emoji',
    ];

    /**
     * Get the businesses for this category.
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(BoosterBusiness::class);
    }
}
