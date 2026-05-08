<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoosterTierItem extends Model
{
    protected $fillable = [
        'booster_package_id',
        'booster_item_id',
        'quantity',
        'is_delivered',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_delivered' => 'boolean',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(BoosterPackage::class, 'booster_package_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(BoosterItem::class, 'booster_item_id');
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * ($this->item->selling_price ?? 0);
    }
}
