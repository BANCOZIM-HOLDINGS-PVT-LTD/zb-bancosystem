<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoosterTier extends Model
{
    protected $fillable = [
        'booster_business_id',
        'name',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the parent business.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(BoosterBusiness::class, 'booster_business_id');
    }
}
