<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchaseOrder) {
            if (empty($purchaseOrder->po_number)) {
                $purchaseOrder->po_number = self::generatePoNumber();
            }

            // Auto-calculate total amount
            $purchaseOrder->total_amount = $purchaseOrder->subtotal +
                                          $purchaseOrder->tax_amount +
                                          $purchaseOrder->shipping_cost;
        });

        static::updating(function ($purchaseOrder) {
            // Recalculate total amount on update
            $purchaseOrder->total_amount = $purchaseOrder->subtotal +
                                          $purchaseOrder->tax_amount +
                                          $purchaseOrder->shipping_cost;
        });
    }

    /**
     * Generate unique PO number
     */
    public static function generatePoNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get the last PO number for this month
        $lastPo = self::where('po_number', 'like', "PO-{$year}{$month}-%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = intval(substr($lastPo->po_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PO-{$year}{$month}-{$newNumber}";
    }

    /**
     * Get the supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get items with product details
     */
    public function itemsWithProducts(): HasMany
    {
        return $this->items()->with('product');
    }

    /**
     * Check if all items are received
     */
    public function isFullyReceived(): bool
    {
        return $this->items()
            ->where('status', '!=', 'received')
            ->count() === 0;
    }

    /**
     * Check if partially received
     */
    public function isPartiallyReceived(): bool
    {
        $receivedCount = $this->items()
            ->where('status', 'received')
            ->count();

        $totalCount = $this->items()->count();

        return $receivedCount > 0 && $receivedCount < $totalCount;
    }

    /**
     * Calculate received percentage
     */
    public function getReceivedPercentageAttribute(): float
    {
        $totalQuantity = $this->items()->sum('quantity_ordered');
        $receivedQuantity = $this->items()->sum('quantity_received');

        if ($totalQuantity === 0) {
            return 0;
        }

        return round(($receivedQuantity / $totalQuantity) * 100, 2);
    }

    /**
     * Approve the purchase order
     */
    public function approve(string $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark as ordered
     */
    public function markAsOrdered(): void
    {
        $this->update([
            'status' => 'ordered',
            'order_date' => now(),
        ]);
    }

    /**
     * Mark as received
     */
    public function markAsReceived(): void
    {
        $this->update([
            'status' => 'received',
            'actual_delivery_date' => now(),
        ]);
    }

    /**
     * Cancel the purchase order
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);

        // Cancel all items
        $this->items()->update(['status' => 'cancelled']);
    }

    /**
     * Add item to purchase order
     */
    public function addItem(array $itemData): PurchaseOrderItem
    {
        $item = $this->items()->create($itemData);

        // Recalculate subtotal
        $this->recalculateTotals();

        return $item;
    }

    /**
     * Recalculate totals
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');

        $this->update([
            'subtotal' => $subtotal,
        ]);
    }

    /**
     * Create from application products
     */
    public static function createFromApplications(array $applicationIds): self
    {
        $applications = ApplicationState::whereIn('id', $applicationIds)
            ->with('products')
            ->get();

        $productQuantities = [];

        foreach ($applications as $application) {
            $products = $application->formData['products'] ?? [];
            foreach ($products as $product) {
                $productId = $product['id'] ?? null;
                if ($productId) {
                    if (! isset($productQuantities[$productId])) {
                        $productQuantities[$productId] = [
                            'quantity' => 0,
                            'applications' => [],
                        ];
                    }
                    $productQuantities[$productId]['quantity'] += $product['quantity'] ?? 1;
                    $productQuantities[$productId]['applications'][] = $application->id;
                }
            }
        }

        // Create purchase order
        $po = self::create([
            'status' => 'draft',
            'notes' => 'Auto-generated from applications: '.implode(', ', $applicationIds),
        ]);

        // Add items
        foreach ($productQuantities as $productId => $data) {
            $product = Product::find($productId);
            if ($product) {
                $po->items()->create([
                    'product_id' => $productId,
                    'quantity_ordered' => $data['quantity'],
                    'unit_price' => $product->cost_price ?? $product->unit_price,
                    'total_price' => ($product->cost_price ?? $product->unit_price) * $data['quantity'],
                    'application_id' => $data['applications'][0], // Link to first application
                ]);
            }
        }

        $po->recalculateTotals();

        return $po;
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved orders
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for orders awaiting delivery
     */
    public function scopeAwaitingDelivery($query)
    {
        return $query->whereIn('status', ['approved', 'ordered']);
    }
}
