<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSubCategory extends Model
{
    protected $fillable = [
        'product_category_id',
        'name',
    ];

    /**
     * Get the parent category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the products in this subcategory
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the total number of products in this subcategory
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get the price range for products in this subcategory
     */
    public function getPriceRangeAttribute(): array
    {
        $products = $this->products();
        
        return [
            'min' => $products->min('base_price') ?? 0,
            'max' => $products->max('base_price') ?? 0,
        ];
    }
}
