<?php

namespace App\Filament\Pages;

use App\Models\ApplicationState;
use App\Jobs\SendSmsJob;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class IncompleteRegistrations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Incomplete Registrations';
    protected static ?string $title = 'Incomplete Registrations';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.incomplete-registrations';

    /**
     * Steps that indicate an incomplete application
     */
    protected static array $incompleteSteps = [
        'product_selection',
        'form_filling',
        'document_upload',
        'started',
        'pending',
    ];

    public function getStats(): array
    {
        $total = ApplicationState::query()
            ->whereIn('current_step', self::$incompleteSteps)
            ->count();

        $lastWeek = ApplicationState::query()
            ->whereIn('current_step', self::$incompleteSteps)
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        $lastMonth = ApplicationState::query()
            ->whereIn('current_step', self::$incompleteSteps)
            ->where('created_at', '>=', now()->subMonth())
            ->count();

        return [
            ['label' => 'Total Incomplete', 'count' => $total, 'color' => 'text-red-600', 'bg' => 'bg-red-100 dark:bg-red-900'],
            ['label' => 'Last 7 Days', 'count' => $lastWeek, 'color' => 'text-amber-600', 'bg' => 'bg-amber-100 dark:bg-amber-900'],
            ['label' => 'Last 30 Days', 'count' => $lastMonth, 'color' => 'text-blue-600', 'bg' => 'bg-blue-100 dark:bg-blue-900'],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApplicationState::query()
                    ->whereIn('current_step', self::$incompleteSteps)
            )
            ->columns([
                TextColumn::make('application_number')
                    ->label('App #')
                    ->getStateUsing(fn ($record) => $record->application_number)
                    ->searchable(false),

                TextColumn::make('client_name')
                    ->label('Name')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data ?? [];
                        $formResponses = $formData['formResponses'] ?? [];
                        $firstName = $formResponses['firstName'] ?? $formData['firstName'] ?? '';
                        $surname = $formResponses['surname'] ?? $formResponses['lastName'] ?? $formData['surname'] ?? '';
                        return trim("{$firstName} {$surname}") ?: '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        if ($isPgsql) {
                            return $query->where(function ($q) use ($search) {
                                $q->whereRaw("form_data->'formResponses'->>'firstName' ILIKE ?", ["%{$search}%"])
                                  ->orWhereRaw("form_data->'formResponses'->>'surname' ILIKE ?", ["%{$search}%"]);
                            });
                        }
                        return $query->where(function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.firstName')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.surname')) LIKE ?", ["%{$search}%"]);
                        });
                    }),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data ?? [];
                        $formResponses = $formData['formResponses'] ?? [];
                        return $formResponses['mobile']
                            ?? $formResponses['phoneNumber']
                            ?? $formResponses['cellNumber']
                            ?? $formData['phone']
                            ?? $record->user_identifier
                            ?? '—';
                    }),

                TextColumn::make('current_step')
                    ->label('Stopped At')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'product_selection' => 'Product Selection',
                        'form_filling' => 'Form Filling',
                        'document_upload' => 'Document Upload',
                        'started' => 'Started',
                        'pending' => 'Pending',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'product_selection' => 'danger',
                        'form_filling' => 'warning',
                        'document_upload' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('current_step')
                    ->label('Stopped At')
                    ->options([
                        'product_selection' => 'Product Selection',
                        'form_filling' => 'Form Filling',
                        'document_upload' => 'Document Upload',
                        'started' => 'Started',
                        'pending' => 'Pending',
                    ]),

                SelectFilter::make('age')
                    ->label('Registration Age')
                    ->options([
                        '7' => 'Last 7 days',
                        '30' => 'Last 30 days',
                        '90' => 'Last 3 months',
                        'older' => 'Older than 3 months',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return match ($data['value']) {
                            '7' => $query->where('created_at', '>=', now()->subDays(7)),
                            '30' => $query->where('created_at', '>=', now()->subDays(30)),
                            '90' => $query->where('created_at', '>=', now()->subDays(90)),
                            'older' => $query->where('created_at', '<', now()->subDays(90)),
                            default => $query,
                        };
                    }),
            ])
            ->bulkActions([
                BulkAction::make('send_sms_reminder')
                    ->label('Send SMS Reminder')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send SMS Reminder')
                    ->modalDescription('This will send an SMS reminder to all selected incomplete applicants, encouraging them to complete their application.')
                    ->action(function (Collection $records) {
                        $sentCount = 0;
                        $failedCount = 0;

                        foreach ($records as $record) {
                            $formData = $record->form_data ?? [];
                            $formResponses = $formData['formResponses'] ?? [];
                            $phone = $formResponses['mobile']
                                ?? $formResponses['phoneNumber']
                                ?? $formResponses['cellNumber']
                                ?? $formData['phone']
                                ?? $record->user_identifier
                                ?? null;

                            $firstName = $formResponses['firstName'] ?? $formData['firstName'] ?? 'Customer';

                            if (!$phone || strlen(preg_replace('/\D/', '', $phone)) < 9) {
                                $failedCount++;
                                continue;
                            }

                            $message = "Dear {$firstName}, you started a ZB BancoSystem loan application but haven't completed it yet. Log in to finish your application or call us for help. Thank you!";

                            try {
                                SendSmsJob::dispatch($phone, $message, $record->reference_code);
                                $sentCount++;
                            } catch (\Exception $e) {
                                Log::error('Failed to dispatch SMS reminder', [
                                    'session_id' => $record->session_id,
                                    'phone' => $phone,
                                    'error' => $e->getMessage(),
                                ]);
                                $failedCount++;
                            }
                        }

                        $body = "Sent to {$sentCount} applicant(s).";
                        if ($failedCount > 0) {
                            $body .= " {$failedCount} failed (no phone number).";
                        }

                        Notification::make()
                            ->title('SMS Reminders Dispatched')
                            ->body($body)
                            ->success()
                            ->send();

                        Log::info('Bulk SMS reminders dispatched for incomplete registrations', [
                            'sent' => $sentCount,
                            'failed' => $failedCount,
                            'admin_id' => auth()->id(),
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
