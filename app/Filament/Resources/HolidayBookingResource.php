<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayBookingResource\Pages;
use App\Models\ApplicationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class HolidayBookingResource extends BaseResource
{
    protected static ?string $model = ApplicationState::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'Holiday Bookings';
    
    protected static ?string $navigationGroup = null; // Remove from Agent Management
    
    protected static ?int $navigationSort = 4; // Position after Cash Orders
    
    protected static ?string $slug = 'holiday-bookings';
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('current_step', 'completed')
            ->whereRaw("JSON_EXTRACT(form_data, '$.business') = 'Zimparks Vacation Package'");
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled(),
                        Forms\Components\TextInput::make('applicant_name')
                            ->label('Applicant Name')
                            ->disabled()
                            ->formatStateUsing(function (Model $record) {
                                $data = $record->form_data['formResponses'] ?? [];
                                $firstName = $data['firstName'] ?? '';
                                $lastName = $data['lastName'] ?? '';
                                return trim($firstName . ' ' . $lastName);
                            }),
                        Forms\Components\TextInput::make('destination')
                            ->label('Destination')
                            ->disabled()
                            ->formatStateUsing(function (Model $record) {
                                return $record->form_data['bookingDetails']['destination'] 
                                    ?? $record->form_data['destinationName'] 
                                    ?? 'N/A';
                            }),
                        Forms\Components\TextInput::make('package')
                            ->label('Package')
                            ->disabled()
                            ->formatStateUsing(function (Model $record) {
                                return $record->form_data['scale'] ?? 'N/A';
                            }),
                        Forms\Components\DatePicker::make('check_in')
                            ->label('Check-in Date')
                            ->disabled()
                            ->formatStateUsing(function (Model $record) {
                                return $record->form_data['bookingDetails']['startDate'] ?? null;
                            }),
                        Forms\Components\DatePicker::make('check_out')
                            ->label('Check-out Date')
                            ->disabled()
                            ->formatStateUsing(function (Model $record) {
                                return $record->form_data['bookingDetails']['endDate'] ?? null;
                            }),
                        Forms\Components\Select::make('current_step')
                            ->label('Status')
                            ->options([
                                'completed' => 'Completed',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
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
                    ->getStateUsing(function (Model $record) {
                        $data = $record->form_data['formResponses'] ?? [];
                        $firstName = $data['firstName'] ?? '';
                        $lastName = $data['lastName'] ?? '';
                        return trim($firstName . ' ' . $lastName) ?: 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("JSON_EXTRACT(form_data, '$.formResponses.firstName') LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_EXTRACT(form_data, '$.formResponses.lastName') LIKE ?", ["%{$search}%"]);
                    }),
                    
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->getStateUsing(function (Model $record) {
                        return $record->form_data['bookingDetails']['destination'] 
                            ?? $record->form_data['destinationName'] 
                            ?? 'N/A';
                    })
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('package')
                    ->label('Package')
                    ->getStateUsing(fn (Model $record) => $record->form_data['scale'] ?? 'N/A'),
                    
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check-in')
                    ->getStateUsing(function (Model $record) {
                        $date = $record->form_data['bookingDetails']['startDate'] ?? null;
                        return $date ? date('M j, Y', strtotime($date)) : 'N/A';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("JSON_EXTRACT(form_data, '$.bookingDetails.startDate') {$direction}");
                    }),
                    
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check-out')
                    ->getStateUsing(function (Model $record) {
                        $date = $record->form_data['bookingDetails']['endDate'] ?? null;
                        return $date ? date('M j, Y', strtotime($date)) : 'N/A';
                    }),
                    
                Tables\Columns\TextColumn::make('nights')
                    ->label('Nights')
                    ->getStateUsing(function (Model $record) {
                        $start = $record->form_data['bookingDetails']['startDate'] ?? null;
                        $end = $record->form_data['bookingDetails']['endDate'] ?? null;
                        if ($start && $end) {
                            $diff = strtotime($end) - strtotime($start);
                            return ceil($diff / (60 * 60 * 24));
                        }
                        return 'N/A';
                    }),
                    
                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['completed', 'approved']),
                        'warning' => fn ($state): bool => $state === 'processing',
                        'danger' => fn ($state): bool => $state === 'rejected',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booking Date')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('destination')
                    ->label('Destination')
                    ->options(function () {
                        return ApplicationState::whereRaw("JSON_EXTRACT(form_data, '$.business') = 'Zimparks Vacation Package'")
                            ->get()
                            ->pluck('form_data.bookingDetails.destination', 'form_data.bookingDetails.destination')
                            ->unique()
                            ->filter()
                            ->toArray();
                    }),
                    
                Tables\Filters\Filter::make('check_in')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From Date'),
                        Forms\Components\DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereRaw("JSON_EXTRACT(form_data, '$.bookingDetails.startDate') >= ?", [$date]),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereRaw("JSON_EXTRACT(form_data, '$.bookingDetails.startDate') <= ?", [$date]),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function () {
                            return redirect()->route('admin.export.holiday-packages');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidayBookings::route('/'),
            'view' => Pages\ViewHolidayBooking::route('/{record}'),
            'edit' => Pages\EditHolidayBooking::route('/{record}/edit'),
        ];
    }
}
