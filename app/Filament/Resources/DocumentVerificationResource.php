<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentVerificationResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DocumentVerificationResource extends Resource
{
    protected static ?string $model = ApplicationState::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Document Manual Verification';
    
    protected static ?string $modelLabel = 'Verification Pending';

    protected static ?string $navigationGroup = 'Document Management';

    protected static ?string $slug = 'document-verification';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('current_step', 'pending_review');
    }

    public static function form(Form $form): Form
    {
        // View-only form for modal context if needed
        return $form
            ->schema([
                Forms\Components\Section::make('Application Data')
                    ->schema([
                        Forms\Components\ViewField::make('form_data')
                            ->view('filament.forms.components.application-data'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')->label('Ref Code')->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(fn (Model $record) => 
                        trim(($record->form_data['formResponses']['firstName'] ?? '') . ' ' . ($record->form_data['formResponses']['lastName'] ?? ''))
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
                Tables\Columns\BadgeColumn::make('channel'),
            ])
            ->actions([
                // Process Application Action (Go to inner page)
                Action::make('process')
                    ->label('Verify Application')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->url(fn (Model $record): string => static::getUrl('process', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentVerifications::route('/'),
            'process' => Pages\ProcessApplication::route('/{record}/process'),
        ];
    }
}
