<?php

namespace App\Filament\ZbAdmin\Resources\HolidayBookingResource\Pages;

use App\Filament\ZbAdmin\Resources\HolidayBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHolidayBooking extends EditRecord
{
    protected static string $resource = HolidayBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
