<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageProduct extends Model
{
    protected $fillable = [
        'microbiz_package_id',
        'product_id',
        'quantity',
        'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the package this item belongs to.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(MicrobizPackage::class, 'microbiz_package_id');
    }

    /**
     * Get the inventory product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the total cost for this line item.
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->unit_cost ?? 0) * $this->quantity;
    }
}
