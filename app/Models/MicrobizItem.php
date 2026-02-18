<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MicrobizItem extends Model
{
    protected $fillable = [
        'microbiz_subcategory_id',
        'item_code',
        'name',
        'unit_cost',
        'unit',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->item_code)) {
                $item->item_code = self::generateItemCode();
            }
        });
    }

    /**
     * Generate a unique item code: MB-XXXX
     */
    public static function generateItemCode(): string
    {
        $last = self::orderBy('id', 'desc')->first();
        $nextId = $last ? $last->id + 1 : 1;

        return 'MB-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the subcategory this item belongs to.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(MicrobizSubcategory::class, 'microbiz_subcategory_id');
    }

    /**
     * Get the packages/tiers this item is included in.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(MicrobizPackage::class, 'microbiz_tier_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
