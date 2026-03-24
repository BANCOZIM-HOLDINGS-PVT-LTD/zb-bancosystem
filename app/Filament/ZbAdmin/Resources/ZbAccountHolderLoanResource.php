<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\ZbAccountHolderLoanResource\Pages;
use App\Models\ApplicationState;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;

class ZbAccountHolderLoanResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'ZB Account Holder Loans';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
        $user = Filament::auth()->user();

        $query = parent::getEloquentQuery()
            ->where(function ($query) use ($isPgsql) {
                // Account Holders: hasAccount = true OR wantsAccount = false (implicitly) 
                // but usually we check for employer NOT being SSB and hasAccount being true
                if ($isPgsql) {
                    $query->whereRaw("form_data->>'hasAccount' = 'true'")
                          ->whereRaw("COALESCE(form_data->>'employer', '') != 'government-ssb'");
                } else {
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'")
                          ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'");
                }
            })
            ->orderBy('created_at', 'desc');

        // Branch-scoping for Qupa Admin users
        if ($user && $user->isQupaAdmin()) {
            if ($user->isLoanOfficer() || $user->isBranchManager()) {
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_branch_id', $user->branch_id)
                      ->orWhere('qupa_admin_id', $user->id);
                });
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled(),
                        Forms\Components\TextInput::make('current_step')
                            ->label('Status')
                            ->disabled(),
                        Forms\Components\ViewField::make('form_data')
                            ->view('filament.forms.components.application-data')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App No')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('National ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data;
                        $responses = $formData['formResponses'] ?? $formData;
                        $firstName = $responses['firstName'] ?? '';
                        $lastName = $responses['lastName'] ?? $responses['surname'] ?? '';
                        return trim("{$firstName} {$lastName}") ?: 'N/A';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->getStateUsing(function ($record) {
                        $amount = $record->form_data['finalPrice']
                                ?? $record->form_data['grossLoan']
                                ?? 0;
                        return '$' . number_format($amount, 2);
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => 'approved',
                        'warning' => 'in_review',
                        'danger' => 'rejected',
                        'primary' => 'pending',
                    ]),

                Tables\Columns\TextColumn::make('assignedBranch.name')
                    ->label('Branch')
                    ->default('Unassigned')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_step')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in_review' => 'In Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_branch_id')
                    ->label('Branch')
                    ->options(Branch::active()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('refer_to_branch')
                    ->label('Refer to Branch')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(function (Model $record) {
                        $user = Filament::auth()->user();
                        if (!$user) return false;
                        return ($user->isQupaManagement() || $user->role === User::ROLE_SUPER_ADMIN || $user->role === User::ROLE_ZB_ADMIN)
                            && !$record->assigned_branch_id;
                    })
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Refer to Branch')
                            ->options(Branch::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Model $record) {
                        $record->update(['assigned_branch_id' => $data['branch_id']]);
                        $branch = Branch::find($data['branch_id']);
                        Notification::make()
                            ->title('Application Referred')
                            ->body("Referred to {$branch->name}")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZbAccountHolderLoans::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
