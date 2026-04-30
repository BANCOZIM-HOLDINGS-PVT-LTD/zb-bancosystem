<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    protected $fillable = [
        'product_code',
        'product_sub_category_id',
        'product_series_id',
        'supplier_id',
        'name',
        'specification',
        'base_price',
        'image_url',
        'purchase_price',
        'markup_percentage',
        'transport_method',
        'ts_code',
        'tc_code',
    ];

    /**
     * Get the auto-generated description using specification and supplier.
     */
    public function getGeneratedDescriptionAttribute(): string
    {
        $desc = $this->specification ?? $this->name;
        
        // Add Supplier info if available
        if ($this->supplier) {
            $desc .= "\nSupplier: " . $this->supplier->name;
        }
        
        return $desc;
    }

    protected $casts = [
        'base_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
    ];

    /**
     * TS Cost: Transport from Source. Small Truck = $20, InDrive = $5.
     */
    public function getTsCostAttribute(): float
    {
        return match ($this->transport_method) {
            'small_truck' => 20.00,
            'indrive' => 5.00,
            default => 0.00,
        };
    }

    /**
     * TC Cost: Transport from Courier = 10% of product base price.
     */
    public function getTcCostAttribute(): float
    {
        if (!$this->transport_method) return 0.00;
        return round((float) $this->base_price * 0.10, 2);
    }

    /**
     * Selling price = base_price + markup + TS + TC
     * Optimized to respect purchase_price or base_price as selling price 
     * depending on which one is populated in the admin.
     */
    public function getSellingPriceAttribute(): float
    {
        // 1. If we have an explicit selling_price column value, use it
        if (isset($this->attributes['selling_price']) && (float) $this->attributes['selling_price'] > 0) {
            return (float) $this->attributes['selling_price'];
        }

        $base = (float) $this->base_price;
        $purchase = (float) $this->purchase_price;
        $markup = (float) $this->markup_percentage;

        // If base_price is non-zero and we have a markup, calculation is likely intended (InventoryManagementResource)
        if ($base > 0 && $markup > 0) {
            return round($base + ($base * $markup / 100) + $this->ts_cost + $this->tc_cost, 2);
        }

        // Fallback: use the larger of base_price or purchase_price as the selling price
        // This handles the swapped roles in different admin panels (InventoryManagementResource vs StoreProductResource)
        $price = max($base, $purchase);
        
        if ($price > 0) {
            return round($price + $this->ts_cost + $this->tc_cost, 2);
        }

        return 0.00;
    }

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
     * Get the supplier for this product
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to only show hire purchase products.
     */
    public function scopeHirePurchaseOnly(Builder $query): Builder
    {
        return $query->whereHas('subCategory.category', function ($q) {
            $q->where('type', 'hire_purchase');
        });
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
