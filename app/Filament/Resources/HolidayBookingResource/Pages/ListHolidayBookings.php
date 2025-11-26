<?php

namespace App\Filament\Resources\HolidayBookingResource\Pages;

use App\Filament\Resources\HolidayBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHolidayBookings extends ListRecords
{
    protected static string $resource = HolidayBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export All to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('admin.export.holiday-packages'))
                ->openUrlInNewTab(),
        ];
    }
}
