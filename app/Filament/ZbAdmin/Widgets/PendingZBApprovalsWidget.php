<?php

namespace App\Filament\ZbAdmin\Widgets;

use App\Models\ApplicationState;
use App\Services\ZBStatusService;
use App\Services\SMSService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PendingZBApprovalsWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending ZB Loan Approvals';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApplicationState::query()
                    ->where('current_step', 'in_review')
                    // Only ZB applications (not SSB)
                    ->where(function ($query) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.wantsAccount')) = 'true'")
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'");
                    })
                    ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'")
                    ->orderBy('created_at', 'asc') // Oldest first
            )
            ->columns([
                TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data;
                        $responses = $formData['formResponses'] ?? $formData;
                        $firstName = $responses['firstName'] ?? $responses['forenames'] ?? '';
                        $surname = $responses['surname'] ?? $responses['lastName'] ?? '';
                        return trim("{$firstName} {$surname}") ?: 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.firstName'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.surname'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.firstName'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.surname'))) LIKE LOWER(?)", ["%{$search}%"]);
                        });
                    }),

                TextColumn::make('form_type')
                    ->label('Form Type')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data;
                        if ($formData['hasAccount'] ?? false) {
                            return 'Account Holder Loan';
                        }
                        if ($formData['wantsAccount'] ?? false) {
                            return 'New Account Opening';
                        }
                        return 'ZB Loan';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Account Holder Loan' => 'info',
                        'New Account Opening' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->getStateUsing(function ($record) {
                        $amount = $record->form_data['finalPrice'] 
                                ?? $record->form_data['grossLoan'] 
                                ?? $record->form_data['loanAmount'] 
                                ?? 0;
                        return '$' . number_format($amount, 2);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.finalPrice')), 0) AS DECIMAL(10,2)) {$direction}");
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                TextColumn::make('days_pending')
                    ->label('Days Pending')
                    ->getStateUsing(fn ($record) => $record->created_at->diffInDays(now()))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 5 => 'danger',
                        $state >= 3 => 'warning',
                        default => 'success',
                    }),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve ZB Loan Application')
                    ->modalDescription('This will approve the loan application and notify the client.')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Approval Notes')
                            ->placeholder('Optional notes for the approval...')
                            ->rows(3),
                    ])
                    ->action(function (ApplicationState $record, array $data) {
                        try {
                            $zbService = app(ZBStatusService::class);
                            $zbService->processApproved($record, $data['notes'] ?? '');

                            Notification::make()
                                ->title('Application Approved')
                                ->body("Reference: {$record->reference_code}")
                                ->success()
                                ->send();

                            Log::info('ZB Admin approved application', [
                                'session_id' => $record->session_id,
                                'reference_code' => $record->reference_code,
                                'approved_by' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Approval Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject ZB Loan Application')
                    ->form([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->options([
                                'credit_check_poor' => 'Poor Credit Check (Blacklist Report Offered)',
                                'salary_not_regular' => 'Salary Not Regular',
                                'insufficient_salary' => 'Insufficient Salary (Period Adjustment)',
                                'incomplete_documents' => 'Incomplete Documents',
                                'verification_failed' => 'Verification Failed',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('recommended_period')
                            ->label('Recommended Period (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->visible(fn (Forms\Get $get) => $get('rejection_reason') === 'insufficient_salary')
                            ->required(fn (Forms\Get $get) => $get('rejection_reason') === 'insufficient_salary'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Rejection Details')
                            ->placeholder('Provide details for the rejection...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ApplicationState $record, array $data) {
                        try {
                            $zbService = app(ZBStatusService::class);

                            switch ($data['rejection_reason']) {
                                case 'credit_check_poor':
                                    $zbService->processCreditCheckPoor($record, $data['notes']);
                                    break;
                                case 'salary_not_regular':
                                    $zbService->processSalaryNotRegular($record, $data['notes']);
                                    break;
                                case 'insufficient_salary':
                                    $zbService->processInsufficientSalary(
                                        $record,
                                        $data['recommended_period'],
                                        $data['notes']
                                    );
                                    break;
                                default:
                                    // Generic rejection via status update
                                    $record->update([
                                        'current_step' => 'rejected',
                                        'metadata' => array_merge($record->metadata ?? [], [
                                            'rejection_reason' => $data['rejection_reason'],
                                            'rejection_notes' => $data['notes'],
                                            'rejected_by' => auth()->id(),
                                            'rejected_at' => now()->toIso8601String(),
                                        ]),
                                    ]);
                            }

                            Notification::make()
                                ->title('Application Rejected')
                                ->body("Reference: {$record->reference_code}")
                                ->warning()
                                ->send();

                            Log::info('ZB Admin rejected application', [
                                'session_id' => $record->session_id,
                                'reference_code' => $record->reference_code,
                                'reason' => $data['rejection_reason'],
                                'rejected_by' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Rejection Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

            ])
            ->emptyStateHeading('No Pending Applications')
            ->emptyStateDescription('All ZB loan applications have been processed.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }
}
