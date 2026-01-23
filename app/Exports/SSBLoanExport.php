<?php

namespace App\Exports;

use App\Models\ApplicationState;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SSBLoanExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Get the collection of approved SSB loan applications
     */
    public function collection()
    {
        return ApplicationState::query()
            ->whereIn('current_step', ['approved', 'completed'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')) = 'government-ssb'")
            ->orderBy('approved_at', 'desc')
            ->get();
    }

    /**
     * Define the CSV headings
     */
    public function headings(): array
    {
        return [
            'DATE',
            'BRANCH',
            'SURNAME',
            'NAME',
            'EC NUMBER',
            'ID NUMBER',
            'PRODUCT',
            'PRICE',
            'INSTALLMENT',
            'PERIOD',
            'MOBILE',
            'ADDRESS',
            'NEXT OF KIN',
        ];
    }

    /**
     * Map each row
     */
    public function map($application): array
    {
        $formData = $application->form_data;
        $formResponses = $formData['formResponses'] ?? [];

        return [
            $application->approved_at ? $application->approved_at->format('Y-m-d') : date('Y-m-d'),
            'WESTEND', // Always defaults to WESTEND
            $formResponses['lastName'] ?? '',
            $formResponses['firstName'] ?? '',
            $formResponses['ecNumber'] ?? '',
            $formResponses['nationalIdNumber'] ?? '',
            $formData['productName'] ?? '',
            $formData['finalPrice'] ?? '',
            $formData['monthlyInstallment'] ?? '',
            $formData['creditDuration'] ?? '',
            $formResponses['phoneNumber'] ?? '',
            $formResponses['residentialAddress'] ?? '',
            $formResponses['nextOfKinName'] ?? '',
        ];
    }
}
