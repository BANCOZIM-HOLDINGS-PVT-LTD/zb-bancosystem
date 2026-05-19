<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolTierItem extends Model
{
    protected $fillable = ['school_package_id', 'school_item_id', 'quantity', 'is_delivered'];

    protected $casts = [
        'quantity'     => 'integer',
        'is_delivered' => 'boolean',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(SchoolPackage::class, 'school_package_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SchoolItem::class, 'school_item_id');
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * ($this->item->selling_price ?? 0);
    }
}
