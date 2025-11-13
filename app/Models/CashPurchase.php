<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashPurchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_number',
        'purchase_type',
        // Product
        'product_id',
        'product_name',
        'cash_price',
        'loan_price',
        'category',
        // Customer
        'national_id',
        'full_name',
        'phone',
        'email',
        // Delivery
        'delivery_type',
        'depot',
        'depot_name',
        'delivery_address',
        'city',
        'region',
        'delivery_fee',
        // Payment
        'payment_method',
        'amount_paid',
        'transaction_id',
        'payment_status',
        // Status
        'status',
        'swift_tracking_number',
        'status_history',
        // Timestamps
        'paid_at',
        'dispatched_at',
        'delivered_at',
    ];

    protected $casts = [
        'cash_price' => 'decimal:2',
        'loan_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'status_history' => 'array',
        'paid_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Boot the model and generate purchase number on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cashPurchase) {
            if (empty($cashPurchase->purchase_number)) {
                $cashPurchase->purchase_number = self::generatePurchaseNumber();
            }
        });
    }

    /**
     * Generate unique purchase number in format: CP-YYYY-XXXX
     */
    public static function generatePurchaseNumber(): string
    {
        $year = now()->year;
        $prefix = "CP-{$year}-";

        // Get the last purchase number for this year
        $lastPurchase = self::where('purchase_number', 'like', "{$prefix}%")
            ->orderBy('purchase_number', 'desc')
            ->first();

        if ($lastPurchase) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($lastPurchase->purchase_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Add a status update to history
     */
    public function addStatusUpdate(string $status, ?string $notes = null, ?array $metadata = []): void
    {
        $history = $this->status_history ?? [];

        $history[] = [
            'status' => $status,
            'notes' => $notes,
            'metadata' => $metadata,
            'updated_by' => auth()->id(),
            'updated_at' => now()->toISOString(),
        ];

        $updates = [
            'status' => $status,
            'status_history' => $history,
        ];

        // Auto-set timestamps based on status
        if ($status === 'dispatched' && !$this->dispatched_at) {
            $updates['dispatched_at'] = now();
        } elseif ($status === 'delivered' && !$this->delivered_at) {
            $updates['delivered_at'] = now();
        }

        $this->update($updates);
    }

    /**
     * Mark payment as completed
     */
    public function markAsPaid(?string $transactionId = null): void
    {
        $this->update([
            'payment_status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);

        $this->addStatusUpdate('processing', 'Payment confirmed');
    }

    /**
     * Mark payment as failed
     */
    public function markPaymentFailed(string $reason = 'Payment verification failed'): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);

        $this->addStatusUpdate('failed', $reason);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'processing' => 'blue',
            'dispatched' => 'indigo',
            'in_transit' => 'purple',
            'delivered' => 'green',
            'failed' => 'red',
            'cancelled' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'dispatched' => 'Dispatched',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get payment status badge color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($this->payment_status),
        };
    }

    /**
     * Get delivery type label
     */
    public function getDeliveryTypeLabelAttribute(): string
    {
        return match($this->delivery_type) {
            'swift' => 'Swift Home Delivery',
            'gain_outlet' => 'Gain Outlet Depot Collection',
            default => ucfirst($this->delivery_type),
        };
    }

    /**
     * Get purchase type label
     */
    public function getPurchaseTypeLabelAttribute(): string
    {
        return match($this->purchase_type) {
            'personal' => 'Personal Products',
            'microbiz' => 'MicroBiz Starter Pack',
            default => ucfirst($this->purchase_type),
        };
    }

    /**
     * Calculate savings compared to loan price
     */
    public function getSavingsAttribute(): float
    {
        if (!$this->loan_price) {
            return 0;
        }

        return $this->loan_price - $this->cash_price;
    }

    /**
     * Get formatted currency values
     */
    public function getFormattedCashPriceAttribute(): string
    {
        return '$' . number_format($this->cash_price, 2);
    }

    public function getFormattedAmountPaidAttribute(): string
    {
        return '$' . number_format($this->amount_paid, 2);
    }

    public function getFormattedSavingsAttribute(): string
    {
        return '$' . number_format($this->savings, 2);
    }

    /**
     * Scope: Filter by purchase type
     */
    public function scopePersonal($query)
    {
        return $query->where('purchase_type', 'personal');
    }

    public function scopeMicrobiz($query)
    {
        return $query->where('purchase_type', 'microbiz');
    }

    /**
     * Scope: Filter by payment status
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter by delivery type
     */
    public function scopeSwiftDelivery($query)
    {
        return $query->where('delivery_type', 'swift');
    }

    public function scopeDepotCollection($query)
    {
        return $query->where('delivery_type', 'gain_outlet');
    }

    /**
     * Scope: Search by customer details
     */
    public function scopeSearchCustomer($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('national_id', 'like', "%{$search}%")
              ->orWhere('full_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('purchase_number', 'like', "%{$search}%");
        });
    }
}