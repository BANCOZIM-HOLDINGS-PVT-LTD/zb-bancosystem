<?php

namespace App\Listeners;

use App\Events\PurchaseOrderFulfilled;
use App\Models\InventoryMovement;

class DeductStockForFulfilledPurchaseOrder
{
    public function handle(PurchaseOrderFulfilled $event): void
    {
        $event->purchaseOrder->loadMissing('items.product.inventory');

        foreach ($event->purchaseOrder->items as $item) {
            $inventory = $item->product?->inventory;
            if (!$inventory) {
                continue;
            }

            $quantity = $item->quantity_received > 0 ? $item->quantity_received : $item->quantity_ordered;
            $inventory->decrement('reserved_quantity', min($inventory->reserved_quantity, $quantity));
            $inventory->decrement('stock_quantity', min($inventory->stock_quantity, $quantity));
            $inventory->movements()->create([
                'type' => InventoryMovement::TYPE_SALE,
                'quantity' => -$quantity,
                'reference_type' => 'purchase_order',
                'reference_id' => $event->purchaseOrder->id,
                'notes' => "Deducted on PO fulfilment {$event->purchaseOrder->po_number}",
                'user_id' => auth()->id(),
            ]);
        }
    }
}
