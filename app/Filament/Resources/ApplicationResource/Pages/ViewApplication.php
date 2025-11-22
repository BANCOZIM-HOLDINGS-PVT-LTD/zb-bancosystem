<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Services\PDFGeneratorService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_pdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-document')
                ->color('success')
                ->action(function () {
                    try {
                        $pdfGenerator = app(PDFGeneratorService::class);
                        $pdfPath = $pdfGenerator->generateApplicationPDF($this->record);
                        
                        Notification::make()
                            ->title('PDF Generated Successfully')
                            ->success()
                            ->send();
                            
                        return redirect()->route('application.pdf.view', $this->record->session_id);
                    } catch (\Exception $e) {
                        Log::error('PDF Generation failed: ' . $e->getMessage(), [
                            'session_id' => $this->record->session_id,
                            'exception' => $e,
                        ]);
                        
                        Notification::make()
                            ->title('PDF Generation Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    try {
                        $pdfGenerator = app(PDFGeneratorService::class);
                        $pdfPath = $pdfGenerator->generateApplicationPDF($this->record);
                        
                        return response()->download(
                            Storage::disk('public')->path($pdfPath),
                            basename($pdfPath)
                        );
                    } catch (\Exception $e) {
                        Log::error('PDF Download failed: ' . $e->getMessage(), [
                            'session_id' => $this->record->session_id,
                            'exception' => $e,
                        ]);
                        
                        Notification::make()
                            ->title('PDF Download Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('view_pdf')
                ->label('View PDF')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('application.pdf.view', $this->record->session_id))
                ->openUrlInNewTab(),
                
            Actions\Action::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(function () {
                    // Detect form type using the same logic as ApplicationResource
                    $formType = $this->detectFormType($this->record);

                    // SSB Loan Application Form
                    if ($formType === 'ssb') {
                        return [
                            \Filament\Forms\Components\Select::make('ssb_action')
                                ->label('SSB Workflow Action')
                                ->options([
                                    'initialize' => 'Initialize SSB Workflow',
                                    'simulate_approved' => 'Simulate: Approved',
                                    'simulate_insufficient_salary' => 'Simulate: Insufficient Salary',
                                    'simulate_invalid_id' => 'Simulate: Invalid ID',
                                    'simulate_contract_expiring' => 'Simulate: Contract Expiring',
                                    'simulate_rejected' => 'Simulate: Rejected',
                                ])
                                ->required()
                                ->live(),
                            \Filament\Forms\Components\TextInput::make('recommended_period')
                                ->label('Recommended Period (months)')
                                ->numeric()
                                ->visible(fn (\Filament\Forms\Get $get) => in_array($get('ssb_action'), ['simulate_insufficient_salary', 'simulate_contract_expiring']))
                                ->required(fn (\Filament\Forms\Get $get) => in_array($get('ssb_action'), ['simulate_insufficient_salary', 'simulate_contract_expiring'])),
                            \Filament\Forms\Components\DatePicker::make('contract_expiry_date')
                                ->label('Contract Expiry Date')
                                ->visible(fn (\Filament\Forms\Get $get) => $get('ssb_action') === 'simulate_contract_expiring')
                                ->required(fn (\Filament\Forms\Get $get) => $get('ssb_action') === 'simulate_contract_expiring'),
                        ];
                    }

                    // ZB Account Opening or Account Holder Form
                    if ($formType === 'zb_account_opening' || $formType === 'account_holders') {
                        return [
                            \Filament\Forms\Components\Select::make('zb_action')
                                ->label('ZB Workflow Action')
                                ->options([
                                    'initialize' => 'Initialize ZB Workflow',
                                    'credit_check_good' => 'Credit Check: Good (Approve)',
                                    'credit_check_poor' => 'Credit Check: Poor (Reject + Blacklist Report)',
                                    'salary_not_regular' => 'Salary Not Regular (Reject)',
                                    'insufficient_salary' => 'Insufficient Salary (Reject + Period Adjustment)',
                                    'approved' => 'Approved',
                                ])
                                ->required()
                                ->live(),
                            \Filament\Forms\Components\TextInput::make('recommended_period')
                                ->label('Recommended Period (months)')
                                ->numeric()
                                ->visible(fn (\Filament\Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                                ->required(fn (\Filament\Forms\Get $get) => $get('zb_action') === 'insufficient_salary'),
                        ];
                    }

                    // Fallback - should never reach here
                    return [
                        \Filament\Forms\Components\Placeholder::make('error')
                            ->label('Unknown Form Type')
                            ->content('Unable to detect form type. Please contact support.'),
                    ];
                })
                ->action(function (array $data) {
                    // Detect form type using the same logic as ApplicationResource
                    $formType = $this->detectFormType($this->record);

                    // Handle SSB workflow
                    if ($formType === 'ssb') {
                        $controller = app(\App\Http\Controllers\Admin\ApplicationManagementController::class);

                        switch ($data['ssb_action']) {
                            case 'initialize':
                                $controller->initializeSSBWorkflow($this->record->session_id);
                                break;
                            case 'simulate_approved':
                                $controller->simulateSSBResponse($this->record->session_id, new \Illuminate\Http\Request([
                                    'response_type' => 'approved'
                                ]));
                                break;
                            case 'simulate_insufficient_salary':
                                $controller->simulateSSBResponse($this->record->session_id, new \Illuminate\Http\Request([
                                    'response_type' => 'insufficient_salary',
                                    'recommended_period' => $data['recommended_period']
                                ]));
                                break;
                            case 'simulate_invalid_id':
                                $controller->simulateSSBResponse($this->record->session_id, new \Illuminate\Http\Request([
                                    'response_type' => 'invalid_id'
                                ]));
                                break;
                            case 'simulate_contract_expiring':
                                $controller->simulateSSBResponse($this->record->session_id, new \Illuminate\Http\Request([
                                    'response_type' => 'contract_expiring',
                                    'recommended_period' => $data['recommended_period'],
                                    'contract_expiry_date' => $data['contract_expiry_date']
                                ]));
                                break;
                            case 'simulate_rejected':
                                $controller->simulateSSBResponse($this->record->session_id, new \Illuminate\Http\Request([
                                    'response_type' => 'rejected'
                                ]));
                                break;
                        }

                        Notification::make()
                            ->title('SSB Workflow Updated Successfully')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                        return;
                    }

                    // Handle ZB workflow (Account Opening or Account Holders)
                    if ($formType === 'zb_account_opening' || $formType === 'account_holders') {
                        $controller = app(\App\Http\Controllers\Admin\ApplicationManagementController::class);

                        switch ($data['zb_action']) {
                            case 'initialize':
                                $controller->initializeZBWorkflow($this->record->session_id);
                                break;
                            case 'credit_check_good':
                                $controller->processCreditCheckGood($this->record->session_id, new \Illuminate\Http\Request());
                                break;
                            case 'credit_check_poor':
                                $controller->processCreditCheckPoor($this->record->session_id, new \Illuminate\Http\Request());
                                break;
                            case 'salary_not_regular':
                                $controller->processSalaryNotRegular($this->record->session_id, new \Illuminate\Http\Request());
                                break;
                            case 'insufficient_salary':
                                $controller->processInsufficientSalary($this->record->session_id, new \Illuminate\Http\Request([
                                    'recommended_period' => $data['recommended_period']
                                ]));
                                break;
                            case 'approved':
                                $controller->processZBApproved($this->record->session_id, new \Illuminate\Http\Request());
                                break;
                        }

                        Notification::make()
                            ->title('ZB Workflow Updated Successfully')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                        return;
                    }

                    // This should never happen - all forms should be SSB, ZB Account Opening, or Account Holders
                    Log::error('Unknown form type detected in Update Status', [
                        'session_id' => $this->record->session_id,
                        'form_type' => $formType,
                        'form_data' => $this->record->form_data,
                    ]);

                    Notification::make()
                        ->title('Unknown Form Type')
                        ->body('Unable to detect form type for this application. Please contact support.')
                        ->danger()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
                
            Actions\Action::make('send_reminder')
                ->label('Send Reminder')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('reminder_type')
                        ->label('Reminder Type')
                        ->options([
                            'documents' => 'Missing Documents',
                            'information' => 'Additional Information',
                            'payment' => 'Payment Required',
                            'general' => 'General Reminder',
                        ])
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Custom Message')
                        ->placeholder('Enter a custom message for the applicant'),
                    \Filament\Forms\Components\Select::make('channel')
                        ->label('Notification Channel')
                        ->options([
                            'sms' => 'SMS',
                            'email' => 'Email',
                            'whatsapp' => 'WhatsApp',
                        ])
                        ->required()
                        ->default('whatsapp'),
                ])
                ->action(function (array $data) {
                    // This would be implemented in a real system to send the reminder
                    Log::info('Reminder sent to applicant', [
                        'session_id' => $this->record->session_id,
                        'reminder_type' => $data['reminder_type'],
                        'channel' => $data['channel'],
                    ]);
                    
                    Notification::make()
                        ->title('Reminder Sent Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            ViewApplication\ApplicationStatusWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            ViewApplication\ApplicationTimelineWidget::class,
            ViewApplication\ApplicationDocumentsWidget::class,
        ];
    }

    /**
     * Detect form type from application data
     * Same logic as ApplicationResource::detectFormType()
     */
    protected function detectFormType($application): string
    {
        // Check metadata first
        if (isset($application->metadata['form_type'])) {
            return $application->metadata['form_type'];
        }

        // Detect from form data
        $formData = $application->form_data;
        $formResponses = $formData['formResponses'] ?? $formData;

        // SSB Form: Has responsibleMinistry field
        if (isset($formData['responsibleMinistry']) || isset($formResponses['responsibleMinistry'])) {
            return 'ssb';
        }

        // SME Business Form: Has businessName or businessRegistration
        elseif (isset($formData['businessName']) || isset($formData['businessRegistration']) ||
                isset($formResponses['businessName']) || isset($formResponses['businessRegistration'])) {
            return 'sme_business';
        }

        // ZB Account Opening: Has accountType field
        elseif (isset($formData['accountType']) || isset($formResponses['accountType'])) {
            return 'zb_account_opening';
        }

        // Account Holders: Default
        else {
            return 'account_holders';
        }
    }
}
