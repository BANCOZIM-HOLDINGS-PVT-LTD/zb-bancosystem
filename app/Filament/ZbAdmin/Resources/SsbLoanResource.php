<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\SsbLoanResource\Pages;
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

class SsbLoanResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'SSB Loan Applications';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
        $user = Filament::auth()->user();

        $query = parent::getEloquentQuery()
            ->where(function ($query) use ($isPgsql) {
                if ($isPgsql) {
                    $query->whereRaw("form_data->>'employer' = 'government-ssb'")
                          ->orWhereRaw("metadata->>'workflow_type' = 'ssb'");
                } else {
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'government-ssb'")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.workflow_type')) = 'ssb'");
                }
            })
            ->orderBy('created_at', 'desc');

        // Branch-scoping for Qupa Admin users
        if ($user && $user->isQupaAdmin()) {
            if ($user->isLoanOfficer() || $user->isBranchManager()) {
                // Loan Officers and Branch Managers: see only their branch's SSB applications
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_branch_id', $user->branch_id)
                      ->orWhere('qupa_admin_id', $user->id);
                });
            }
            // VLC and Qupa Management see all SSB applications — no filter
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
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference')
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

                Tables\Columns\TextColumn::make('ec_number')
                    ->label('EC Number')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data;
                        $responses = $formData['formResponses'] ?? $formData;
                        return $responses['ecNumber'] ?? 'N/A';
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

                Tables\Columns\TextColumn::make('qupaAdmin.name')
                    ->label('Officer')
                    ->default('—')
                    ->toggleable(),

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

                // Assign to Branch — visible to Qupa Management only
                Action::make('assign_to_branch')
                    ->label('Assign Branch')
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary')
                    ->visible(function (Model $record) use ($user) {
                        if (!$user) return false;
                        return ($user->isQupaManagement() || $user->role === User::ROLE_SUPER_ADMIN)
                            && !$record->assigned_branch_id;
                    })
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Assign to Branch')
                            ->options(Branch::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Model $record) {
                        $record->update(['assigned_branch_id' => $data['branch_id']]);
                        $branch = Branch::find($data['branch_id']);
                        Notification::make()
                            ->title('Application Assigned')
                            ->body("Assigned to {$branch->name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // VLC CSV Export for SSB loans
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_ssb_csv')
                        ->label('Export SSB (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(function () use ($user) {
                            if (!$user) return false;
                            return $user->isVlc() || $user->isQupaManagement()
                                || $user->role === User::ROLE_ZB_ADMIN
                                || $user->role === User::ROLE_SUPER_ADMIN;
                        })
                        ->action(function () {
                            $csvService = app(\App\Services\CsvExportService::class);
                            $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';

                            $query = ApplicationState::query()
                                ->where(function ($query) use ($isPgsql) {
                                    if ($isPgsql) {
                                        $query->whereRaw("COALESCE(form_data->>'employer', '') = 'government-ssb'");
                                    } else {
                                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'government-ssb'");
                                    }
                                })
                                ->orderBy('updated_at', 'desc');

                            $headings = [
                                'DATE', 'BRANCH', 'SURNAME', 'NAME', 'EC NUMBER', 'ID NUMBER',
                                'PRODUCT', 'PRICE', 'INSTALLMENT', 'PERIOD', 'MOBILE', 'ADDRESS', 'NEXT OF KIN'
                            ];

                            return $csvService->download(
                                'ssb_loans_export_' . date('Y-m-d') . '.csv',
                                $headings,
                                $query,
                                function ($application) {
                                    $formData = $application->form_data;
                                    $formResponses = $formData['formResponses'] ?? [];

                                    $getAddressLine = function ($addressData) {
                                        if (empty($addressData)) return '';
                                        if (is_string($addressData) && (str_starts_with($addressData, '{') || str_starts_with($addressData, '['))) {
                                            $decoded = json_decode($addressData, true);
                                            return $decoded['addressLine'] ?? $addressData;
                                        }
                                        if (is_array($addressData)) return $addressData['addressLine'] ?? '';
                                        return $addressData;
                                    };

                                    $getNextOfKin = function ($responses) {
                                        $spouseDetails = $responses['spouseDetails'] ?? [];
                                        if (empty($spouseDetails)) return $responses['nextOfKinName'] ?? '';
                                        if (is_string($spouseDetails)) $spouseDetails = json_decode($spouseDetails, true) ?? [];
                                        if (is_array($spouseDetails) && !empty($spouseDetails)) {
                                            foreach ($spouseDetails as $kin) {
                                                if (!empty($kin['fullName'])) return $kin['fullName'];
                                            }
                                        }
                                        return $responses['nextOfKinName'] ?? '';
                                    };

                                    $branchName = $application->assignedBranch?->name ?? 'Unassigned';

                                    return [
                                        $application->updated_at ? $application->updated_at->format('Y-m-d') : date('Y-m-d'),
                                        $branchName,
                                        $formResponses['surname'] ?? $formResponses['lastName'] ?? '',
                                        $formResponses['firstName'] ?? '',
                                        $formResponses['employmentNumber'] ?? $formResponses['employeeNumber'] ?? $formResponses['ecNumber'] ?? '',
                                        $formResponses['nationalIdNumber'] ?? '',
                                        $formData['productName'] ?? $formResponses['productName'] ?? '',
                                        $formResponses['loanAmount'] ?? $formData['finalPrice'] ?? '',
                                        $formResponses['monthlyPayment'] ?? $formData['monthlyInstallment'] ?? '',
                                        $formResponses['loanTenure'] ?? $formData['creditDuration'] ?? '',
                                        $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? '',
                                        $getAddressLine($formResponses['residentialAddress'] ?? ''),
                                        $getNextOfKin($formResponses),
                                    ];
                                }
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSsbLoans::route('/'),
            'view' => Pages\ViewSsbLoan::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
