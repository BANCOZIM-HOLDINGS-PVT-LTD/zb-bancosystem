<?php

namespace App\Filament\Resources\PDFManagementResource\Pages;

use App\Filament\Resources\PDFManagementResource;
use Filament\Actions;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPDFManagement extends ViewRecord
{
    protected static string $resource = PDFManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('admin.pdf.download', $this->record->session_id))
                ->openUrlInNewTab(),

            Actions\Action::make('regenerate_pdf')
                ->label('Regenerate PDF')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $response = app(\App\Http\Controllers\Admin\PDFManagementController::class)
                        ->regenerate($this->record->session_id);

                    if ($response->getStatusCode() === 200) {
                        \Filament\Notifications\Notification::make()
                            ->title('PDF Regenerated Successfully')
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
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Application Details')
                    ->schema([
                        TextEntry::make('session_id')
                            ->label('Session ID')
                            ->copyable(),

                        TextEntry::make('channel')
                            ->label('Channel')
                            ->badge(),

                        TextEntry::make('current_step')
                            ->label('Current Status')
                            ->badge(),

                        TextEntry::make('created_at')
                            ->label('Application Date')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(2),

                Section::make('Applicant Information')
                    ->schema([
                        TextEntry::make('applicant_name')
                            ->label('Full Name')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data;
                                $responses = $formData['formResponses'] ?? [];
                                $firstName = $responses['firstName'] ?? $formData['firstName'] ?? '';
                                $surname = $responses['surname'] ?? $formData['surname'] ?? '';

                                return trim($firstName.' '.$surname) ?: 'N/A';
                            }),

                        TextEntry::make('email')
                            ->label('Email')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data;
                                $responses = $formData['formResponses'] ?? [];

                                return $responses['emailAddress'] ?? $responses['email'] ?? $formData['emailAddress'] ?? 'N/A';
                            }),

                        TextEntry::make('mobile')
                            ->label('Mobile Number')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data;
                                $responses = $formData['formResponses'] ?? [];

                                return $responses['mobile'] ?? $formData['mobile'] ?? 'N/A';
                            }),

                        TextEntry::make('national_id')
                            ->label('National ID')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data;
                                $responses = $formData['formResponses'] ?? [];

                                return $responses['nationalIdNumber'] ?? $responses['nationalID'] ?? $formData['nationalIdNumber'] ?? 'N/A';
                            }),

                        TextEntry::make('employer')
                            ->label('Employer')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data;
                                $employer = $formData['employer'] ?? '';
                                // Map employer codes to names
                                $employerMap = [
                                    'goz-ssb' => 'Government of Zimbabwe - SSB',
                                    'goz-non-ssb' => 'Government of Zimbabwe - Non-SSB',
                                    'private' => 'Private Sector',
                                    'self-employed' => 'Self Employed',
                                ];

                                return $employerMap[$employer] ?? $employer ?: 'N/A';
                            }),

                        TextEntry::make('amount')
                            ->label('Loan Amount')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data;
                                $amount = $formData['amount'] ?? $formData['loanAmount'] ?? 0;

                                return '$'.number_format($amount, 2);
                            }),
                    ])->columns(2),

                Section::make('Form Data')
                    ->schema([
                        KeyValueEntry::make('form_data')
                            ->label('Complete Form Data')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->getStateUsing(function () {
                                $formData = $this->record->form_data ?? [];

                                return $this->flattenArray($formData);
                            }),
                    ])->collapsible(),

                Section::make('Metadata')
                    ->schema([
                        KeyValueEntry::make('metadata')
                            ->label('Application Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->getStateUsing(function () {
                                $metadata = $this->record->metadata ?? [];

                                return $this->flattenArray($metadata);
                            }),
                    ])->collapsible(),
            ]);
    }

    /**
     * Flatten a multi-dimensional array into key-value pairs
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                // Recursively flatten nested arrays
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                // Convert non-string values to string representation
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (is_null($value)) {
                    $value = 'N/A';
                } elseif (! is_string($value)) {
                    $value = (string) $value;
                }

                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
