<?php

namespace App\Filament\ZbAdmin\Resources\SmeLoanResource\Pages;

use App\Filament\ZbAdmin\Resources\SmeLoanResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSmeLoan extends ViewRecord
{
    protected static string $resource = SmeLoanResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Application Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('application_number')
                            ->label('Application Number'),
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
                        Infolists\Components\TextEntry::make('sme_stage')
                            ->label('SME Stage')
                            ->getStateUsing(function ($record) {
                                $metadata = $record->metadata ?? [];
                                $stage = $metadata['sme_stage'] ?? 'submitted';
                                return match ($stage) {
                                    'submitted' => 'Submitted',
                                    'sme_document_review' => 'Document Review',
                                    'sme_credit_assessment' => 'Credit Assessment',
                                    'sme_committee_review' => 'Committee Review',
                                    'sme_pending_documents' => 'Pending Documents',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    default => ucfirst(str_replace('_', ' ', $stage)),
                                };
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                'Committee Review' => 'warning',
                                default => 'primary',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Business Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('company_type')
                            ->label('Company Type')
                            ->getStateUsing(function ($record) {
                                $metadata = $record->metadata ?? [];
                                return $metadata['company_type_name'] ?? ($record->form_data['companyTypeName'] ?? 'N/A');
                            })
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('company_structure')
                            ->label('Company Structure')
                            ->getStateUsing(function ($record) {
                                $companyType = $record->form_data['companyType'] ?? '';
                                return match ($companyType) {
                                    'sole_trader' => 'Sole Trader',
                                    'partnership' => 'Partnership',
                                    'private_limited' => 'Private Limited Company',
                                    'cooperative' => 'Co-operative',
                                    'trust' => 'Trust',
                                    default => 'Not specified',
                                };
                            }),
                        Infolists\Components\TextEntry::make('product')
                            ->label('Product/Business')
                            ->getStateUsing(fn ($record) => $record->form_data['productName'] ?? $record->form_data['business'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('category')
                            ->label('Category')
                            ->getStateUsing(fn ($record) => $record->form_data['category'] ?? 'N/A'),
                    ])->columns(2),

                Infolists\Components\Section::make('Applicant Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant_name')
                            ->label('Full Name')
                            ->getStateUsing(function ($record) {
                                $responses = $record->form_data['formResponses'] ?? [];
                                return trim(($responses['firstName'] ?? '') . ' ' . ($responses['lastName'] ?? $responses['surname'] ?? ''));
                            }),
                        Infolists\Components\TextEntry::make('national_id')
                            ->label('National ID')
                            ->getStateUsing(fn ($record) => $record->form_data['formResponses']['nationalIdNumber'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Phone')
                            ->getStateUsing(fn ($record) => $record->form_data['formResponses']['mobile'] ?? $record->form_data['formResponses']['phoneNumber'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->getStateUsing(fn ($record) => $record->form_data['formResponses']['emailAddress'] ?? 'N/A'),
                    ])->columns(2),

                Infolists\Components\Section::make('Loan Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('gross_loan')
                            ->label('Gross Loan')
                            ->getStateUsing(function ($record) {
                                $amount = $record->form_data['grossLoan'] ?? $record->form_data['amount'] ?? 0;
                                return '$' . number_format($amount, 2);
                            }),
                        Infolists\Components\TextEntry::make('net_loan')
                            ->label('Net Loan')
                            ->getStateUsing(function ($record) {
                                $amount = $record->form_data['netLoan'] ?? $record->form_data['finalPrice'] ?? 0;
                                return '$' . number_format($amount, 2);
                            }),
                        Infolists\Components\TextEntry::make('duration')
                            ->label('Duration')
                            ->getStateUsing(fn ($record) => ($record->form_data['creditTerm'] ?? $record->form_data['creditDuration'] ?? 0) . ' months'),
                        Infolists\Components\TextEntry::make('installment')
                            ->label('Monthly Installment')
                            ->getStateUsing(function ($record) {
                                $amount = $record->form_data['monthlyPayment'] ?? $record->form_data['monthlyInstallment'] ?? 0;
                                return '$' . number_format($amount, 2);
                            }),
                        Infolists\Components\TextEntry::make('interest_rate')
                            ->label('Interest Rate')
                            ->getStateUsing(fn ($record) => $record->form_data['interestRate'] ?? 'N/A'),
                    ])->columns(3),

                Infolists\Components\Section::make('SME Workflow History')
                    ->schema([
                        Infolists\Components\TextEntry::make('sme_submitted_at')
                            ->label('Submitted')
                            ->getStateUsing(fn ($record) => $record->metadata['sme_submitted_at'] ?? 'N/A'),
                        Infolists\Components\TextEntry::make('sme_doc_review')
                            ->label('Doc Review Started')
                            ->getStateUsing(fn ($record) => $record->metadata['sme_sme_document_review_at'] ?? '—'),
                        Infolists\Components\TextEntry::make('sme_credit')
                            ->label('Credit Assessment')
                            ->getStateUsing(fn ($record) => $record->metadata['sme_sme_credit_assessment_at'] ?? '—'),
                        Infolists\Components\TextEntry::make('sme_committee')
                            ->label('Committee Review')
                            ->getStateUsing(fn ($record) => $record->metadata['sme_sme_committee_review_at'] ?? '—'),
                    ])->columns(4),
            ]);
    }
}
