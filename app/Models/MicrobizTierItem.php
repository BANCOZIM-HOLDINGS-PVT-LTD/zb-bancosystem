<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrobizTierItem extends Model
{
    protected $table = 'microbiz_tier_items';

    protected $fillable = [
        'microbiz_package_id',
        'microbiz_item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the package/tier this belongs to.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(MicrobizPackage::class, 'microbiz_package_id');
    }

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(MicrobizItem::class, 'microbiz_item_id');
    }
}
