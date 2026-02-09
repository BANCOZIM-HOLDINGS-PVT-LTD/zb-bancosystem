<?php

namespace App\Filament\Resources\MEApplicantsResource\Pages;

use App\Filament\Resources\MEApplicantsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMEApplicant extends ViewRecord
{
    protected static string $resource = MEApplicantsResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('schedule_delivery')
                ->label('Schedule Delivery')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn () => !$this->record->deliveryTracking()->exists())
                ->url(fn () => route('filament.admin.resources.delivery-trackings.create', [
                    'application_state_id' => $this->record->id,
                ])),
                
            Actions\Action::make('send_sms')
                ->label('Send SMS')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->default('Dear {name}, your M&E and Training package is ready for delivery. We will contact you shortly to schedule delivery.'),
                ])
                ->action(function (array $data) {
                    $formData = $this->record->form_data ?? [];
                    $formResponses = $formData['formResponses'] ?? $formData;
                    $phone = $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellphone'] ?? null;
                    $name = trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['lastName'] ?? '')) ?: 'Customer';
                    
                    if (!$phone) {
                        \Filament\Notifications\Notification::make()
                            ->title('No Phone Number')
                            ->body('Could not find a phone number for this applicant.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $message = str_replace('{name}', $name, $data['message']);
                    
                    try {
                        app(\App\Services\SMSService::class)->sendSMS($phone, $message);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('SMS Sent')
                            ->body("Message sent to {$phone}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('SMS Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
