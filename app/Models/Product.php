<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'product_sub_category_id',
        'product_series_id',
        'name',
        'base_price',
        'image_url',
        'colors',
        'purchase_price', // Added
        'markup_percentage', // Added
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'colors' => 'array',
    ];

    /**
     * Get the subcategory this product belongs to
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubCategory::class, 'product_sub_category_id');
    }

    /**
     * Get the series this product belongs to
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(ProductSeries::class, 'product_series_id');
    }

    /**
     * Get the main category through subcategory
     */
    public function category()
    {
        return $this->hasOneThrough(
            ProductCategory::class,
            ProductSubCategory::class,
            'id',
            'id',
            'product_sub_category_id',
            'product_category_id'
        );
    }

    /**
     * Get the package sizes for this product
     */
    public function packageSizes(): HasMany
    {
        return $this->hasMany(ProductPackageSize::class);
    }

    /**
     * Get the inventory record for this product
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class);
    }

    /**
     * Get sales for this product
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get loan terms for this product
     */
    public function loanTerms(): HasMany
    {
        return $this->hasMany(LoanTerm::class);
    }

    /**
     * Get packages for this product
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ProductPackage::class);
    }

    /**
     * Get the default package size (first one)
     */
    public function getDefaultPackageSizeAttribute()
    {
        return $this->packageSizes()->first();
    }

    /**
     * Get the price range based on package sizes
     */
    public function getPriceRangeAttribute(): array
    {
        $packageSizes = $this->packageSizes;
        
        if ($packageSizes->isEmpty()) {
            return [
                'min' => $this->base_price,
                'max' => $this->base_price,
            ];
        }

        $prices = $packageSizes->map(function ($size) {
            return $size->custom_price ?? ($this->base_price * $size->multiplier);
        });

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
        ];
    }

    /**
     * Get formatted price range
     */
    public function getFormattedPriceRangeAttribute(): string
    {
        $range = $this->price_range;
        
        if ($range['min'] == $range['max']) {
            return '$' . number_format($range['min'], 2);
        }
        
        return '$' . number_format($range['min'], 2) . ' - $' . number_format($range['max'], 2);
    }

    /**
     * Get the full category path
     */
    public function getFullCategoryPathAttribute(): string
    {
        return $this->category->name . ' > ' . $this->subCategory->name;
    }

    /**
     * Scope to filter by category
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('subCategory', function ($q) use ($categoryId) {
            $q->where('product_category_id', $categoryId);
        });
    }

    /**
     * Scope to filter by subcategory
     */
    public function scopeInSubCategory($query, $subCategoryId)
    {
        return $query->where('product_sub_category_id', $subCategoryId);
    }

    /**
     * Scope to filter by price range
     */
    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('base_price', [$minPrice, $maxPrice]);
    }

    /**
     * Search products by name or category
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhereHas('subCategory', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orWhereHas('category', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
    }
}
