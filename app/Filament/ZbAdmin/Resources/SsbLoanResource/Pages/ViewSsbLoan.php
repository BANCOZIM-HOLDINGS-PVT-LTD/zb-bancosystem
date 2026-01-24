<?php

namespace App\Filament\ZbAdmin\Resources\SsbLoanResource\Pages;

use App\Filament\ZbAdmin\Resources\SsbLoanResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSsbLoan extends ViewRecord
{
    protected static string $resource = SsbLoanResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Application Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_code')
                            ->label('Reference Code'),
                        Infolists\Components\TextEntry::make('current_step')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'in_review' => 'warning',
                                default => 'primary',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Applicant Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant_name')
                            ->label('Full Name')
                            ->getStateUsing(function ($record) {
                                $responses = $record->form_data['formResponses'] ?? [];
                                return trim(($responses['firstName'] ?? '') . ' ' . ($responses['lastName'] ?? ''));
                            }),
                        Infolists\Components\TextEntry::make('ec_number')
                            ->label('EC Number')
                            ->getStateUsing(fn ($record) => $record->form_data['formResponses']['ecNumber'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Phone')
                            ->getStateUsing(fn ($record) => $record->form_data['formResponses']['phoneNumber'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->getStateUsing(fn ($record) => $record->form_data['formResponses']['emailAddress'] ?? 'N/A'),
                    ])->columns(2),

                Infolists\Components\Section::make('Loan Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('product')
                            ->label('Product')
                            ->getStateUsing(fn ($record) => $record->form_data['productName'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Loan Amount')
                            ->getStateUsing(function ($record) {
                                $amount = $record->form_data['finalPrice'] ?? 0;
                                return '$' . number_format($amount, 2);
                            }),
                        Infolists\Components\TextEntry::make('duration')
                            ->label('Duration')
                            ->getStateUsing(fn ($record) => ($record->form_data['creditDuration'] ?? 0) . ' months'),
                        Infolists\Components\TextEntry::make('installment')
                            ->label('Monthly Installment')
                            ->getStateUsing(function ($record) {
                                $amount = $record->form_data['monthlyInstallment'] ?? 0;
                                return '$' . number_format($amount, 2);
                            }),
                    ])->columns(2),
            ]);
    }
}
