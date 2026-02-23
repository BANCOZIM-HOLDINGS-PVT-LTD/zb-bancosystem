<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'tax_number',
        'status',
        'rating',
        'payment_terms',
        'metadata',
        'branches'
    ];
    
    protected $casts = [
        'rating' => 'decimal:2',
        'payment_terms' => 'array',
        'metadata' => 'array',
        'branches' => 'array',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($supplier) {
            if (empty($supplier->supplier_code)) {
                $supplier->supplier_code = self::generateSupplierCode();
            }
        });
    }
    
    /**
     * Generate unique supplier code
     */
    public static function generateSupplierCode(): string
    {
        $lastSupplier = self::orderBy('id', 'desc')->first();
        $nextId = $lastSupplier ? $lastSupplier->id + 1 : 1;
        
        return 'SUP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get supplier's purchase orders
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    
    /**
     * Get supplier's products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get MicroBiz business subcategories supplied by this supplier
     */
    public function microbizSubcategories(): HasMany
    {
        return $this->hasMany(MicrobizSubcategory::class);
    }
    
    /**
     * Get total orders count
     */
    public function getTotalOrdersAttribute(): int
    {
        return $this->purchaseOrders()->count();
    }
    
    /**
     * Get total order value
     */
    public function getTotalOrderValueAttribute(): float
    {
        return $this->purchaseOrders()->sum('total_amount');
    }
    
    /**
     * Get average delivery time in days
     */
    public function getAverageDeliveryTimeAttribute(): float
    {
        $orders = $this->purchaseOrders()
            ->whereNotNull('order_date')
            ->whereNotNull('actual_delivery_date')
            ->get();
        
        if ($orders->isEmpty()) {
            return 0;
        }
        
        $totalDays = 0;
        foreach ($orders as $order) {
            $totalDays += $order->order_date->diffInDays($order->actual_delivery_date);
        }
        
        return round($totalDays / $orders->count(), 1);
    }
    
    /**
     * Get on-time delivery rate
     */
    public function getOnTimeDeliveryRateAttribute(): float
    {
        $orders = $this->purchaseOrders()
            ->whereNotNull('expected_delivery_date')
            ->whereNotNull('actual_delivery_date')
            ->get();
        
        if ($orders->isEmpty()) {
            return 100;
        }
        
        $onTimeCount = $orders->filter(function ($order) {
            return $order->actual_delivery_date <= $order->expected_delivery_date;
        })->count();
        
        return round(($onTimeCount / $orders->count()) * 100, 2);
    }
    
    /**
     * Scope for active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Scope for suppliers by rating
     */
    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }
    
    /**
     * Update supplier rating based on performance
     */
    public function updateRating(): void
    {
        $onTimeRate = $this->on_time_delivery_rate;
        
        // Simple rating calculation based on on-time delivery
        if ($onTimeRate >= 95) {
            $rating = 5.0;
        } elseif ($onTimeRate >= 90) {
            $rating = 4.5;
        } elseif ($onTimeRate >= 85) {
            $rating = 4.0;
        } elseif ($onTimeRate >= 80) {
            $rating = 3.5;
        } elseif ($onTimeRate >= 75) {
            $rating = 3.0;
        } else {
            $rating = 2.0;
        }
        
        $this->update(['rating' => $rating]);
    }
}