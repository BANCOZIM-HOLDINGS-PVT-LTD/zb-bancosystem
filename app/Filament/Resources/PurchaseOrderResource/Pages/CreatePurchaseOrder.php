<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->name ?? 'System';

        // Calculate subtotal from items
        if (isset($data['items']) && is_array($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['total_price'] ?? 0;
            }
            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal + ($data['tax_amount'] ?? 0) + ($data['shipping_cost'] ?? 0);
        }

        return $data;
    }
}
