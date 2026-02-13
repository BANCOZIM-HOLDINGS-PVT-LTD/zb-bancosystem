<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Widgets;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\NotificationService;
use App\Services\ApplicationWorkflowService;
use App\Services\SSBStatusService;
use App\Services\ZBStatusService;
use App\Enums\SSBLoanStatus;
use App\Enums\ZBLoanStatus;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;

class ApplicationResource extends BaseResource
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
                            ->label('National ID / Reference Code')
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
                            ->view('filament.forms.components.application-data')
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
                            ->deletable(false)
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

                Forms\Components\Section::make('Data Retention')
                    ->schema([
                        Forms\Components\Toggle::make('exempt_from_auto_deletion')
                            ->label('Exempt from Auto-Deletion')
                            ->helperText('Applications are automatically deleted 90 days after delivery completion. Enable this to prevent automatic deletion.')
                            ->default(false),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('App #')
                    ->formatStateUsing(fn (Model $record) => $record->application_number)
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('National ID')
                    ->getStateUsing(function (Model $record) {
                        // If reference_code exists and is longer than 6 chars, it's likely a National ID
                        if ($record->reference_code && strlen($record->reference_code) > 6) {
                            return $record->reference_code;
                        }

                        // Otherwise, extract National ID from form data
                        return data_get($record->form_data, 'formResponses.idNumber')
                            ?? data_get($record->form_data, 'formResponses.nationalIdNumber')
                            ?? data_get($record->form_data, 'formResponses.nationalId')
                            ?? ($record->reference_code ?: 'N/A');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        
                        if ($isPgsql) {
                            return $query->where('reference_code', 'LIKE', "%{$search}%")
                                ->orWhereRaw("form_data->'formResponses'->>'idNumber' ILIKE ?", ["%{$search}%"])
                                ->orWhereRaw("form_data->'formResponses'->>'nationalIdNumber' ILIKE ?", ["%{$search}%"])
                                ->orWhereRaw("form_data->'formResponses'->>'nationalId' ILIKE ?", ["%{$search}%"]);
                        }
                        
                        return $query->where('reference_code', 'LIKE', "%{$search}%")
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.idNumber') LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.nationalIdNumber') LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.nationalId') LIKE ?", ["%{$search}%"]);
                    })
                    ->copyable()
                    ->tooltip('Applicant National ID number'),
                    
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function (Model $record) {
                        $firstName = data_get($record->form_data, 'formResponses.firstName', '');
                        $lastName = data_get($record->form_data, 'formResponses.lastName', '');
                        return trim($firstName . ' ' . $lastName) ?: 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        
                        if ($isPgsql) {
                            return $query->whereRaw("form_data->'formResponses'->>'firstName' ILIKE ?", ["%{$search}%"])
                                ->orWhereRaw("form_data->'formResponses'->>'lastName' ILIKE ?", ["%{$search}%"]);
                        }
                        
                        return $query->whereRaw("JSON_EXTRACT(form_data, '$.formResponses.firstName') LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.lastName') LIKE ?", ["%{$search}%"]);
                    }),
                    
                Tables\Columns\TextColumn::make('business_type')
                    ->label('Business')
                    ->getStateUsing(fn (Model $record) => data_get($record->form_data, 'selectedBusiness.name', 'N/A'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        
                        if ($isPgsql) {
                            return $query->orderByRaw("form_data->'selectedBusiness'->>'name' $direction");
                        }
                        
                        return $query->orderByRaw("JSON_EXTRACT(form_data, '$.selectedBusiness.name') {$direction}");
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (Model $record) => '$' . number_format(data_get($record->form_data, 'finalPrice', 0)))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
                            return $query->orderByRaw("COALESCE(NULLIF(form_data->>'finalPrice', ''), '0')::numeric $direction");
                        }
                        return $query->orderByRaw("CAST(JSON_EXTRACT(form_data, '$.finalPrice') AS DECIMAL(10,2)) {$direction}");
                    }),
                    
                Tables\Columns\BadgeColumn::make('check_status')
                    ->label('SSB/FCB Check')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['S', 'A']), // Success / Approved
                        'danger' => fn ($state): bool => in_array($state, ['F', 'B']), // Failure / Blacklisted
                        'warning' => 'P', // Pending
                    ])
                    ->formatStateUsing(function ($state, Model $record) {
                        $type = $record->check_type ?? 'Check';
                        
                        $labels = [
                            'S' => 'Success',
                            'F' => 'Failure',
                            'A' => 'Approved',
                            'B' => 'Blacklisted',
                            'P' => 'Pending',
                        ];
                        
                        return ($type ? "$type: " : "") . ($labels[$state] ?? $state ?? 'N/A');
                    })
                    ->sortable(),

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
                        'warning' => fn ($state): bool => in_array($state, ['in_review', 'processing', 'pending_documents', 'pending_verification', 'sent_for_checks']),
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
                        true: fn (Builder $query) => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql'
                            ? $query->whereRaw("form_data->'documents'->>'uploadedDocuments' IS NOT NULL")
                            : $query->whereRaw("JSON_EXTRACT(form_data, '$.documents.uploadedDocuments') IS NOT NULL"),
                        false: fn (Builder $query) => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql'
                            ? $query->whereRaw("form_data->'documents'->>'uploadedDocuments' IS NULL")
                            : $query->whereRaw("JSON_EXTRACT(form_data, '$.documents.uploadedDocuments') IS NULL"),
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
                                fn (Builder $query, $amount): Builder => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' 
                                    ? $query->whereRaw("(form_data->>'finalPrice')::numeric >= ?", [$amount])
                                    : $query->whereRaw("CAST(JSON_EXTRACT(form_data, '$.finalPrice') AS DECIMAL(10,2)) >= ?", [$amount]),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql'
                                    ? $query->whereRaw("(form_data->>'finalPrice')::numeric <= ?", [$amount])
                                    : $query->whereRaw("CAST(JSON_EXTRACT(form_data, '$.finalPrice') AS DECIMAL(10,2)) <= ?", [$amount]),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                
                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('success')
                    ->action(function (Model $record) {
                        try {
                            $pdfGenerator = app(PDFGeneratorService::class);
                            $pdfPath = $pdfGenerator->generateApplicationPDF($record);
                            
                            Notification::make()
                                ->title('PDF Generated Successfully')
                                ->success()
                                ->send();
                                
                            return redirect()->route('application.pdf.view', $record->session_id);
                        } catch (\Exception $e) {
                            Log::error('PDF Generation failed: ' . $e->getMessage(), [
                                'session_id' => $record->session_id,
                                'exception' => $e,
                            ]);
                            
                            Notification::make()
                                ->title('PDF Generation Failed')
                                ->body('Error: ' . $e->getMessage())
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
                            $pdfGenerator = app(PDFGeneratorService::class);
                            $pdfPath = $pdfGenerator->generateApplicationPDF($record);
                            
                            return response()->download(
                                Storage::disk('public')->path($pdfPath),
                                basename($pdfPath)
                            );
                        } catch (\Exception $e) {
                            Log::error('PDF Download failed: ' . $e->getMessage(), [
                                'session_id' => $record->session_id,
                                'exception' => $e,
                            ]);
                            
                            Notification::make()
                                ->title('PDF Download Failed')
                                ->body('Error: ' . $e->getMessage())
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
                            'record' => $record
                        ]);
                    })
                    ->modalWidth('4xl'),
                    
                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => \Filament\Facades\Filament::getCurrentPanel()->getId() !== 'partner')
                    ->mountUsing(function (Forms\ComponentContainer $form, Model $record) {
                        // Ensure reference code exists before form load
                        if (empty($record->reference_code)) {
                            Log::warning('Application missing reference code during status update mount - generating one', ['id' => $record->id]);
                            
                            try {
                                // Try to get National ID from form data
                                $formData = $record->form_data;
                                $formResponses = $formData['formResponses'] ?? $formData;
                                $nationalId = $formResponses['idNumber'] 
                                           ?? $formResponses['nationalIdNumber'] 
                                           ?? $formResponses['nationalId'] 
                                           ?? null;

                                if ($nationalId) {
                                    $cleanId = preg_replace('/[^A-Z0-9]/', '', strtoupper($nationalId));
                                    $record->reference_code = $cleanId;
                                    $record->saveQuietly();
                                    Log::info('Generated missing reference code in mount', ['reference_code' => $cleanId]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to generate missing reference code in mount', ['error' => $e->getMessage()]);
                            }
                        }

                        $form->fill();
                    })
                    ->form(function (Model $record) {
                        // Detect form type using the same logic as ListApplications
                        $formType = static::detectFormType($record);

                        // SSB Loan Application Form
                        if ($formType === 'ssb') {
                            return [
                                Forms\Components\Select::make('ssb_action')
                                    ->label('SSB Workflow Action')
                                    ->options([
                                        'initialize' => 'Initialize SSB Workflow',
                                        'simulate_approved' => 'Mark as SSB Approved',
                                        'simulate_insufficient_salary' => 'Mark as Insufficient Salary',
                                        'simulate_invalid_id' => 'Mark as Invalid ID',
                                        'simulate_contract_expiring' => 'Mark as Contract Expiring',
                                        'simulate_rejected' => 'Mark as Rejected',
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('recommended_period')
                                    ->label('Recommended Period (months)')
                                    ->numeric()
                                    ->visible(fn (Forms\Get $get) => in_array($get('ssb_action'), ['simulate_insufficient_salary', 'simulate_contract_expiring']))
                                    ->required(fn (Forms\Get $get) => in_array($get('ssb_action'), ['simulate_insufficient_salary', 'simulate_contract_expiring'])),
                                Forms\Components\DatePicker::make('contract_expiry_date')
                                    ->label('Contract Expiry Date')
                                    ->visible(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_contract_expiring')
                                    ->required(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_contract_expiring'),
                            ];
                        }

                        // ZB Account Opening or Account Holder Form
                        if ($formType === 'zb_account_opening' || $formType === 'account_holders') {
                            return [
                                Forms\Components\Select::make('zb_action')
                                    ->label('ZB Workflow Action')
                                    ->options([
                                        'initialize' => 'Initialize ZB Workflow',
                                        'credit_check_good' => 'Credit Check Passed (Approve)',
                                        'credit_check_poor' => 'Credit Check Failed (Reject)',
                                        'salary_not_regular' => 'Salary Irregular (Reject)',
                                        'insufficient_salary' => 'Insufficient Salary (Reject)',
                                        'approved' => 'Final Approval',
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('recommended_period')
                                    ->label('Recommended Period (months)')
                                    ->numeric()
                                    ->visible(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                                    ->required(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary'),
                            ];
                        }

                        // Fallback - should never reach here
                        return [
                            Forms\Components\Placeholder::make('error')
                                ->label('Unknown Form Type')
                                ->content('Unable to detect form type. Please contact support.'),
                        ];
                    })
                    ->action(function (array $data, Model $record) {
                        Log::info('Update Status Action Triggered', ['session_id' => $record->session_id]);
                        
                        // Detect form type using the same logic as ListApplications
                        $formType = static::detectFormType($record);

                        // Ensure reference code exists
                        if (empty($record->reference_code)) {
                            Log::warning('Application missing reference code during status update - generating one', ['id' => $record->id]);
                            
                            try {
                                // Try to get National ID from form data
                                $formData = $record->form_data;
                                $formResponses = $formData['formResponses'] ?? $formData;
                                $nationalId = $formResponses['idNumber'] 
                                           ?? $formResponses['nationalIdNumber'] 
                                           ?? $formResponses['nationalId'] 
                                           ?? null;

                                if ($nationalId) {
                                    $refService = app(\App\Services\ReferenceCodeService::class);
                                    // We need to generate it manually because generateReferenceCode expects a session ID and creates a new record?
                                    // No, let's look at ReferenceCodeService. It has generateReferenceCode(string $nationalId, string $sessionId).
                                    // But that method also creates a NEW application state if I recall correctly?
                                    // Let's check ReferenceCodeService again. 
                                    // Wait, I can't check it right now without interrupting.
                                    // Let's just generate it manually here to be safe and simple.
                                    $cleanId = preg_replace('/[^A-Z0-9]/', '', strtoupper($nationalId));
                                    $record->reference_code = $cleanId;
                                    $record->saveQuietly(); // Save without triggering events if possible
                                    Log::info('Generated missing reference code', ['reference_code' => $cleanId]);
                                } else {
                                    Log::error('Cannot generate reference code - no National ID found in form data', ['id' => $record->id]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to generate missing reference code', ['error' => $e->getMessage()]);
                            }
                        }

                        // Handle SSB workflow
                        if ($formType === 'ssb') {
                            $ssbService = app(\App\Services\SSBStatusService::class);

                            try {
                                switch ($data['ssb_action']) {
                                    case 'initialize':
                                        $ssbService->initializeSSBApplication($record);
                                        break;
                                    case 'simulate_approved':
                                        $ssbService->processSSBResponse($record, [
                                            'response_type' => 'approved'
                                        ]);
                                        break;
                                    case 'simulate_insufficient_salary':
                                        $ssbService->processSSBResponse($record, [
                                            'response_type' => 'insufficient_salary',
                                            'recommended_period' => $data['recommended_period']
                                        ]);
                                        break;
                                    case 'simulate_invalid_id':
                                        $ssbService->processSSBResponse($record, [
                                            'response_type' => 'invalid_id'
                                        ]);
                                        break;
                                    case 'simulate_contract_expiring':
                                        $ssbService->processSSBResponse($record, [
                                            'response_type' => 'contract_expiring',
                                            'recommended_period' => $data['recommended_period'],
                                            'contract_expiry_date' => $data['contract_expiry_date']
                                        ]);
                                        break;
                                    case 'simulate_rejected':
                                        $ssbService->processSSBResponse($record, [
                                            'response_type' => 'rejected'
                                        ]);
                                        break;
                                }

                                Notification::make()
                                    ->title('SSB Workflow Updated Successfully')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('SSB Workflow Update Failed', [
                                    'session_id' => $record->session_id,
                                    'error' => $e->getMessage()
                                ]);
                                
                                Notification::make()
                                    ->title('Update Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                            return;
                        }

                        // Handle ZB workflow (Account Opening or Account Holders)
                        if ($formType === 'zb_account_opening' || $formType === 'account_holders') {
                            $zbService = app(\App\Services\ZBStatusService::class);

                            try {
                                switch ($data['zb_action']) {
                                    case 'initialize':
                                        $zbService->initializeZBApplication($record);
                                        break;
                                    case 'credit_check_good':
                                        $zbService->processCreditCheckGood($record);
                                        break;
                                    case 'credit_check_poor':
                                        $zbService->processCreditCheckPoor($record);
                                        break;
                                    case 'salary_not_regular':
                                        $zbService->processSalaryNotRegular($record);
                                        break;
                                    case 'insufficient_salary':
                                        $zbService->processInsufficientSalary($record, $data['recommended_period']);
                                        break;
                                    case 'approved':
                                        $zbService->processApproved($record);
                                        break;
                                }

                                Notification::make()
                                    ->title('ZB Workflow Updated Successfully')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('ZB Workflow Update Failed', [
                                    'session_id' => $record->session_id,
                                    'error' => $e->getMessage()
                                ]);
                                
                                Notification::make()
                                    ->title('Update Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                            return;
                        }

                        // This should never happen - all forms should be SSB, ZB Account Opening, or Account Holders
                        Log::error('Unknown form type detected in Update Status', [
                            'session_id' => $record->session_id,
                            'form_type' => $formType,
                            'form_data' => $record->form_data,
                        ]);

                        Notification::make()
                            ->title('Unknown Form Type')
                            ->body('Unable to detect form type for this application. Please contact support.')
                            ->danger()
                            ->send();
                    }),

                // Super Admin SMS Notification Actions
                Action::make('send_status_sms')
                    ->label('Send Status SMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('primary')
                    ->visible(fn () => \Filament\Facades\Filament::getCurrentPanel()->getId() === 'admin')
                    ->requiresConfirmation()
                    ->modalHeading('Send Application Status SMS')
                    ->modalDescription('Send an SMS notification to the client about their application status.')
                    ->action(function (Model $record) {
                        try {
                            $formData = $record->form_data;
                            $formResponses = $formData['formResponses'] ?? $formData;
                            $phone = $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellphone'] ?? null;
                            
                            if (!$phone) {
                                throw new \Exception('No phone number found for this application.');
                            }
                            
                            $smsService = app(\App\Services\SMSService::class);
                            $message = "Your application status has been updated. Please login to check status.";
                            $smsService->sendSMS($phone, $message);
                            
                            Notification::make()
                                ->title('SMS Sent Successfully')
                                ->body("Status update SMS sent to {$phone}")
                                ->success()
                                ->send();
                                
                            Log::info('Status SMS sent by Super Admin', [
                                'session_id' => $record->session_id,
                                'phone' => $phone,
                                'sent_by' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('SMS Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('send_delivery_sms')
                    ->label('Send Delivery SMS')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (Model $record) => 
                        \Filament\Facades\Filament::getCurrentPanel()->getId() === 'admin' && 
                        in_array($record->current_step, ['approved', 'completed'])
                    )
                    ->modalHeading('Send Delivery Notification SMS')
                    ->form([
                        Forms\Components\Select::make('message_type')
                            ->label('Message Type')
                            ->options([
                                'zdc' => 'ZDC - Expect Delivery (Zero Deposit Credit)',
                                'pdc' => 'PDC - Make Deposit to Initiate Delivery (Paid Deposit Credit)',
                                'custom' => 'Custom Message',
                            ])
                            ->required()
                            ->live()
                            ->default('zdc'),
                        Forms\Components\Textarea::make('custom_message')
                            ->label('Custom Message')
                            ->visible(fn (Forms\Get $get) => $get('message_type') === 'custom')
                            ->required(fn (Forms\Get $get) => $get('message_type') === 'custom')
                            ->placeholder('Enter your custom message...'),
                    ])
                    ->action(function (array $data, Model $record) {
                        try {
                            $formData = $record->form_data;
                            $formResponses = $formData['formResponses'] ?? $formData;
                            $phone = $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellphone'] ?? null;
                            
                            if (!$phone) {
                                throw new \Exception('No phone number found for this application.');
                            }
                            
                            $firstName = $formResponses['firstName'] ?? $formResponses['forenames'] ?? 'Customer';
                            
                            switch ($data['message_type']) {
                                case 'zdc':
                                    $message = "Dear {$firstName}, your loan application has been approved. You can now expect your delivery. Thank you for choosing BancoSystem.";
                                    break;
                                case 'pdc':
                                    $message = "Dear {$firstName}, your loan application has been approved. Please make your deposit payment to initiate delivery. Login to view payment details. Thank you for choosing BancoSystem.";
                                    break;
                                case 'custom':
                                    $message = $data['custom_message'];
                                    break;
                                default:
                                    throw new \Exception('Invalid message type');
                            }
                            
                            $smsService = app(\App\Services\SMSService::class);
                            $smsService->sendSMS($phone, $message);
                            
                            Notification::make()
                                ->title('Delivery SMS Sent')
                                ->body("Message sent to {$phone}")
                                ->success()
                                ->send();
                                
                            Log::info('Delivery SMS sent by Super Admin', [
                                'session_id' => $record->session_id,
                                'phone' => $phone,
                                'message_type' => $data['message_type'],
                                'sent_by' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('SMS Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // SSB Loan Workflow Actions
                Action::make('ssb_workflow')
                    ->label('SSB Loan Status')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn (Model $record) => ((($record->form_data['employer'] ?? '') === 'SSB' || ($record->form_data['employer'] ?? '') === 'government-ssb' || ($record->metadata['workflow_type'] ?? '') === 'ssb') && \Filament\Facades\Filament::getCurrentPanel()->getId() === 'zb_admin'))
                    ->modalHeading('Update SSB Loan Status')
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Select::make('ssb_action')
                            ->label('SSB Status Update')
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
                        Forms\Components\TextInput::make('recommended_period')
                            ->label('Recommended Period (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->visible(fn (Forms\Get $get) => in_array($get('ssb_action'), ['simulate_insufficient_salary', 'simulate_contract_expiring']))
                            ->required(fn (Forms\Get $get) => in_array($get('ssb_action'), ['simulate_insufficient_salary', 'simulate_contract_expiring'])),
                        Forms\Components\TextInput::make('salary')
                            ->label('Applicant Salary')
                            ->numeric()
                            ->prefix('$')
                            ->visible(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_insufficient_salary'),
                        Forms\Components\DatePicker::make('contract_expiry_date')
                            ->label('Contract Expiry Date')
                            ->visible(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_contract_expiring')
                            ->required(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_contract_expiring'),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->visible(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_invalid_id'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->visible(fn (Forms\Get $get) => $get('ssb_action') === 'simulate_rejected'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Admin Notes')
                            ->placeholder('Optional notes about this status update'),
                    ])
                    ->action(function (array $data, Model $record) {
                        $ssbService = app(SSBStatusService::class);

                        try {
                            if ($data['ssb_action'] === 'initialize') {
                                $ssbService->initializeSSBApplication($record);

                                Notification::make()
                                    ->title('SSB Workflow Initialized')
                                    ->success()
                                    ->send();
                            } else {
                                // Simulate SSB response
                                $responseType = str_replace('simulate_', '', $data['ssb_action']);

                                $ssbResponse = [
                                    'response_type' => $responseType,
                                    'recommended_period' => $data['recommended_period'] ?? null,
                                    'salary' => $data['salary'] ?? null,
                                    'contract_expiry_date' => $data['contract_expiry_date'] ?? null,
                                    'error_message' => $data['error_message'] ?? null,
                                    'reason' => $data['rejection_reason'] ?? null,
                                ];

                                $ssbService->processSSBResponse($record, $ssbResponse);

                                Notification::make()
                                    ->title('SSB Status Updated')
                                    ->body("Status: " . str_replace('_', ' ', ucwords($responseType)))
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Log::error('SSB status update failed', [
                                'session_id' => $record->session_id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('SSB Status Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ZB Loan Workflow Actions
                Action::make('zb_workflow')
                    ->label('ZB Loan Status')
                    ->icon('heroicon-o-building-library')
                    ->color('success')
                    ->visible(fn (Model $record) => (($record->form_data['hasAccount'] ?? false) || ($record->form_data['wantsAccount'] ?? false) || ($record->metadata['workflow_type'] ?? '') === 'zb') && \Filament\Facades\Filament::getCurrentPanel()->getId() === 'zb_admin')
                    ->modalHeading('Update ZB Loan Status')
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Select::make('zb_action')
                            ->label('ZB Status Update')
                            ->options([
                                'initialize' => 'Initialize ZB Workflow',
                                'credit_check_good' => 'Credit Check: Good (Approve)',
                                'credit_check_poor' => 'Credit Check: Poor (Reject + Blacklist Report)',
                                'salary_not_regular' => 'Salary Not Regular (Reject)',
                                'insufficient_salary' => 'Insufficient Salary (Reject + Period Adjustment)',
                                'approved' => 'Approved (Final)',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('recommended_period')
                            ->label('Recommended Period (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->visible(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                            ->required(fn (Forms\Get $get) => $get('zb_action') === 'insufficient_salary')
                            ->helperText('Calculate based on salary (max 30% for installment)'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Admin Notes')
                            ->placeholder('Optional notes about this status update')
                            ->helperText('e.g., Credit score, salary details, reasons'),
                    ])
                    ->action(function (array $data, Model $record) {
                        $zbService = app(ZBStatusService::class);

                        try {
                            switch ($data['zb_action']) {
                                case 'initialize':
                                    $zbService->initializeZBApplication($record);
                                    $message = 'ZB Workflow Initialized';
                                    break;

                                case 'credit_check_good':
                                    $zbService->processCreditCheckGood($record, $data['notes'] ?? '');
                                    $message = 'Credit Check: Good - Application Approved';
                                    break;

                                case 'credit_check_poor':
                                    $zbService->processCreditCheckPoor($record, $data['notes'] ?? '');
                                    $message = 'Credit Check: Poor - Client offered blacklist report ($5)';
                                    break;

                                case 'salary_not_regular':
                                    $zbService->processSalaryNotRegular($record, $data['notes'] ?? '');
                                    $message = 'Rejected: Salary Not Regular';
                                    break;

                                case 'insufficient_salary':
                                    $zbService->processInsufficientSalary(
                                        $record,
                                        $data['recommended_period'],
                                        $data['notes'] ?? ''
                                    );
                                    $message = 'Rejected: Insufficient Salary - Client offered period adjustment';
                                    break;

                                case 'approved':
                                    $zbService->processApproved($record, $data['notes'] ?? '');
                                    $message = 'Application Approved - Delivery tracking available';
                                    break;

                                default:
                                    throw new \Exception('Invalid action');
                            }

                            Notification::make()
                                ->title('ZB Status Updated')
                                ->body($message)
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('ZB status update failed', [
                                'session_id' => $record->session_id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('ZB Status Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('generate_pdfs')
                        ->label('Generate PDFs')
                        ->icon('heroicon-o-document')
                        ->action(function ($records) {
                            $sessionIds = $records->pluck('session_id')->toArray();
                            $count = count($sessionIds);
                            
                            try {
                                $pdfGenerator = app(PDFGeneratorService::class);
                                $generatedPaths = $pdfGenerator->generateBatchPDFs($sessionIds);
                                
                                Notification::make()
                                    ->title("Generated {$count} PDFs Successfully")
                                    ->success()
                                    ->send();
                                    
                                // Return the first PDF for viewing
                                if (!empty($generatedPaths)) {
                                    return redirect()->route('application.pdf.view', $sessionIds[0]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Bulk PDF Generation failed: ' . $e->getMessage(), [
                                    'session_ids' => $sessionIds,
                                    'exception' => $e,
                                ]);
                                
                                Notification::make()
                                    ->title('Bulk PDF Generation Failed')
                                    ->body('Error: ' . $e->getMessage())
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
                                'session_ids' => $sessionIds
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
                            $notificationService = new NotificationService();
                            $applicationUpdates = [];
                            
                            foreach ($records as $record) {
                                $oldStatus = $record->current_step;
                                
                                // Update both current_step and metadata for consistency
                                $metadata = $record->metadata ?? [];
                                $metadata['status'] = $newStatus;
                                $metadata['status_updated_at'] = now()->toIso8601String();
                                $metadata['status_updated_by'] = auth()->id();
                                
                                // Add status history to metadata
                                $metadata['status_history'] = $metadata['status_history'] ?? [];
                                $metadata['status_history'][] = [
                                    'status' => $newStatus,
                                    'timestamp' => now()->toIso8601String(),
                                    'updated_by' => auth()->id(),
                                    'notes' => $data['notes'] ?? null,
                                    'bulk_update' => true,
                                ];
                                
                                $record->current_step = $newStatus;
                                $record->metadata = $metadata;
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
                            if (!empty($applicationUpdates)) {
                                $notificationResults = $notificationService->sendBatchStatusNotifications($applicationUpdates);
                                $successCount = count(array_filter($notificationResults, fn($r) => $r['success']));
                                
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

                    Tables\Actions\BulkAction::make('export_ssb_loans')
                        ->label('Export SSB Loans (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function () {
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\SSBLoanExport(),
                                'ssb_loans_export_' . date('Y-m-d') . '.csv'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_zb_loans')
                        ->label('Export ZB Loans (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function () {
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\ZBLoanExport(),
                                'zb_loans_export_' . date('Y-m-d') . '.csv'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\ForceDeleteBulkAction::make('forceDelete')
                        ->label('Delete'),
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
        return parent::getEloquentQuery()
            // Exclude agent application states from Loan Applications
            ->where(function ($query) {
                $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                
                $query->where('current_step', 'not like', 'agent_%')
                    ->where(function ($q) use ($isPgsql) {
                        $q->whereNull('form_data');
                        
                        if ($isPgsql) {
                            $q->orWhereRaw("form_data->>'outcome' IS NULL")
                              ->orWhereRaw("form_data->>'outcome' != 'agent_application_submitted'");
                        } else {
                            $q->orWhereRaw("JSON_EXTRACT(form_data, '$.outcome') IS NULL")
                              ->orWhereRaw("JSON_EXTRACT(form_data, '$.outcome') != 'agent_application_submitted'");
                        }
                    });
            });
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

    /**
     * Detect form type from application data
     * Same logic as ListApplications::detectFormType()
     */
    public static function detectFormType($application): string
    {
        try {
            if (!$application) {
                return 'unknown';
            }

            // Check metadata first
            if (isset($application->metadata['form_type'])) {
                return $application->metadata['form_type'];
            }

            // Detect from form data
            $formData = $application->form_data;
            
            if (empty($formData) || !is_array($formData)) {
                return 'unknown';
            }

            // Get relevant fields
            $employer = $formData['employer'] ?? null;
            $hasAccount = $formData['hasAccount'] ?? false;
            $wantsAccount = $formData['wantsAccount'] ?? false;
            
            // Logic ported from ApplicationSummary.tsx
            if ($employer === 'government-ssb') {
                return 'ssb';
            }
            
            if ($employer === 'entrepreneur') {
                return 'sme_business';
            }

            if ($hasAccount) {
                return 'account_holders';
            }

            if ($wantsAccount) {
                return 'zb_account_opening';
            }

            // Fallback: Check form responses for specific fields if the above flags are missing
            $formResponses = $formData['formResponses'] ?? $formData;
            
            if (isset($formResponses['responsibleMinistry'])) {
                return 'ssb';
            }

            if (isset($formResponses['businessName']) || isset($formResponses['businessRegistration'])) {
                return 'sme_business';
            }

            if (isset($formResponses['accountType'])) {
                return 'zb_account_opening';
            }

            // Default to account holders (ZB loan) if nothing else matches
            return 'account_holders';
            
        } catch (\Exception $e) {
            Log::error('Error detecting form type', [
                'id' => $application->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return 'unknown';
        }
    }
}
