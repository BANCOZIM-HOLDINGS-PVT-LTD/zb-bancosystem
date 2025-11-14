<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationState;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentApplicationsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApplicationState::query()
                    ->latest()
                    ->limit(10)
            )
            ->heading('Recent Applications')
            ->description('Latest applications submitted to the system')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('App #')
                    ->formatStateUsing(fn ($state) => 'ZB'.date('Y').str_pad($state, 6, '0', STR_PAD_LEFT)),

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->getStateUsing(function ($record) {
                        $data = $record->form_data['formResponses'] ?? [];
                        $firstName = $data['firstName'] ?? '';
                        $lastName = $data['lastName'] ?? '';

                        return trim($firstName.' '.$lastName) ?: 'N/A';
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn ($record) => '$'.number_format($record->form_data['finalPrice'] ?? 0))
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('channel')
                    ->colors([
                        'primary' => 'web',
                        'success' => 'whatsapp',
                        'warning' => 'ussd',
                        'danger' => 'mobile_app',
                    ]),

                Tables\Columns\BadgeColumn::make('current_step')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state): bool => in_array($state, ['completed', 'approved']),
                        'warning' => fn ($state): bool => in_array($state, ['in_review', 'processing', 'pending_documents']),
                        'danger' => fn ($state): bool => $state === 'rejected',
                        'gray' => fn ($state): bool => in_array($state, ['language', 'intent', 'employer', 'form', 'product', 'business']),
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_ago')
                    ->label('Time Ago')
                    ->getStateUsing(fn ($record) => $record->created_at->diffForHumans())
                    ->sortable(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (ApplicationState $record): string => route('filament.admin.resources.applications.view', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
