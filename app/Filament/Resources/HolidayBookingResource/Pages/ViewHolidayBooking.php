<?php

namespace App\Filament\Resources\HolidayBookingResource\Pages;

use App\Filament\Resources\HolidayBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHolidayBooking extends ViewRecord
{
    protected static string $resource = HolidayBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
