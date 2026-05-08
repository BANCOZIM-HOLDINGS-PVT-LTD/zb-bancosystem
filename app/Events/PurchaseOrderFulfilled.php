<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderFulfilled
{
    use Dispatchable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder)
    {
    }
}
