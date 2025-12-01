<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSeries extends Model
{
    protected $fillable = [
        'product_sub_category_id',
        'name',
        'description',
        'image_url',
    ];

    /**
     * Get the subcategory this series belongs to
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubCategory::class, 'product_sub_category_id');
    }

    /**
     * Get the products in this series
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
