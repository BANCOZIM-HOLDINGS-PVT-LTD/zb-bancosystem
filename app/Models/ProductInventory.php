<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductInventory extends Model
{
    protected $fillable = [
        'product_id',
        'stock_quantity',
        'reserved_quantity',
        'minimum_stock_level',
        'maximum_stock_level',
        'reorder_point',
        'reorder_quantity',
        'cost_price',
        'selling_price',
        'markup_percentage',
        'is_active',
        'is_featured',
        'availability_status',
        'availability_date',
        'discontinue_date',
        'supplier_info',
        'warehouse_location',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'availability_date' => 'datetime',
        'discontinue_date' => 'datetime',
        'supplier_info' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the product this inventory belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get inventory movements for this product
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get available stock (total - reserved)
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    /**
     * Check if product is in stock
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->available_stock > 0;
    }

    /**
     * Check if stock is low (below reorder point)
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->available_stock <= $this->reorder_point;
    }

    /**
     * Check if stock is critical (below minimum level)
     */
    public function getIsCriticalStockAttribute(): bool
    {
        return $this->available_stock <= $this->minimum_stock_level;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->available_stock <= 0) {
            return 'out_of_stock';
        }

        if ($this->is_critical_stock) {
            return 'critical';
        }

        if ($this->is_low_stock) {
            return 'low';
        }

        return 'in_stock';
    }

    /**
     * Get stock status color for UI
     */
    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'danger',
            'critical' => 'danger',
            'low' => 'warning',
            'in_stock' => 'success',
            'inactive' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return round((($this->selling_price - $this->cost_price) / $this->cost_price) * 100, 2);
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $quantity): bool
    {
        if ($this->available_stock < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);

        // Log the movement
        $this->movements()->create([
            'type' => 'reservation',
            'quantity' => $quantity,
            'reference_type' => 'stock_reservation',
            'notes' => "Reserved {$quantity} units",
            'user_id' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(int $quantity): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }

        $this->decrement('reserved_quantity', $quantity);

        // Log the movement
        $this->movements()->create([
            'type' => 'release',
            'quantity' => $quantity,
            'reference_type' => 'stock_release',
            'notes' => "Released {$quantity} units from reservation",
            'user_id' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Add stock
     */
    public function addStock(int $quantity, string $reason = 'manual_adjustment'): void
    {
        $this->increment('stock_quantity', $quantity);

        // Log the movement
        $this->movements()->create([
            'type' => 'addition',
            'quantity' => $quantity,
            'reference_type' => $reason,
            'notes' => "Added {$quantity} units - {$reason}",
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Remove stock
     */
    public function removeStock(int $quantity, string $reason = 'manual_adjustment'): bool
    {
        if ($this->available_stock < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);

        // Log the movement
        $this->movements()->create([
            'type' => 'removal',
            'quantity' => -$quantity,
            'reference_type' => $reason,
            'notes' => "Removed {$quantity} units - {$reason}",
            'user_id' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('(stock_quantity - reserved_quantity) <= reorder_point');
    }

    /**
     * Scope for critical stock products
     */
    public function scopeCriticalStock($query)
    {
        return $query->whereRaw('(stock_quantity - reserved_quantity) <= minimum_stock_level');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(stock_quantity - reserved_quantity) <= 0');
    }

    /**
     * Scope for available products
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->whereRaw('(stock_quantity - reserved_quantity) > 0')
            ->where(function ($q) {
                $q->whereNull('availability_date')
                    ->orWhere('availability_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('discontinue_date')
                    ->orWhere('discontinue_date', '>', now());
            });
    }
}
