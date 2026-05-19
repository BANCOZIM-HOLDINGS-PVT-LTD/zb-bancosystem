<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBusiness extends Model
{
    protected $fillable = ['school_category_id', 'name', 'description', 'image_url'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SchoolCategory::class, 'school_category_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(SchoolPackage::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SchoolItem::class);
    }
}
