<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\ZbApplicationResource\Pages;
use App\Models\ApplicationState;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\PDFGeneratorService;
use App\Services\ZBStatusService;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Facades\Filament;

class ZbApplicationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'ZB Loan Applications';

    protected static ?string $navigationGroup = 'Loan Management';

    public static function getEloquentQuery(): Builder
    {
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
        $user = Filament::auth()->user();

        $query = parent::getEloquentQuery()
            ->where(function ($query) use ($isPgsql) {
                // Only ZB applications (has account or wants account)
                if ($isPgsql) {
                    $query->whereRaw("form_data->>'hasAccount' = 'true'")
                          ->orWhereRaw("form_data->>'wantsAccount' = 'true'");
                } else {
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.wantsAccount')) = 'true'");
                }
            })
            ->where(function ($query) use ($isPgsql) {
                // Exclude SSB applications
                if ($isPgsql) {
                    $query->whereRaw("COALESCE(form_data->>'employer', '') != 'government-ssb'");
                } else {
                    $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'");
                }
            })
            // Exclude agent applications
            ->where('current_step', 'not like', 'agent_%');

        // Branch-scoping for Qupa Admin users
        if ($user && $user->isQupaAdmin()) {
            if ($user->isLoanOfficer() || $user->isBranchManager()) {
                // Loan Officers and Branch Managers: see only their branch's applications
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_branch_id', $user->branch_id)
                      ->orWhere('qupa_admin_id', $user->id);
                });
            } elseif ($user->isVlc()) {
                // VLC doesn't see ZB applications (they handle SSB exports)
                $query->whereRaw('1 = 0');
            }
            // Qupa Management sees everything — no additional filter
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        // Minimal readonly form for viewing details if needed
        return $form->schema([
             Forms\Components\Section::make('Application Details')
                ->schema([
                    Forms\Components\TextInput::make('session_id')->disabled(),
                    Forms\Components\TextInput::make('reference_code')->disabled(),
                    Forms\Components\ViewField::make('form_data')
                        ->view('filament.forms.components.application-data'),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')->label('Ref Code')->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) =>
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['lastName'] ?? ''))
                    ),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (Model $record) => '$' . number_format($record->form_data['finalPrice'] ?? 0)),
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['completed', 'approved']),
                        'warning' => fn ($state) => in_array($state, ['in_review', 'processing', 'pending_verification', 'sent_for_checks']),
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\BadgeColumn::make('check_status')
                    ->label('FCB Check')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['S', 'A']),
                        'danger' => fn ($state): bool => in_array($state, ['F', 'B']),
                        'warning' => 'P',
                    ])
                    ->formatStateUsing(function ($state, Model $record) {
                        $type = $record->check_type ?? 'Check';
                        $labels = ['S' => 'Success', 'F' => 'Failure', 'A' => 'Approved', 'B' => 'Blacklisted', 'P' => 'Pending'];
                        return ($type ? "$type: " : "") . ($labels[$state] ?? $state ?? 'N/A');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedBranch.name')
                    ->label('Branch')
                    ->default('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('qupaAdmin.name')
                    ->label('Officer')
                    ->default('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('success')
                    ->action(function (Model $record) {
                        try {
                            $pdfGenerator = app(PDFGeneratorService::class);
                            $pdfPath = $pdfGenerator->generateApplicationPDF($record);
                            Notification::make()->title('PDF Generated')->success()->send();
                            return redirect()->route('application.pdf.view', $record->session_id);
                        } catch (\Exception $e) {
                            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    // Only Branch Managers, Management, and zb_admin can update status
                    ->visible(function () use ($user) {
                        if (!$user) return false;
                        if ($user->role === User::ROLE_ZB_ADMIN || $user->role === User::ROLE_SUPER_ADMIN) return true;
                        if ($user->isBranchManager() || $user->isQupaManagement()) return true;
                        return false;
                    })
                    ->form([
                        Forms\Components\Select::make('zb_action')
                            ->label('Action')
                            ->options([
                                'credit_check_good' => 'Approve (Credit Good)',
                                'credit_check_poor' => 'Reject (Credit Poor)',
                                'salary_not_regular' => 'Reject (Salary Not Regular)',
                                'insufficient_salary' => 'Reject (Insufficient Salary)',
                                'approved' => 'Final Approval',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('recommended_period')
                            ->visible(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                            ->required(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                            ->numeric(),
                        Forms\Components\Textarea::make('notes')
                    ])
                    ->action(function (array $data, Model $record) {
                        $zbService = app(ZBStatusService::class);
                        try {
                            switch ($data['zb_action']) {
                                case 'credit_check_good': $zbService->processCreditCheckGood($record, $data['notes'] ?? ''); break;
                                case 'credit_check_poor': $zbService->processCreditCheckPoor($record, $data['notes'] ?? ''); break;
                                case 'salary_not_regular': $zbService->processSalaryNotRegular($record, $data['notes'] ?? ''); break;
                                case 'insufficient_salary': $zbService->processInsufficientSalary($record, $data['recommended_period'], $data['notes'] ?? ''); break;
                                case 'approved': $zbService->processApproved($record); break;
                            }
                            Notification::make()->title('Status Updated')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // Refer to Branch — visible to Qupa Management only
                Action::make('refer_to_branch')
                    ->label('Refer to Branch')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(function (Model $record) {
                        $user = Filament::auth()->user();
                        if (!$user) return false;
                        return ($user->isQupaManagement() || $user->role === User::ROLE_SUPER_ADMIN || $user->role === User::ROLE_ZB_ADMIN || $user->email === 'zbadmin@bancosystem.fly.dev')
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
            ])
            
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_ssb_loans')
                        ->label('Export SSB Loans (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function () {
                            $csvService = app(\App\Services\CsvExportService::class);

                            $query = ApplicationState::query()
                                ->where(function ($query) {
                                    $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
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
                                        $application->approved_at ? $application->approved_at->format('Y-m-d') : ($application->updated_at ? $application->updated_at->format('Y-m-d') : date('Y-m-d')),
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

                    Tables\Actions\BulkAction::make('export_zb_loans')
                        ->label('Export ZB Loans (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function () {
                            $csvService = app(\App\Services\CsvExportService::class);

                            $query = ApplicationState::query()
                                ->where(function ($query) {
                                    $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                                    if ($isPgsql) {
                                        $query->whereRaw("form_data->>'hasAccount' = 'true'")
                                              ->orWhereRaw("form_data->>'wantsAccount' = 'true'");
                                    } else {
                                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'")
                                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.wantsAccount')) = 'true'");
                                    }
                                })
                                ->where(function ($query) {
                                    $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                                    if ($isPgsql) {
                                        $query->whereRaw("COALESCE(form_data->>'employer', '') != 'government-ssb'");
                                    } else {
                                        $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'");
                                    }
                                })
                                ->orderBy('updated_at', 'desc');

                            $headings = [
                                'DATE', 'BRANCH', 'SURNAME', 'NAME', 'EC NUMBER', 'ID NUMBER',
                                'PRODUCT', 'PRICE', 'INSTALLMENT', 'PERIOD', 'MOBILE', 'ADDRESS', 'NEXT OF KIN'
                            ];

                            return $csvService->download(
                                'zb_loans_export_' . date('Y-m-d') . '.csv',
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
                                        $application->approved_at ? $application->approved_at->format('Y-m-d') : ($application->updated_at ? $application->updated_at->format('Y-m-d') : date('Y-m-d')),
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
            'index' => Pages\ListZbApplications::route('/'),
        ];
    }
}
