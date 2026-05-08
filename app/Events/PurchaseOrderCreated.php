<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder)
    {
    }
}
