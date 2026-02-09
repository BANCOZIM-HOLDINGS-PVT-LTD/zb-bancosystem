<?php

namespace App\Filament\Resources\CustomerManagementResource\Pages;

use App\Filament\Resources\CustomerManagementResource;
use App\Models\BulkSMSCampaign;
use App\Models\ApplicationState;
use App\Models\DeliveryTracking;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class PreviewRecipients extends Page
{
    protected static string $resource = CustomerManagementResource::class;
    
    protected static string $view = 'filament.resources.customer-management-resource.pages.preview-recipients';
    
    public BulkSMSCampaign $record;
    
    public array $recipients = [];
    
    public function mount(BulkSMSCampaign $record): void
    {
        $this->record = $record;
        $this->recipients = $this->getRecipients();
    }
    
    public function getTitle(): string
    {
        return "Preview Recipients: {$this->record->name}";
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Campaigns')
                ->url(CustomerManagementResource::getUrl('index'))
                ->color('gray'),
            Actions\Action::make('send')
                ->label('Send Campaign')
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
                        ->success()
                        ->send();
                        
                    return redirect(CustomerManagementResource::getUrl('index'));
                }),
        ];
    }
    
    protected function getRecipients(): array
    {
        $type = $this->record->type;
        
        return match($type) {
            'incomplete_application' => $this->getIncompleteApplicationRecipients(),
            'installment_due' => $this->getInstallmentsDueRecipients(),
            default => [],
        };
    }
    
    protected function getIncompleteApplicationRecipients(): array
    {
        return ApplicationState::query()
            ->where('is_archived', false)
            ->whereNotNull('form_data')
            ->where('current_step', '!=', 'completed')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                return [
                    'name' => $formData['fullName'] ?? $formData['applicantName'] ?? 'Unknown',
                    'phone' => $formData['cellNumber'] ?? $formData['mobileNumber'] ?? '',
                    'reference' => $app->reference_code ?? 'N/A',
                    'status' => $app->current_step ?? 'Unknown',
                ];
            })
            ->values()
            ->toArray();
    }
    
    protected function getInstallmentsDueRecipients(): array
    {
        return DeliveryTracking::query()
            ->join('application_states', 'delivery_trackings.application_state_id', '=', 'application_states.id')
            ->where('delivery_trackings.status', 'delivered')
            ->whereNotNull('delivery_trackings.recipient_phone')
            ->select([
                'delivery_trackings.recipient_name as name',
                'delivery_trackings.recipient_phone as phone',
                'application_states.reference_code as reference',
            ])
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name ?? 'Unknown',
                'phone' => $row->phone,
                'reference' => $row->reference ?? 'N/A',
                'status' => 'delivered',
            ])
            ->toArray();
    }
}

