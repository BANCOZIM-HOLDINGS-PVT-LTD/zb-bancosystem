<?php

namespace App\Filament\Hr\Resources;

use App\Filament\Hr\Resources\DailyRegisterResource\Pages;
use App\Models\DailyRegister;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyRegisterResource extends Resource
{
    protected static ?string $model = DailyRegister::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Daily Register';

    protected static ?string $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Entry')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Staff Member')
                            ->options(fn () => User::whereIn('role', ['employee', 'intern', 'ROLE_HR', 'ROLE_ACCOUNTING', 'ROLE_STORES'])
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('user_type')
                            ->label('Staff Type')
                            ->options([
                                'employee' => 'Employee',
                                'intern' => 'Intern',
                            ])
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),

                        Forms\Components\Select::make('status')
                            ->label('Attendance Status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                                'half_day' => 'Half Day',
                                'leave' => 'On Leave',
                            ])
                            ->required()
                            ->default('present')
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Time Tracking')
                    ->schema([
                        Forms\Components\TimePicker::make('check_in')
                            ->label('Check In Time')
                            ->seconds(false)
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['present', 'late', 'half_day'])),

                        Forms\Components\TimePicker::make('check_out')
                            ->label('Check Out Time')
                            ->seconds(false)
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['present', 'late', 'half_day'])),
                    ])->columns(2),

                Forms\Components\Section::make('Task Log')
                    ->schema([
                        Forms\Components\Textarea::make('tasks_completed')
                            ->label('Tasks Completed Today')
                            ->rows(4)
                            ->placeholder('List the tasks completed during this work day...'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(2)
                            ->placeholder('Any additional notes...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'employee' => 'success',
                        'intern' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'half_day' => 'info',
                        'absent' => 'danger',
                        'leave' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check In')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check Out')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('hours_worked')
                    ->label('Hours')
                    ->getStateUsing(fn ($record) => $record->hours_worked ? number_format($record->hours_worked, 1) . 'h' : '-'),

                Tables\Columns\TextColumn::make('tasks_completed')
                    ->label('Tasks')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->options([
                        'employee' => 'Employee',
                        'intern' => 'Intern',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'half_day' => 'Half Day',
                        'leave' => 'On Leave',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('From'),
                        Forms\Components\DatePicker::make('date_to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['date_to'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),
                Tables\Filters\Filter::make('today')
                    ->label('Today Only')
                    ->query(fn (Builder $query) => $query->whereDate('date', today()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyRegisters::route('/'),
            'create' => Pages\CreateDailyRegister::route('/create'),
            'edit' => Pages\EditDailyRegister::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::today()->count() ?: null;
    }
}
