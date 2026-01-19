<?php

namespace App\Filament\Hr\Resources\DailyRegisterResource\Pages;

use App\Filament\Hr\Resources\DailyRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyRegisters extends ListRecords
{
    protected static string $resource = DailyRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Attendance'),
        ];
    }
}
