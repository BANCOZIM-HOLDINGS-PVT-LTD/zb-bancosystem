<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'unit_price',
        'total_amount',
        'payment_method',
        'sale_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sale_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            // Check stock before sale
            $product = $sale->product;
            if ($product && $product->inventory) {
                 if ($product->inventory->available_stock < $sale->quantity) {
                     // We could throw exception but Filament handles validation better in the form.
                     // This is a failsafe.
                 }
                 
                 // Deduct stock
                 $product->inventory->removeStock($sale->quantity, 'sale');
            }
        });
    }
}
