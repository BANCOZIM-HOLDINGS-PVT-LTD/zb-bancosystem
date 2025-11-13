<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationState;
use App\Services\NotificationService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApplicationState::query()
                    ->where('current_step', 'in_review')
                    ->latest()
            )
            ->heading('Pending Approvals')
            ->description('Applications awaiting review and approval')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('App #')
                    ->formatStateUsing(fn ($state) => 'ZB' . date('Y') . str_pad($state, 6, '0', STR_PAD_LEFT)),
                    
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function ($record) {
                        $data = $record->form_data['formResponses'] ?? [];
                        $firstName = $data['firstName'] ?? '';
                        $lastName = $data['lastName'] ?? '';
                        return trim($firstName . ' ' . $lastName) ?: 'N/A';
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn ($record) => '$' . number_format($record->form_data['finalPrice'] ?? 0)),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A'),
                    
                Tables\Columns\TextColumn::make('waiting_time')
                    ->label('Waiting')
                    ->getStateUsing(fn ($record) => $record->created_at->diffForHumans())
                    ->color(function ($record) {
                        $hoursWaiting = $record->created_at->diffInHours(now());
                        if ($hoursWaiting > 72) return 'danger';
                        if ($hoursWaiting > 24) return 'warning';
                        return 'success';
                    }),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->getStateUsing(function ($record) {
                        $hoursWaiting = $record->created_at->diffInHours(now());
                        $amount = $record->form_data['finalPrice'] ?? 0;
                        
                        if ($hoursWaiting > 72 || $amount > 50000) return 'High';
                        if ($hoursWaiting > 24 || $amount > 20000) return 'Medium';
                        return 'Low';
                    })
                    ->colors([
                        'danger' => 'High',
                        'warning' => 'Medium',
                        'success' => 'Low',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (ApplicationState $record): string => route('filament.admin.resources.applications.view', $record))
                    ->icon('heroicon-m-eye'),
                    
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Application')
                    ->modalDescription('Are you sure you want to approve this application?')
                    ->action(function (ApplicationState $record) {
                        $oldStatus = $record->current_step;
                        $record->current_step = 'approved';
                        $record->status_updated_at = now();
                        $record->status_updated_by = auth()->id();
                        $record->save();
                        
                        // Create a state transition record with audit information
                        $record->transitions()->create([
                            'from_step' => $oldStatus,
                            'to_step' => 'approved',
                            'channel' => 'admin',
                            'transition_data' => [
                                'admin_id' => auth()->id(),
                                'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                                'from_widget' => true,
                                'widget_name' => 'PendingApprovalsWidget',
                                'ip_address' => request()->ip(),
                                'timestamp' => now()->toIso8601String(),
                            ],
                            'created_at' => now(),
                        ]);
                        
                        // Send notification to applicant
                        $notificationService = new NotificationService();
                        $notificationService->sendStatusUpdateNotification($record, $oldStatus, 'approved');
                        
                        // Log the approval
                        Log::info('Application approved from widget', [
                            'session_id' => $record->session_id,
                            'reference_code' => $record->reference_code,
                            'admin_id' => auth()->id(),
                            'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                        ]);
                        
                        Notification::make()
                            ->title('Application Approved')
                            ->body('Application has been approved and applicant notified')
                            ->success()
                            ->send();
                        
                        $this->refreshTable();
                    }),
                    
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Application')
                    ->modalDescription('Are you sure you want to reject this application?')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->placeholder('Please provide a reason for rejection')
                            ->required(),
                    ])
                    ->action(function (ApplicationState $record, array $data) {
                        $oldStatus = $record->current_step;
                        $record->current_step = 'rejected';
                        $record->status_updated_at = now();
                        $record->status_updated_by = auth()->id();
                        $record->save();
                        
                        // Create a state transition record with audit information
                        $record->transitions()->create([
                            'from_step' => $oldStatus,
                            'to_step' => 'rejected',
                            'channel' => 'admin',
                            'transition_data' => [
                                'admin_id' => auth()->id(),
                                'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                                'from_widget' => true,
                                'widget_name' => 'PendingApprovalsWidget',
                                'rejection_reason' => $data['rejection_reason'],
                                'ip_address' => request()->ip(),
                                'timestamp' => now()->toIso8601String(),
                            ],
                            'created_at' => now(),
                        ]);
                        
                        // Send notification to applicant
                        $notificationService = new NotificationService();
                        $notificationService->sendStatusUpdateNotification($record, $oldStatus, 'rejected');
                        
                        // Log the rejection
                        Log::info('Application rejected from widget', [
                            'session_id' => $record->session_id,
                            'reference_code' => $record->reference_code,
                            'admin_id' => auth()->id(),
                            'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        
                        Notification::make()
                            ->title('Application Rejected')
                            ->body('Application has been rejected and applicant notified')
                            ->success()
                            ->send();
                        
                        $this->refreshTable();
                    }),
                    
                Tables\Actions\Action::make('request_documents')
                    ->icon('heroicon-m-document-plus')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('document_request')
                            ->label('Document Request')
                            ->placeholder('Specify which documents are needed')
                            ->required(),
                    ])
                    ->action(function (ApplicationState $record, array $data) {
                        $oldStatus = $record->current_step;
                        $record->current_step = 'pending_documents';
                        $record->status_updated_at = now();
                        $record->status_updated_by = auth()->id();
                        $record->save();
                        
                        // Create a state transition record
                        $record->transitions()->create([
                            'from_step' => $oldStatus,
                            'to_step' => 'pending_documents',
                            'channel' => 'admin',
                            'transition_data' => [
                                'admin_id' => auth()->id(),
                                'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                                'from_widget' => true,
                                'widget_name' => 'PendingApprovalsWidget',
                                'document_request' => $data['document_request'],
                                'ip_address' => request()->ip(),
                                'timestamp' => now()->toIso8601String(),
                            ],
                            'created_at' => now(),
                        ]);
                        
                        // Send notification to applicant
                        $notificationService = new NotificationService();
                        $notificationService->sendStatusUpdateNotification($record, $oldStatus, 'pending_documents');
                        
                        Notification::make()
                            ->title('Documents Requested')
                            ->body('Applicant has been notified about required documents')
                            ->success()
                            ->send();
                        
                        $this->refreshTable();
                    }),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('All applications have been reviewed.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}