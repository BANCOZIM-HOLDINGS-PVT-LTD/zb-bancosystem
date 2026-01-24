<?php

namespace App\Filament\ZbAdmin\Resources;

use App\Filament\ZbAdmin\Resources\SsbLoanResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SsbLoanResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'SSB Loan Applications';

    protected static ?string $navigationGroup = 'Loan Management';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Only show SSB loan applications
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'government-ssb'")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.workflow_type')) = 'ssb'");
            })
            ->orderBy('created_at', 'desc');
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for view-only resource
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSsbLoans::route('/'),
            'view' => Pages\ViewSsbLoan::route('/{record}'),
        ];
    }

    // View-only access
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
