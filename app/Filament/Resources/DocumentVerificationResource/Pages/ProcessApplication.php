<?php

namespace App\Filament\Resources\DocumentVerificationResource\Pages;

use App\Filament\Resources\DocumentVerificationResource;
use App\Models\ApplicationState;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\AutomatedCheckService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Log;

class ProcessApplication extends Page
{
    protected static string $resource = DocumentVerificationResource::class;

    protected static string $view = 'filament.resources.document-verification-resource.pages.process-application';

    public ApplicationState $record;
    
    // Checklist state
    public array $documentStatuses = [];
    public array $rejectionReasons = [];

    public function mount(int | string $record): void
    {
        $this->record = ApplicationState::findOrFail($record);
        
        // Initialize document statuses
        $documents = $this->record->form_data['documents'] ?? $this->record->form_data['documentsByType'] ?? [];
        
        if (isset($documents['uploadedDocuments'])) {
             foreach ($documents['uploadedDocuments'] as $index => $doc) {
                 $this->documentStatuses[$index] = null; // null = pending, true = valid, false = invalid
             }
        } elseif (is_array($documents)) {
             foreach ($documents as $key => $doc) {
                 if (is_array($doc) && isset($doc['path'])) {
                     $this->documentStatuses[$key] = null;
                 }
             }
        }
    }

    public function markDocument(string|int $key, bool $isValid, ?string $reason = null)
    {
        $this->documentStatuses[$key] = $isValid;
        if (!$isValid && $reason) {
            $this->rejectionReasons[$key] = $reason;
        } elseif ($isValid) {
            unset($this->rejectionReasons[$key]);
        }
    }

    public function getHeaderActions(): array
    {
        return [
             Action::make('verify')
                ->label('Verify & Process')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->disabled(fn () => in_array(null, $this->documentStatuses, true) || in_array(false, $this->documentStatuses, true))
                ->action('processVerification'),
                
             Action::make('reject')
                ->label('Reject Application')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->default(fn () => $this->generateRejectionSummary())
                ])
                ->action(function (array $data) {
                    $this->rejectApplication($data['reason']);
                })
        ];
    }
    
    public function generateRejectionSummary(): string
    {
        $reasons = array_values($this->rejectionReasons);
        if (empty($reasons)) {
            return "Application declined due to verify failure.";
        }
        return "Application declined due to: " . implode(', ', $reasons);
    }

    public function processVerification()
    {
        // 1. Double check all documents are valid
        if (in_array(false, $this->documentStatuses, true) || in_array(null, $this->documentStatuses, true)) {
            Notification::make()->title('Error')->body('All documents must be marked as Valid.')->danger()->send();
            return;
        }

        try {
            // Update status
            $this->record->current_step = 'processing';
            $this->record->save();

            // Trigger Automated Checks (SSB/FCB determination)
            $checkService = app(AutomatedCheckService::class);
            $checkService->executeAutomatedChecks($this->record);
            
            // Reload record to get check_type updated by service
            $this->record->refresh();

            // Move to next step based on check type or application type
            if ($this->record->check_type === 'SSB') {
                if ($this->record->check_status === 'S') {
                    $this->record->current_step = 'ssb_check_successful';
                    $this->record->save();
                    
                    // Trigger PO Creation
                    $poService = app(\App\Services\PurchaseOrderService::class);
                    $poService->createFromApplication($this->record);
                    
                    // Move to Approved
                    $workflow = app(\App\Services\ApplicationWorkflowService::class);
                    $workflow->approveApplication($this->record, ['auto_approved' => true, 'notes' => 'Auto-approved after successful SSB Check']);
                    
                    Notification::make()->title('SSB Check Successful - PO Created & Approved')->success()->send();
                    return redirect()->to(DocumentVerificationResource::getUrl('index'));
                } elseif ($this->record->check_status === 'F') {
                     $this->record->current_step = 'ssb_check_failed';
                     $this->record->save();
                     Notification::make()->title('SSB Check Failed')->danger()->send();
                     return redirect()->to(DocumentVerificationResource::getUrl('index'));
                } else {
                    $this->record->current_step = 'ssb_check_initialized';
                }
            } elseif ($this->record->check_type === 'FCB') {
                 // For ZB Account Holders - Move to ZB Admin Verification
                $this->record->current_step = 'zb_verification_pending';
            } else {
                // Default or Account Opening
                // Check if it is account opening
                $formData = $this->record->form_data;
                $isAccountOpening = ($formData['wantsAccount'] ?? false) === true || 
                                   ($formData['intent'] ?? '') === 'account' || 
                                   ($formData['applicationType'] ?? '') === 'account_opening';
                                   
                if ($isAccountOpening) {
                    $this->record->current_step = 'account_opening_initiated';
                } else {
                    $this->record->current_step = 'sent_for_checks';
                }
            }
            
            $this->record->save();

            Notification::make()->title('Application Verified')->success()->send();
            
            return redirect()->to(DocumentVerificationResource::getUrl('index'));

        } catch (\Exception $e) {
            Log::error('Verification Process Failed', ['id' => $this->record->id, 'error' => $e->getMessage()]);
            Notification::make()->title('Error')->body('Failed to process: ' . $e->getMessage())->danger()->send();
        }
    }

    public function rejectApplication($reason)
    {
        try {
            $this->record->current_step = 'documents_rejected';
            $this->record->status_updated_at = now();
            // Store rejection metadata
            $metadata = $this->record->metadata ?? [];
            $metadata['rejection_reason'] = $reason;
            $metadata['document_rejection_reasons'] = $this->rejectionReasons;
            $this->record->metadata = $metadata;
            
            $this->record->save();

            // Send SMS
            $this->sendRejectionSMS($reason);

            Notification::make()->title('Application Rejected')->success()->send();
            return redirect()->to(DocumentVerificationResource::getUrl('index'));

        } catch (\Exception $e) {
            Log::error('Rejection Process Failed', ['id' => $this->record->id, 'error' => $e->getMessage()]);
            Notification::make()->title('Error')->body('Failed to reject: ' . $e->getMessage())->danger()->send();
        }
    }
    
    protected function sendRejectionSMS($reason)
    {
        $formData = $this->record->form_data;
        $phone = $formData['formResponses']['mobile'] ??
                 $formData['formResponses']['phoneNumber'] ??
                 $formData['formResponses']['contactPhone'] ?? null;
                 
        if ($phone) {
            $smsService = app(SMSService::class);
            $smsService->sendSMS($phone, "ZB: Your application was declined. " . $reason);
        }
    }
}
