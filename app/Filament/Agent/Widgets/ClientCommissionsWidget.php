<?php

namespace App\Filament\Agent\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationState;
use Illuminate\Database\Eloquent\Builder;

class ClientCommissionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $agent = Auth::guard('agent')->user();

        return $table
            ->heading('Credit/Loan Application Referrals')
            ->description('Clients who applied for credit or hire purchase through your referral link')
            ->query(
                ApplicationState::query()
                    ->where('agent_id', $agent?->id)
                    ->with(['commissions'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client Name')
                    ->getStateUsing(function (ApplicationState $record) {
                        $formData = $record->form_data ?? [];
                        $formResponses = $formData['formResponses'] ?? [];
                        $firstName = $formResponses['firstName'] ?? '';
                        $lastName = $formResponses['lastName'] ?? '';
                        return trim($firstName . ' ' . $lastName) ?: 'N/A';
                    })
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference Code')
                    ->copyable()
                    ->copyMessage('Reference code copied')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('admin_status')
                    ->label('Application Status')
                    ->getStateUsing(function (ApplicationState $record) {
                        $metadata = $record->metadata ?? [];
                        return $metadata['admin_status'] ?? 'pending';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'under_review' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->getStateUsing(function (ApplicationState $record) {
                        $formData = $record->form_data ?? [];
                        $formResponses = $formData['formResponses'] ?? [];
                        return floatval($formResponses['loanAmount'] ?? 0);
                    })
                    ->money('USD')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->getStateUsing(function (ApplicationState $record) {
                        $commission = $record->commissions()->first();
                        return $commission ? $commission->amount : 0;
                    })
                    ->money('USD')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('commission_status')
                    ->label('Commission Status')
                    ->getStateUsing(function (ApplicationState $record) {
                        $commission = $record->commissions()->first();
                        return $commission ? $commission->status : 'N/A';
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'approved' => 'info',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('admin_status')
                    ->label('Application Status')
                    ->options([
                        'pending' => 'Pending',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            return $query->whereJsonContains('metadata->admin_status', $data['value']);
                        }
                    }),

                Tables\Filters\SelectFilter::make('commission_status')
                    ->label('Commission Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            return $query->whereHas('commissions', function ($q) use ($data) {
                                $q->where('status', $data['value']);
                            });
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
