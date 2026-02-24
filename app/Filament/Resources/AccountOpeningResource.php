<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountOpeningResource\Pages;
use App\Models\AccountOpening;
use App\Services\AccountOpeningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class AccountOpeningResource extends Resource
{
    protected static ?string $model = AccountOpening::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Account Openings';

    protected static ?string $navigationGroup = 'ZB Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account Opening Details')
                ->schema([
                    Forms\Components\TextInput::make('reference_code')
                        ->label('Reference Code')
                        ->disabled(),
                    Forms\Components\TextInput::make('user_identifier')
                        ->label('National ID / Phone')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'referred' => 'Referred to Branch',
                            'account_opened' => 'Account Opened',
                            'rejected' => 'Rejected',
                        ])
                        ->disabled(),
                    Forms\Components\TextInput::make('zb_account_number')
                        ->label('ZB Account Number')
                        ->disabled(),
                    Forms\Components\TextInput::make('referred_to_branch')
                        ->label('Referred To Branch')
                        ->disabled(),
                    Forms\Components\ViewField::make('form_data')
                        ->view('filament.forms.components.application-data'),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch')
                    ->label('Branch')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.serviceCenter')) {$direction}");
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.serviceCenter')) LIKE ?", ["%{$search}%"]);
                    }),
                Tables\Columns\TextColumn::make('zb_account_number')
                    ->label('Account #')
                    ->searchable()
                    ->placeholder('Not assigned'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'referred',
                        'success' => 'account_opened',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'referred' => 'Referred to Branch',
                        'account_opened' => 'Account Opened',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('branch')
                    ->label('Branch')
                    ->options(fn () => collect(config('branches.list', []))->keys()->mapWithKeys(fn ($b) => [$b => $b])->toArray())
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.serviceCenter')) = ?", [$data['value']]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('refer_to_branch')
                    ->label('Refer to Branch')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Model $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Select::make('branch')
                            ->label('Branch')
                            ->options(fn () => collect(config('branches.list', []))->keys()->mapWithKeys(fn ($b) => [$b => $b])->toArray())
                            ->default(fn (Model $record) => $record->branch)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $email = config("branches.list.{$state}.email", '');
                                $set('email', $email);
                            }),
                        Forms\Components\TextInput::make('email')
                            ->label('Branch Email')
                            ->email()
                            ->default(fn (Model $record) => config("branches.list.{$record->branch}.email", ''))
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $service = app(AccountOpeningService::class);
                        $count = $service->referToBranch(
                            collect([$record]),
                            $data['branch'],
                            $data['email']
                        );

                        Notification::make()
                            ->title("Referred to {$data['branch']}")
                            ->body("Application emailed to {$data['email']}")
                            ->success()
                            ->send();
                    }),

                Action::make('mark_opened')
                    ->label('Mark Account Opened')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Model $record) => in_array($record->status, ['pending', 'referred']))
                    ->form([
                        Forms\Components\TextInput::make('account_number')
                            ->label('ZB Account Number')
                            ->required()
                            ->maxLength(50),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->markAsOpened($data['account_number']);
                        
                        $service = app(AccountOpeningService::class);
                        $service->sendAccountOpenedSMS($record);
                        
                        Notification::make()
                            ->title('Account Marked as Opened')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Model $record) => in_array($record->status, ['pending', 'referred']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Model $record, array $data) {
                        $record->reject($data['reason']);
                        
                        $service = app(AccountOpeningService::class);
                        $service->sendRejectionSMS($record);
                        
                        Notification::make()
                            ->title('Application Rejected')
                            ->success()
                            ->send();
                    }),

                Action::make('send_sms')
                    ->label('Send SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('SMS Message')
                            ->required()
                            ->maxLength(160)
                            ->helperText('Maximum 160 characters'),
                    ])
                    ->action(function (Model $record, array $data) {
                        $service = app(AccountOpeningService::class);
                        $service->sendCustomSMS($record, $data['message']);
                        
                        Notification::make()
                            ->title('SMS Sent')
                            ->success()
                            ->send();
                    }),

                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('gray')
                    ->action(function (Model $record) {
                        try {
                            $service = app(AccountOpeningService::class);
                            $pdfPath = $service->generatePDF($record);
                            
                            Notification::make()
                                ->title('PDF Generated')
                                ->success()
                                ->send();
                                
                            return response()->download(storage_path('app/' . $pdfPath));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('PDF Generation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('archive')
                    ->label('Clear Record')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Model $record) => in_array($record->status, ['account_opened', 'rejected']))
                    ->requiresConfirmation()
                    ->modalHeading('Clear Record')
                    ->modalDescription('This will remove this account opening from the active list. The record will be archived.')
                    ->action(function (Model $record) {
                        $service = app(AccountOpeningService::class);
                        $service->archiveRecord($record);
                        
                        Notification::make()
                            ->title('Record Cleared')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_refer')
                    ->label('Refer Selected to Branch')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('branch')
                            ->label('Branch')
                            ->options(fn () => collect(config('branches.list', []))->keys()->mapWithKeys(fn ($b) => [$b => $b])->toArray())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $email = config("branches.list.{$state}.email", '');
                                $set('email', $email);
                            }),
                        Forms\Components\TextInput::make('email')
                            ->label('Branch Email')
                            ->email()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $pendingRecords = $records->filter(fn ($r) => $r->status === 'pending');
                        
                        if ($pendingRecords->isEmpty()) {
                            Notification::make()
                                ->title('No pending records selected')
                                ->warning()
                                ->send();
                            return;
                        }

                        $service = app(AccountOpeningService::class);
                        $count = $service->referToBranch($pendingRecords, $data['branch'], $data['email']);

                        Notification::make()
                            ->title("Referred {$count} applications to {$data['branch']}")
                            ->body("PDFs emailed to {$data['email']}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountOpenings::route('/'),
            'view' => Pages\ViewAccountOpening::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return static::getModel()::where('status', 'pending')->count();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
