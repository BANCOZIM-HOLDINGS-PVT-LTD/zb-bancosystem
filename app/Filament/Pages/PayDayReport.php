<?php

namespace App\Filament\Pages;

use App\Models\ApplicationState;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class PayDayReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Pay Day Report';
    protected static ?string $title = 'Pay Day Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.pay-day-report';

    /**
     * Map payDayRange value to human-readable label
     */
    public static function getPayDayLabel(?string $value): string
    {
        return match ($value) {
            'week1' => '1st Week (1st - 7th)',
            'week2' => '2nd Week (8th - 15th)',
            'week3' => '3rd Week (16th - 21st)',
            'week4' => 'After 22nd',
            default => $value ?? '—',
        };
    }

    /**
     * Map payDayRange to the reference day of the month
     */
    public static function getPayDayReferenceDate(string $value): int
    {
        return match ($value) {
            'week1' => 7,
            'week2' => 15,
            'week3' => 21,
            'week4' => 28,
            default => 0,
        };
    }

    public function getPayDayStats(): array
    {
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';

        $ranges = ['week1', 'week2', 'week3', 'week4'];
        $labels = [
            'week1' => '1st Week',
            'week2' => '2nd Week',
            'week3' => '3rd Week',
            'week4' => 'After 22nd',
        ];
        $colors = [
            'week1' => 'text-green-600',
            'week2' => 'text-blue-600',
            'week3' => 'text-amber-600',
            'week4' => 'text-red-600',
        ];
        $bgs = [
            'week1' => 'bg-green-100 dark:bg-green-900',
            'week2' => 'bg-blue-100 dark:bg-blue-900',
            'week3' => 'bg-amber-100 dark:bg-amber-900',
            'week4' => 'bg-red-100 dark:bg-red-900',
        ];

        $stats = [];
        foreach ($ranges as $range) {
            $count = ApplicationState::query()
                ->whereIn('current_step', ['approved', 'completed', 'in_review', 'processing'])
                ->where(function ($q) use ($isPgsql, $range) {
                    if ($isPgsql) {
                        $q->whereRaw("form_data->'formResponses'->>'payDayRange' = ?", [$range]);
                    } else {
                        $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.payDayRange')) = ?", [$range]);
                    }
                })
                ->count();

            $stats[] = [
                'label' => $labels[$range],
                'count' => $count,
                'color' => $colors[$range],
                'bg' => $bgs[$range],
            ];
        }

        return $stats;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApplicationState::query()
                    ->whereIn('current_step', ['approved', 'completed', 'in_review', 'processing'])
                    ->where(function (Builder $query) {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        if ($isPgsql) {
                            $query->whereRaw("form_data->'formResponses'->>'payDayRange' IS NOT NULL");
                        } else {
                            $query->whereRaw("JSON_EXTRACT(form_data, '$.formResponses.payDayRange') IS NOT NULL");
                        }
                    })
            )
            ->columns([
                TextColumn::make('application_number')
                    ->label('App #')
                    ->getStateUsing(fn ($record) => $record->application_number)
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('client_name')
                    ->label('Client Name')
                    ->getStateUsing(function ($record) {
                        $formResponses = $record->form_data['formResponses'] ?? [];
                        $firstName = $formResponses['firstName'] ?? '';
                        $surname = $formResponses['surname'] ?? $formResponses['lastName'] ?? '';
                        return trim("{$firstName} {$surname}") ?: '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        if ($isPgsql) {
                            return $query->where(function ($q) use ($search) {
                                $q->whereRaw("form_data->'formResponses'->>'firstName' ILIKE ?", ["%{$search}%"])
                                  ->orWhereRaw("form_data->'formResponses'->>'surname' ILIKE ?", ["%{$search}%"]);
                            });
                        }
                        return $query->where(function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.firstName')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.surname')) LIKE ?", ["%{$search}%"]);
                        });
                    }),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->getStateUsing(function ($record) {
                        $formResponses = $record->form_data['formResponses'] ?? [];
                        return $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? $formResponses['cellNumber'] ?? '—';
                    }),

                TextColumn::make('employer')
                    ->label('Employer')
                    ->getStateUsing(function ($record) {
                        $formData = $record->form_data ?? [];
                        $employer = $formData['employer'] ?? '';
                        return match ($employer) {
                            'government-ssb' => 'Government (SSB)',
                            'entrepreneur' => 'SME / Entrepreneur',
                            default => $formData['formResponses']['employerName'] ?? $employer ?: '—',
                        };
                    }),

                TextColumn::make('pay_day_range')
                    ->label('Pay Day Range')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        '1st Week (1st - 7th)' => 'success',
                        '2nd Week (8th - 15th)' => 'info',
                        '3rd Week (16th - 21st)' => 'warning',
                        'After 22nd' => 'danger',
                        default => 'gray',
                    })
                    ->getStateUsing(function ($record) {
                        $payDay = $record->form_data['formResponses']['payDayRange'] ?? null;
                        return self::getPayDayLabel($payDay);
                    }),

                TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->getStateUsing(function ($record) {
                        $formResponses = $record->form_data['formResponses'] ?? [];
                        $amount = $formResponses['loanAmount'] ?? $record->form_data['finalPrice'] ?? '';
                        $currency = $record->form_data['currency'] ?? 'USD';
                        $symbol = $currency === 'ZiG' ? 'ZiG ' : '$';
                        return $amount ? "{$symbol}{$amount}" : '—';
                    }),

                TextColumn::make('current_step')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'completed' => 'success',
                        'in_review' => 'warning',
                        'processing' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('pay_day_range')
                    ->label('Pay Day Range')
                    ->options([
                        'week1' => '1st Week (1st - 7th)',
                        'week2' => '2nd Week (8th - 15th)',
                        'week3' => '3rd Week (16th - 21st)',
                        'week4' => 'After 22nd',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        if ($isPgsql) {
                            return $query->whereRaw("form_data->'formResponses'->>'payDayRange' = ?", [$data['value']]);
                        }
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.formResponses.payDayRange')) = ?", [$data['value']]);
                    }),

                SelectFilter::make('form_type')
                    ->label('Form Type')
                    ->options([
                        'ssb' => 'SSB (Government)',
                        'account_holders' => 'Account Holders',
                        'pensioner' => 'Pensioner',
                        'rdc' => 'RDC',
                        'sme_business' => 'SME Business',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                        return match ($data['value']) {
                            'ssb' => $query->where(function ($q) use ($isPgsql) {
                                if ($isPgsql) {
                                    $q->whereRaw("form_data->>'employer' = 'government-ssb'");
                                } else {
                                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'government-ssb'");
                                }
                            }),
                            'sme_business' => $query->where(function ($q) use ($isPgsql) {
                                if ($isPgsql) {
                                    $q->whereRaw("form_data->>'employer' = 'entrepreneur'");
                                } else {
                                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'entrepreneur'");
                                }
                            }),
                            'account_holders' => $query->where(function ($q) use ($isPgsql) {
                                if ($isPgsql) {
                                    $q->whereRaw("(form_data->>'hasAccount')::boolean = true");
                                } else {
                                    $q->whereRaw("JSON_EXTRACT(form_data, '$.hasAccount') = true");
                                }
                            }),
                            default => $query,
                        };
                    }),

                SelectFilter::make('current_step')
                    ->label('Status')
                    ->options([
                        'approved' => 'Approved',
                        'completed' => 'Completed',
                        'in_review' => 'In Review',
                        'processing' => 'Processing',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
