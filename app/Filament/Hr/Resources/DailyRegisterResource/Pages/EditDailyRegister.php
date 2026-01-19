<?php

namespace App\Filament\Hr\Resources\DailyRegisterResource\Pages;

use App\Filament\Hr\Resources\DailyRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyRegister extends EditRecord
{
    protected static string $resource = DailyRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
