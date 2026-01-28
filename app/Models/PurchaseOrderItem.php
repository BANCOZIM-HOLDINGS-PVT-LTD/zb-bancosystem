<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'application_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'total_price',
        'status',
        'notes'
    ];
    
    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            // Calculate total price if not set
            if (empty($item->total_price)) {
                $item->total_price = $item->unit_price * $item->quantity_ordered;
            }
        });
        
        static::updating(function ($item) {
            // Update status based on quantities
            if ($item->quantity_received === 0) {
                $item->status = 'pending';
            } elseif ($item->quantity_received >= $item->quantity_ordered) {
                $item->status = 'received';
            } else {
                $item->status = 'partial';
            }
        });
    }
    
    /**
     * Get the purchase order
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get the application
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class, 'application_id');
    }
    
    /**
     * Get remaining quantity
     */
    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity_ordered - $this->quantity_received;
    }
    
    /**
     * Get fulfillment percentage
     */
    public function getFulfillmentPercentageAttribute(): float
    {
        if ($this->quantity_ordered === 0) {
            return 0;
        }
        
        return round(($this->quantity_received / $this->quantity_ordered) * 100, 2);
    }
    
    /**
     * Mark as received
     */
    public function markAsReceived(int $quantity = null): void
    {
        $quantity = $quantity ?? $this->quantity_ordered;
        
        $this->update([
            'quantity_received' => $quantity,
            'status' => $quantity >= $this->quantity_ordered ? 'received' : 'partial'
        ]);
        
        // Update inventory if needed
        if ($this->product) {
            // Create inventory movement
            InventoryMovement::create([
                'product_id' => $this->product_id,
                'type' => 'purchase',
                'quantity' => $quantity,
                'reference_type' => 'purchase_order',
                'reference_id' => $this->purchase_order_id,
                'notes' => 'Received from PO: ' . $this->purchaseOrder->po_number,
            ]);
            
            // Update product inventory
            $inventory = ProductInventory::firstOrCreate(
                ['product_id' => $this->product_id],
                ['stock_quantity' => 0, 'reserved_quantity' => 0]
            );

            // Use the addStock method which handles inventory movement logging
            $inventory->addStock($quantity, 'purchase_order_received');
        }
    }
    
    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }
    
    /**
     * Check if partially received
     */
    public function isPartiallyReceived(): bool
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_ordered;
    }
}