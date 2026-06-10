<?php

namespace App\Filament\Stores\Pages;

class ZimPostDeliveries extends \App\Filament\Pages\ZimPostDeliveries
{
    protected static ?string $navigationGroup = 'Deliveries';
    protected static ?int $navigationSort = 1;

    protected static function detailRouteName(): string
    {
        return 'filament.stores.pages.zim-post-delivery-detail';
    }
}
