<?php

namespace App\Filament\Resources\CustomerManagementResource\Pages;

use App\Filament\Resources\CustomerManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerManagement extends ViewRecord
{
    protected static string $resource = CustomerManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('send')
                ->label('Send Now')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->canBeSent())
                ->action(function () {
                    $this->record->update(['status' => 'sending']);
                    dispatch(function () {
                        app(\App\Services\CustomerManagementService::class)->sendCampaign($this->record);
                    })->afterCommit();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Campaign Started')
                        ->body('SMS campaign is now being sent.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
