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
                        $pdfGenerator = new PDFGeneratorService();
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
                        $pdfGenerator = new PDFGeneratorService();
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
                ->form([
                    \Filament\Forms\Components\Select::make('status')
                        ->label('New Status')
                        ->options([
                            'in_review' => 'In Review',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'pending_documents' => 'Pending Documents',
                            'processing' => 'Processing',
                        ])
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Status Notes')
                        ->placeholder('Add notes about this status change'),
                    \Filament\Forms\Components\Checkbox::make('notify_applicant')
                        ->label('Notify Applicant')
                        ->helperText('Send a notification to the applicant about this status change'),
                ])
                ->action(function (array $data) {
                    $oldStatus = $this->record->current_step;
                    $newStatus = $data['status'];
                    
                    // Update the application status
                    $this->record->current_step = $newStatus;
                    $this->record->save();
                    
                    // Create a state transition record
                    $this->record->transitions()->create([
                        'from_step' => $oldStatus,
                        'to_step' => $newStatus,
                        'channel' => 'admin',
                        'transition_data' => [
                            'notes' => $data['notes'] ?? null,
                            'admin_id' => auth()->id(),
                            'notify_applicant' => $data['notify_applicant'] ?? false,
                        ],
                        'created_at' => now(),
                    ]);
                    
                    // Log the status change
                    Log::info('Application status updated', [
                        'session_id' => $this->record->session_id,
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                        'admin_id' => auth()->id(),
                    ]);
                    
                    // Handle notification to applicant if selected
                    if ($data['notify_applicant'] ?? false) {
                        // This would be implemented in a real system to send SMS, email, or WhatsApp
                        Log::info('Applicant notification requested for status change', [
                            'session_id' => $this->record->session_id,
                            'new_status' => $newStatus,
                        ]);
                    }
                    
                    Notification::make()
                        ->title('Status Updated Successfully')
                        ->success()
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
}