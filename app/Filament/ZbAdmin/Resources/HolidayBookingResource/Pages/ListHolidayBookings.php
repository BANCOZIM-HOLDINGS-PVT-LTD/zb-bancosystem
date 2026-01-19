<?php

namespace App\Filament\ZbAdmin\Resources\HolidayBookingResource\Pages;

use App\Filament\ZbAdmin\Resources\HolidayBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHolidayBookings extends ListRecords
{
    protected static string $resource = HolidayBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
