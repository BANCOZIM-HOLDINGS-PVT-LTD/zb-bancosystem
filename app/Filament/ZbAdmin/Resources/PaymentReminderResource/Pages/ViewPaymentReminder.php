<?php

namespace App\Filament\ZbAdmin\Resources\PaymentReminderResource\Pages;

use App\Filament\ZbAdmin\Resources\PaymentReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentReminder extends ViewRecord
{
    protected static string $resource = PaymentReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
