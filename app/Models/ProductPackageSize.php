<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPackageSize extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'multiplier',
        'custom_price',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'custom_price' => 'decimal:2',
    ];

    /**
     * Get the product this package size belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the calculated price for this package size
     */
    public function getCalculatedPriceAttribute(): float
    {
        if ($this->custom_price) {
            return $this->custom_price;
        }

        return $this->product->base_price * $this->multiplier;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->calculated_price, 2);
    }

    /**
     * Get the display name with price
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' - ' . $this->formatted_price;
    }
}
