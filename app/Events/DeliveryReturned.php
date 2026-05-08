<?php

namespace App\Events;

use App\Models\DeliveryTracking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryReturned
{
    use Dispatchable, SerializesModels;

    public function __construct(public DeliveryTracking $deliveryTracking)
    {
    }
}
