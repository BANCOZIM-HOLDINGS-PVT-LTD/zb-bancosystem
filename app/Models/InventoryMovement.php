<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_inventory_id',
        'type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'reference_type',
        'reference_id',
        'cost_per_unit',
        'total_cost',
        'reason',
        'notes',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Movement types
     */
    const TYPE_ADDITION = 'addition';
    const TYPE_REMOVAL = 'removal';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RELEASE = 'release';
    const TYPE_SALE = 'sale';
    const TYPE_RETURN = 'return';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_DAMAGE = 'damage';
    const TYPE_EXPIRED = 'expired';

    /**
     * Get the product inventory this movement belongs to
     */
    public function productInventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class);
    }

    /**
     * Get the user who made this movement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product through inventory
     */
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            ProductInventory::class,
            'id',
            'id',
            'product_inventory_id',
            'product_id'
        );
    }

    /**
     * Get movement type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ADDITION => 'Stock Addition',
            self::TYPE_REMOVAL => 'Stock Removal',
            self::TYPE_RESERVATION => 'Stock Reserved',
            self::TYPE_RELEASE => 'Reservation Released',
            self::TYPE_SALE => 'Sale',
            self::TYPE_RETURN => 'Return',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_DAMAGE => 'Damaged',
            self::TYPE_EXPIRED => 'Expired',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get movement type color for UI
     */
    public function getTypeColor(): string
    {
        return match ($this->type) {
            self::TYPE_ADDITION, self::TYPE_RETURN, self::TYPE_RELEASE => 'success',
            self::TYPE_REMOVAL, self::TYPE_SALE, self::TYPE_RESERVATION => 'primary',
            self::TYPE_DAMAGE, self::TYPE_EXPIRED => 'danger',
            self::TYPE_ADJUSTMENT, self::TYPE_TRANSFER => 'warning',
            default => 'gray',
        };
    }

    /**
     * Check if movement increases stock
     */
    public function getIncreasesStockAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_ADDITION,
            self::TYPE_RETURN,
            self::TYPE_RELEASE,
        ]);
    }

    /**
     * Check if movement decreases stock
     */
    public function getDecreasesStockAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_REMOVAL,
            self::TYPE_SALE,
            self::TYPE_RESERVATION,
            self::TYPE_DAMAGE,
            self::TYPE_EXPIRED,
        ]);
    }

    /**
     * Get formatted quantity with sign
     */
    public function getFormattedQuantityAttribute(): string
    {
        $sign = $this->increases_stock ? '+' : '-';
        return $sign . abs($this->quantity);
    }

    /**
     * Scope for specific movement types
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for movements that increase stock
     */
    public function scopeIncreasing($query)
    {
        return $query->whereIn('type', [
            self::TYPE_ADDITION,
            self::TYPE_RETURN,
            self::TYPE_RELEASE,
        ]);
    }

    /**
     * Scope for movements that decrease stock
     */
    public function scopeDecreasing($query)
    {
        return $query->whereIn('type', [
            self::TYPE_REMOVAL,
            self::TYPE_SALE,
            self::TYPE_RESERVATION,
            self::TYPE_DAMAGE,
            self::TYPE_EXPIRED,
        ]);
    }

    /**
     * Scope for movements by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for movements in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Boot method to automatically set previous and new quantities
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if ($movement->product_inventory_id) {
                $inventory = ProductInventory::find($movement->product_inventory_id);
                if ($inventory) {
                    $movement->previous_quantity = $inventory->stock_quantity;
                    
                    // Calculate new quantity based on movement type
                    if ($movement->increases_stock) {
                        $movement->new_quantity = $inventory->stock_quantity + abs($movement->quantity);
                    } else {
                        $movement->new_quantity = $inventory->stock_quantity - abs($movement->quantity);
                    }
                }
            }
        });
    }
}
