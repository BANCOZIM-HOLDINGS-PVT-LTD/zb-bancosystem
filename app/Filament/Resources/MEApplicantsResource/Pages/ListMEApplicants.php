<?php

namespace App\Filament\Resources\MEApplicantsResource\Pages;

use App\Filament\Resources\MEApplicantsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMEApplicants extends ListRecords
{
    protected static string $resource = MEApplicantsResource::class;
    
    protected ?string $heading = 'M&E Successful Applicants';
    
    protected ?string $subheading = 'Approved loan applications with M&E and Training packages ready for delivery';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export All to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $records = $this->getTableQuery()->get();
                    
                    return response()->streamDownload(function () use ($records) {
                        $handle = fopen('php://output', 'w');
                        fputcsv($handle, ['App #', 'National ID', 'Name', 'Phone', 'Product', 'Amount', 'Status', 'Applied Date']);
                        
                        foreach ($records as $record) {
                            $formData = $record->form_data ?? [];
                            $formResponses = $formData['formResponses'] ?? $formData;
                            $firstName = $formResponses['firstName'] ?? '';
                            $lastName = $formResponses['lastName'] ?? '';
                            $phone = $formResponses['mobile'] ?? $formResponses['phoneNumber'] ?? '';
                            
                            fputcsv($handle, [
                                'ZB' . date('Y') . str_pad($record->id, 6, '0', STR_PAD_LEFT),
                                $record->reference_code ?? 'N/A',
                                trim($firstName . ' ' . $lastName),
                                $phone,
                                data_get($formData, 'selectedBusiness.name', 'N/A'),
                                data_get($formData, 'finalPrice', 0),
                                $record->current_step,
                                $record->created_at->format('Y-m-d'),
                            ]);
                        }
                        
                        fclose($handle);
                    }, 'me_applicants_all_' . now()->format('Y-m-d') . '.csv');
                }),
        ];
    }
}
