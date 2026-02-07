<?php

namespace App\Exports;

use App\Models\ApplicationState;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ZBLoanExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Get the collection of approved ZB loan applications
     */
    public function collection()
    {
        return ApplicationState::query()
            ->whereIn('current_step', ['approved', 'completed'])
            ->where(function ($query) {
                // ZB applications (has account or wants account)
                $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                if ($isPgsql) {
                    $query->whereRaw("form_data->>'hasAccount' = 'true'")
                          ->orWhereRaw("form_data->>'wantsAccount' = 'true'");
                } else {
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'")
                          ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.wantsAccount')) = 'true'");
                }
            })
            ->where(function ($query) {
                // Exclude SSB applications
                $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                if ($isPgsql) {
                    $query->whereRaw("COALESCE(form_data->>'employer', '') != 'government-ssb'");
                } else {
                    $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'");
                }
            })
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
