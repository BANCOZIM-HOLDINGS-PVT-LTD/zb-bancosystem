<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashPurchaseItem extends Model
{
    protected $fillable = [
        'cash_purchase_id',
        'product_id',
        'product_name',
        'category',
        'quantity',
        'unit_price',
        'total_price',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function cashPurchase()
    {
        return $this->belongsTo(CashPurchase::class);
    }
}
