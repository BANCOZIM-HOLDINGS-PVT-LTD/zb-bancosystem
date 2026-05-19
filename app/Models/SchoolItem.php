<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolItem extends Model
{
    protected $fillable = [
        'school_business_id', 'item_code', 'name', 'description',
        'unit', 'unit_cost', 'markup_percentage', 'image_url', 'is_active',
    ];

    protected $casts = [
        'unit_cost'          => 'decimal:2',
        'markup_percentage'  => 'decimal:2',
        'is_active'          => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(SchoolBusiness::class, 'school_business_id');
    }

    public function getSellingPriceAttribute(): float
    {
        $cost = (float) $this->unit_cost;
        return round($cost + ($cost * (float) $this->markup_percentage / 100), 2);
    }
}
