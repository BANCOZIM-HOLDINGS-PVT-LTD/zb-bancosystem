<?php

namespace App\Listeners;

use App\Events\DeliveryReturned;
use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;

class RestoreStockForReturnedDelivery
{
    public function handle(DeliveryReturned $event): void
    {
        $applicationId = $event->deliveryTracking->application_state_id;
        if (!$applicationId) {
            return;
        }

        $purchaseOrders = PurchaseOrder::where('metadata->application_id', $applicationId)
            ->with('items.product.inventory')
            ->get();

        foreach ($purchaseOrders as $purchaseOrder) {
            foreach ($purchaseOrder->items as $item) {
                $inventory = $item->product?->inventory;
                if (!$inventory) {
                    continue;
                }

                $inventory->increment('stock_quantity', $item->quantity_ordered);
                $inventory->movements()->create([
                    'type' => InventoryMovement::TYPE_RETURN,
                    'quantity' => $item->quantity_ordered,
                    'reference_type' => 'delivery_return',
                    'reference_id' => $event->deliveryTracking->id,
                    'notes' => 'Stock restored after delivery return',
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }
}
