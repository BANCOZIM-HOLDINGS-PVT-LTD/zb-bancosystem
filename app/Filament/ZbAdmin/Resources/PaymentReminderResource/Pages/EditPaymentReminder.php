<?php

namespace App\Filament\ZbAdmin\Resources\PaymentReminderResource\Pages;

use App\Filament\ZbAdmin\Resources\PaymentReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentReminder extends EditRecord
{
    protected static string $resource = PaymentReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
