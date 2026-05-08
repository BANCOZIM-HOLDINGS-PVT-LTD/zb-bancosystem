<?php

namespace App\Listeners;

use App\Events\ApplicationApproved;
use App\Services\PurchaseOrderService;

class CreatePurchaseOrdersForApprovedApplication
{
    public function handle(ApplicationApproved $event): void
    {
        app(PurchaseOrderService::class)->createFromApplication($event->application);
    }
}
