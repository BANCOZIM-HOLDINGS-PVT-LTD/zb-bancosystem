<?php

namespace App\Filament\Resources\CustomerManagementResource\Pages;

use App\Filament\Resources\CustomerManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerManagement extends ListRecords
{
    protected static string $resource = CustomerManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reports')
                ->label('Download Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(CustomerManagementResource::getUrl('reports')),
            Actions\CreateAction::make()
                ->label('New Campaign'),
        ];
    }
}
