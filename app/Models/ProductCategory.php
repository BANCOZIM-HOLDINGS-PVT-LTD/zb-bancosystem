<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'emoji',
        'type',
    ];

    /**
     * Get the subcategories for this category
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(ProductSubCategory::class);
    }

    /**
     * Get all products through subcategories
     */
    public function products()
    {
        return $this->hasManyThrough(Product::class, ProductSubCategory::class);
    }

    /**
     * Get the total number of products in this category
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get the price range for products in this category
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
