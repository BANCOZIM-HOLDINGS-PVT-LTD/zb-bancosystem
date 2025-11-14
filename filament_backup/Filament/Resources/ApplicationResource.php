<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Widgets;
use App\Models\ApplicationState;
use App\Services\ApplicationWorkflowService;
use App\Services\NotificationService;
use App\Services\PDFGeneratorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApplicationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Loan Applications';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\TextInput::make('session_id')
                            ->label('Session ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled(),
                        Forms\Components\TextInput::make('channel')
                            ->label('Channel')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'completed' => 'Completed',
                                'in_review' => 'In Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'pending_documents' => 'Pending Documents',
                                'processing' => 'Processing',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('status_updated_at', now()->toDateTimeString());
                                $set('status_updated_by', auth()->id());
                            }),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Submitted Date')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('status_updated_at')
                            ->label('Status Updated')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => $get('status') !== 'completed'),
                        Forms\Components\TextInput::make('status_updated_by')
                            ->label('Updated By (User ID)')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => $get('status') !== 'completed'),
                        Forms\Components\TextInput::make('status_updated_by_name')
                            ->label('Updated By')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => $get('status') !== 'completed')
                            ->formatStateUsing(function ($record) {
                                if ($record && $record->status_updated_by) {
                                    $user = \App\Models\User::find($record->status_updated_by);

                                    return $user ? $user->name : 'Unknown Admin';
                                }

                                return null;
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Applicant Information')
                    ->schema([
                        Forms\Components\ViewField::make('form_data')
                            ->label('Application Data')
                            ->view('filament.forms.components.application-data'),
                    ]),

                Forms\Components\Section::make('Status History')
                    ->schema([
                        Forms\Components\Repeater::make('transitions')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('from_step')
                                    ->label('From Status')
                                    ->disabled(),
                                Forms\Components\TextInput::make('to_step')
                                    ->label('To Status')
                                    ->disabled(),
                                Forms\Components\TextInput::make('channel')
                                    ->label('Channel')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Date')
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Admin Notes')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes')
                            ->placeholder('Add notes about this application')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('App #')
                    ->formatStateUsing(fn ($state) => 'ZB'.date('Y').str_pad($state, 6, '0', STR_PAD_LEFT))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Reference code for applicant tracking'),

                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function (Model $record) {
                        $data = $record->form_data['formResponses'] ?? [];
                        $firstName = $data['firstName'] ?? '';
                        $lastName = $data['lastName'] ?? '';

                        return trim($firstName.' '.$lastName) ?: 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("JSON_EXTRACT(form_data, '$.formResponses.firstName') LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.lastName') LIKE ?", ["%{$search}%"]);
                    }),

                Tables\Columns\TextColumn::make('business_type')
                    ->label('Business')
                    ->getStateUsing(fn (Model $record) => $record->form_data['selectedBusiness']['name'] ?? 'N/A')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("JSON_EXTRACT(form_data, '$.selectedBusiness.name') {$direction}");
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (Model $record) => '$'.number_format($record->form_data['finalPrice'] ?? 0))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("CAST(JSON_EXTRACT(form_data, '$.finalPrice') AS DECIMAL(10,2)) {$direction}");
                    }),

                Tables\Columns\BadgeColumn::make('channel')
                    ->colors([
                        'primary' => 'web',
                        'success' => 'whatsapp',
                        'warning' => 'ussd',
                        'danger' => 'mobile_app',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['completed', 'approved']),
                        'warning' => fn ($state): bool => in_array($state, ['in_review', 'processing', 'pending_documents']),
                        'danger' => fn ($state): bool => $state === 'rejected',
                        'gray' => fn ($state): bool => in_array($state, ['language', 'intent', 'employer', 'form', 'product', 'business']),
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'web' => 'Web',
                        'whatsapp' => 'WhatsApp',
                        'ussd' => 'USSD',
                        'mobile_app' => 'Mobile App',
                    ]),

                SelectFilter::make('current_step')
                    ->label('Status')
                    ->options([
                        'completed' => 'Completed',
                        'in_review' => 'In Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'pending_documents' => 'Pending Documents',
                        'processing' => 'Processing',
                        'form' => 'In Progress',
                        'abandoned' => 'Abandoned',
                    ]),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                TernaryFilter::make('has_documents')
                    ->label('Has Documents')
                    ->queries(
                        true: fn (Builder $query) => $query->whereRaw("JSON_EXTRACT(form_data, '$.documents.uploadedDocuments') IS NOT NULL"),
                        false: fn (Builder $query) => $query->whereRaw("JSON_EXTRACT(form_data, '$.documents.uploadedDocuments') IS NULL"),
                    ),

                Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum Amount')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->whereRaw("CAST(JSON_EXTRACT(form_data, '$.finalPrice') AS DECIMAL(10,2)) >= ?", [$amount]),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->whereRaw("CAST(JSON_EXTRACT(form_data, '$.finalPrice') AS DECIMAL(10,2)) <= ?", [$amount]),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('success')
                    ->action(function (Model $record) {
                        try {
                            $pdfGenerator = new PDFGeneratorService;
                            $pdfPath = $pdfGenerator->generateApplicationPDF($record);

                            Notification::make()
                                ->title('PDF Generated Successfully')
                                ->success()
                                ->send();

                            return redirect()->route('application.pdf.view', $record->session_id);
                        } catch (\Exception $e) {
                            Log::error('PDF Generation failed: '.$e->getMessage(), [
                                'session_id' => $record->session_id,
                                'exception' => $e,
                            ]);

                            Notification::make()
                                ->title('PDF Generation Failed')
                                ->body('Error: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function (Model $record) {
                        try {
                            $pdfGenerator = new PDFGeneratorService;
                            $pdfPath = $pdfGenerator->generateApplicationPDF($record);

                            return response()->download(
                                Storage::disk('public')->path($pdfPath),
                                basename($pdfPath)
                            );
                        } catch (\Exception $e) {
                            Log::error('PDF Download failed: '.$e->getMessage(), [
                                'session_id' => $record->session_id,
                                'exception' => $e,
                            ]);

                            Notification::make()
                                ->title('PDF Download Failed')
                                ->body('Error: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Model $record) => route('application.pdf.view', $record->session_id))
                    ->openUrlInNewTab(),

                Action::make('view_status_history')
                    ->label('Status History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Application Status History')
                    ->modalContent(function (Model $record) {
                        $transitions = $record->transitions()
                            ->orderBy('created_at', 'desc')
                            ->get();

                        return view('filament.resources.application-resource.modals.status-history', [
                            'transitions' => $transitions,
                            'record' => $record,
                        ]);
                    })
                    ->modalWidth('4xl'),

                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('New Status')
                            ->options([
                                'in_review' => 'In Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'pending_documents' => 'Pending Documents',
                                'processing' => 'Processing',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Status Notes')
                            ->placeholder('Add notes about this status change'),
                        Forms\Components\Toggle::make('send_notification')
                            ->label('Send Notification to Applicant')
                            ->default(true),
                    ])
                    ->action(function (array $data, Model $record) {
                        $oldStatus = $record->current_step;
                        $newStatus = $data['status'];

                        // Update the application status
                        $record->current_step = $newStatus;
                        $record->status_updated_at = now();
                        $record->status_updated_by = auth()->id();
                        $record->save();

                        // Create a state transition record with audit information
                        $record->transitions()->create([
                            'from_step' => $oldStatus,
                            'to_step' => $newStatus,
                            'channel' => 'admin',
                            'transition_data' => [
                                'notes' => $data['notes'] ?? null,
                                'admin_id' => auth()->id(),
                                'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                                'admin_email' => auth()->user()->email ?? null,
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                                'timestamp' => now()->toIso8601String(),
                            ],
                            'created_at' => now(),
                        ]);

                        // Send notification to applicant if requested
                        if ($data['send_notification'] ?? true) {
                            $notificationService = new NotificationService;
                            $notificationService->sendStatusUpdateNotification($record, $oldStatus, $newStatus);
                        }

                        // Log the status change with comprehensive audit information
                        Log::info('Application status updated by admin', [
                            'session_id' => $record->session_id,
                            'reference_code' => $record->reference_code,
                            'from_status' => $oldStatus,
                            'to_status' => $newStatus,
                            'admin_id' => auth()->id(),
                            'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                            'admin_email' => auth()->user()->email ?? null,
                            'notes' => $data['notes'] ?? null,
                            'notification_sent' => $data['send_notification'] ?? true,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'timestamp' => now()->toIso8601String(),
                        ]);

                        Notification::make()
                            ->title('Status Updated Successfully')
                            ->body("Application status changed from {$oldStatus} to {$newStatus}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('generate_pdfs')
                        ->label('Generate PDFs')
                        ->icon('heroicon-o-document')
                        ->action(function ($records) {
                            $sessionIds = $records->pluck('session_id')->toArray();
                            $count = count($sessionIds);

                            try {
                                $pdfGenerator = new PDFGeneratorService;
                                $generatedPaths = $pdfGenerator->generateBatchPDFs($sessionIds);

                                Notification::make()
                                    ->title("Generated {$count} PDFs Successfully")
                                    ->success()
                                    ->send();

                                // Return the first PDF for viewing
                                if (! empty($generatedPaths)) {
                                    return redirect()->route('application.pdf.view', $sessionIds[0]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Bulk PDF Generation failed: '.$e->getMessage(), [
                                    'session_ids' => $sessionIds,
                                    'exception' => $e,
                                ]);

                                Notification::make()
                                    ->title('Bulk PDF Generation Failed')
                                    ->body('Error: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('download_pdfs')
                        ->label('Download PDFs')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $sessionIds = $records->pluck('session_id')->toArray();

                            // This would trigger batch PDF download
                            return redirect()->route('application.pdf.batch', [
                                'session_ids' => $sessionIds,
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('update_status_bulk')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'in_review' => 'In Review',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    'pending_documents' => 'Pending Documents',
                                    'processing' => 'Processing',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Status Notes')
                                ->placeholder('Add notes about this status change'),
                            Forms\Components\Toggle::make('send_notifications')
                                ->label('Send Notifications to Applicants')
                                ->default(true),
                        ])
                        ->action(function (array $data, $records) {
                            $newStatus = $data['status'];
                            $count = $records->count();
                            $notificationService = new NotificationService;
                            $applicationUpdates = [];

                            foreach ($records as $record) {
                                $oldStatus = $record->current_step;

                                // Update the application status
                                $record->current_step = $newStatus;
                                $record->status_updated_at = now();
                                $record->status_updated_by = auth()->id();
                                $record->save();

                                // Create a state transition record with audit information
                                $record->transitions()->create([
                                    'from_step' => $oldStatus,
                                    'to_step' => $newStatus,
                                    'channel' => 'admin',
                                    'transition_data' => [
                                        'notes' => $data['notes'] ?? null,
                                        'admin_id' => auth()->id(),
                                        'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                                        'admin_email' => auth()->user()->email ?? null,
                                        'bulk_update' => true,
                                        'bulk_count' => $count,
                                        'ip_address' => request()->ip(),
                                        'user_agent' => request()->userAgent(),
                                        'timestamp' => now()->toIso8601String(),
                                    ],
                                    'created_at' => now(),
                                ]);

                                // Collect for batch notifications
                                if ($data['send_notifications'] ?? true) {
                                    $applicationUpdates[] = [
                                        'application' => $record,
                                        'old_status' => $oldStatus,
                                        'new_status' => $newStatus,
                                    ];
                                }
                            }

                            // Send batch notifications
                            if (! empty($applicationUpdates)) {
                                $notificationResults = $notificationService->sendBatchStatusNotifications($applicationUpdates);
                                $successCount = count(array_filter($notificationResults, fn ($r) => $r['success']));

                                Log::info('Bulk status notifications sent', [
                                    'total_applications' => count($applicationUpdates),
                                    'successful_notifications' => $successCount,
                                    'failed_notifications' => count($applicationUpdates) - $successCount,
                                ]);
                            }

                            // Log the bulk status change with comprehensive audit information
                            Log::info('Bulk application status updated by admin', [
                                'count' => $count,
                                'to_status' => $newStatus,
                                'admin_id' => auth()->id(),
                                'admin_name' => auth()->user()->name ?? 'Unknown Admin',
                                'admin_email' => auth()->user()->email ?? null,
                                'notes' => $data['notes'] ?? null,
                                'notifications_sent' => $data['send_notifications'] ?? true,
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                                'timestamp' => now()->toIso8601String(),
                                'session_ids' => $records->pluck('session_id')->toArray(),
                            ]);

                            Notification::make()
                                ->title("Updated Status for {$count} Applications")
                                ->body("All selected applications have been updated to {$newStatus}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('workflow_approve')
                        ->label('Approve Applications')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Applications')
                        ->modalDescription('This will approve all selected applications and trigger commission calculations.')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Approval Notes')
                                ->placeholder('Optional notes for the approval...')
                                ->rows(3),
                            Forms\Components\Toggle::make('send_notifications')
                                ->label('Send notifications to applicants')
                                ->default(true),
                        ])
                        ->action(function ($records, array $data) {
                            $workflowService = app(ApplicationWorkflowService::class);
                            $applicationIds = $records->pluck('id')->toArray();

                            $results = $workflowService->processBulkApplications($applicationIds, 'approve', [
                                'notes' => $data['notes'] ?? null,
                                'send_notifications' => $data['send_notifications'] ?? true,
                            ]);

                            $successCount = count($results['success']);
                            $failedCount = count($results['failed']);

                            if ($failedCount === 0) {
                                Notification::make()
                                    ->title("Approved {$successCount} Applications")
                                    ->body('All selected applications have been approved successfully.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("Approved {$successCount} Applications")
                                    ->body("{$failedCount} applications failed to process. Check logs for details.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('workflow_reject')
                        ->label('Reject Applications')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Selected Applications')
                        ->modalDescription('This will reject all selected applications. This action cannot be undone.')
                        ->form([
                            Forms\Components\Select::make('category')
                                ->label('Rejection Category')
                                ->options([
                                    'incomplete_documents' => 'Incomplete Documents',
                                    'financial_criteria' => 'Does Not Meet Financial Criteria',
                                    'verification_failed' => 'Verification Failed',
                                    'duplicate_application' => 'Duplicate Application',
                                    'other' => 'Other',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->placeholder('Please provide a detailed reason for rejection...')
                                ->required()
                                ->rows(3),
                            Forms\Components\Toggle::make('send_notifications')
                                ->label('Send notifications to applicants')
                                ->default(true),
                        ])
                        ->action(function ($records, array $data) {
                            $workflowService = app(ApplicationWorkflowService::class);
                            $applicationIds = $records->pluck('id')->toArray();

                            $results = $workflowService->processBulkApplications($applicationIds, 'reject', [
                                'reason' => $data['reason'],
                                'category' => $data['category'],
                                'send_notifications' => $data['send_notifications'] ?? true,
                            ]);

                            $successCount = count($results['success']);
                            $failedCount = count($results['failed']);

                            if ($failedCount === 0) {
                                Notification::make()
                                    ->title("Rejected {$successCount} Applications")
                                    ->body('All selected applications have been rejected.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("Rejected {$successCount} Applications")
                                    ->body("{$failedCount} applications failed to process. Check logs for details.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'view' => Pages\ViewApplication::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())
            ->count();
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ApplicationStatsWidget::class,
            Widgets\RecentApplicationsWidget::class,
            Widgets\PendingApprovalsWidget::class,
        ];
    }
}
