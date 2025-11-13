<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PDFManagementResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class PDFManagementResource extends Resource
{
    protected static ?string $model = ApplicationState::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'PDF Management';
    
    protected static ?string $navigationGroup = 'Document Management';
    
    protected static ?int $navigationSort = 1;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('session_id')
                    ->label('Session ID')
                    ->disabled(),
                
                Forms\Components\Select::make('channel')
                    ->options([
                        'web' => 'Web',
                        'whatsapp' => 'WhatsApp',
                        'ussd' => 'USSD',
                        'mobile_app' => 'Mobile App',
                    ])
                    ->disabled(),
                
                Forms\Components\TextInput::make('current_step')
                    ->label('Current Step')
                    ->disabled(),
                
                Forms\Components\KeyValue::make('form_data')
                    ->label('Form Data')
                    ->disabled(),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session_id')
                    ->label('Session ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant Name')
                    ->getStateUsing(function (ApplicationState $record) {
                        $formData = $record->form_data;
                        return ($formData['firstName'] ?? '') . ' ' . ($formData['surname'] ?? '');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereRaw("JSON_EXTRACT(form_data, '$.firstName') LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_EXTRACT(form_data, '$.surname') LIKE ?", ["%{$search}%"]);
                        });
                    }),
                
                Tables\Columns\TextColumn::make('form_type')
                    ->label('Form Type')
                    ->getStateUsing(function (ApplicationState $record) {
                        return static::detectFormType($record);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ssb' => 'success',
                        'account_holders' => 'info',
                        'sme_business' => 'warning',
                        'zb_account_opening' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('channel')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'web' => 'success',
                        'whatsapp' => 'info',
                        'ussd' => 'warning',
                        'mobile_app' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('current_step')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'document_upload' => 'info',
                        'form_step' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('monthly_payment')
                    ->label('Monthly Payment')
                    ->getStateUsing(function (ApplicationState $record) {
                        return '$' . ($record->form_data['monthlyPayment'] ?? '0');
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied On')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('pdf_status')
                    ->label('PDF Status')
                    ->getStateUsing(function (ApplicationState $record) {
                        $pattern = "*{$record->session_id}*.pdf";
                        $files = Storage::disk('local')->files('applications');
                        $matchingFiles = array_filter($files, function($file) use ($record) {
                            return str_contains($file, $record->session_id);
                        });
                        return count($matchingFiles) > 0 ? 'Generated' : 'Not Generated';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Generated' ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'web' => 'Web',
                        'whatsapp' => 'WhatsApp',
                        'ussd' => 'USSD',
                        'mobile_app' => 'Mobile App',
                    ]),
                
                Tables\Filters\SelectFilter::make('current_step')
                    ->options([
                        'completed' => 'Completed',
                        'document_upload' => 'Document Upload',
                        'form_step' => 'Form Step',
                        'product_selection' => 'Product Selection',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (ApplicationState $record): string => route('admin.pdf.download', $record->session_id))
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('regenerate_pdf')
                    ->label('Regenerate PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (ApplicationState $record) {
                        // Call the regenerate endpoint
                        $response = app(\App\Http\Controllers\Admin\PDFManagementController::class)
                            ->regenerate($record->session_id);
                        
                        if ($response->getStatusCode() === 200) {
                            \Filament\Notifications\Notification::make()
                                ->title('PDF Regenerated')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('PDF Regeneration Failed')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('bulk_download')
                    ->label('Download Selected PDFs')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $sessionIds = $records->pluck('session_id')->toArray();
                        
                        return redirect()->route('admin.pdf.bulk-download', [
                            'session_ids' => $sessionIds
                        ]);
                    }),
                
                BulkAction::make('export_for_bank')
                    ->label('Export for Bank')
                    ->icon('heroicon-o-building-library')
                    ->form([
                        Forms\Components\Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'pdf' => 'PDF Archive',
                                'excel' => 'Excel Spreadsheet',
                                'csv' => 'CSV File',
                            ])
                            ->required()
                            ->default('pdf'),
                        
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),
                        
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ])
                    ->action(function (Collection $records, array $data) {
                        return redirect()->route('admin.pdf.export-bank', $data);
                    }),
                
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Delete Applications')
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
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
            'index' => Pages\ListPDFManagement::route('/'),
            'view' => Pages\ViewPDFManagement::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('form_data');
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNotNull('form_data')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereNotNull('form_data')->count() > 100 ? 'warning' : 'primary';
    }
    
    // Helper method to detect form type
    protected static function detectFormType(ApplicationState $application): string
    {
        // Check metadata first
        if (isset($application->metadata['form_type'])) {
            return $application->metadata['form_type'];
        }
        
        // Detect from form data
        $formData = $application->form_data;
        
        if (isset($formData['responsibleMinistry'])) {
            return 'SSB';
        } elseif (isset($formData['businessName']) || isset($formData['businessRegistration'])) {
            return 'SME Business';
        } elseif (isset($formData['accountType'])) {
            return 'ZB Account Opening';
        } else {
            return 'Account Holders';
        }
    }
}