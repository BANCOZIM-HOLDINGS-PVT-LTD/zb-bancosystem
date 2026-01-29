<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * FCB Service
 * 
 * Handles integration with Financial Clearing Bureau (FCB).
 * Currently a placeholder returning mock data for PDF generation.
 */
class FCBService
{
    /**
     * Check credit status for a given National ID.
     * 
     * @param string $nationalId
     * @return array
     */
    public function checkCreditStatus(string $nationalId): array
    {
        // IMPORTANT: This is MOCK data for development/testing only
        // TODO: Implement real FCB API integration before production use
        // Real FCB API endpoint should be configured via environment variables:
        //   FCB_API_URL, FCB_API_KEY, FCB_API_SECRET

        if (app()->environment('production')) {
            Log::warning('[FCB Service] MOCK DATA IN USE - Real FCB integration required!', [
                'national_id' => substr($nationalId, 0, 4) . '****',
            ]);
        }

        Log::info('[FCB Service] Returning mock credit status', ['national_id' => $nationalId]);

        // Mock response structure matching the PDF report
        // WARNING: This always returns "GOOD" status - NOT suitable for production
        return [
            'report_serial' => '2026' . rand(10000, 99999),
            'report_date' => now()->format('d-M-Y H:i'),
            'fcb_score' => 230,
            'status' => 'GOOD', // Color code: Blue for GOOD
            'status_color' => '#0000FF', // Blue
            'score_color' => '#FFFF00', // Yellow
            
            'individual_details' => [
                'nationality' => 'ZIMBABWE',
                'date_of_birth' => '1990-01-01', // Should ideally come from ID parsing or user data
                'national_id' => $nationalId,
                'gender' => 'MALE',
                'mobile' => '0771234567',
                'property_status' => 'Rented',
                'property_density' => 'Medium',
                'address' => '123 SAMORA MACHEL AVENUE, HARARE',
                'marital_status' => 'MARRIED',
            ],

            'addresses' => [
                [
                    'date' => '30-Jul-2025',
                    'street' => '123 SAMORA MACHEL AVENUE, HARARE',
                    'city' => 'HARARE',
                    'country' => 'ZIMBABWE',
                    'rights' => 'Rented'
                ],
                [
                    'date' => '15-Mar-2023',
                    'street' => '456  BORROWDALE ROAD, HARARE',
                    'city' => 'HARARE',
                    'country' => 'ZIMBABWE',
                    'rights' => 'Rented'
                ]
            ],

            'previous_searches' => [
                [
                    'date' => '30-Jul-2025',
                    'event_type' => 'NEW LOAN APPLICATION',
                    'counterparty' => 'BANC ABC',
                    'branch' => 'BancEasy',
                    'score' => 230,
                    'status' => 'GOOD'
                ],
                [
                    'date' => '22-Jan-2024',
                    'event_type' => 'NEW LOAN APPLICATION',
                    'counterparty' => 'ZB BANK',
                    'branch' => 'Rotten Row',
                    'score' => 210,
                    'status' => 'GOOD'
                ]
            ],

            'reported_incomes' => [
                [
                    'date' => '30-Jul-2025',
                    'employer' => 'MINISTRY OF HEALTH',
                    'industry' => 'N/A',
                    'salary_band' => 'OVER 10 000',
                    'occupation' => 'N/A'
                ]
            ],

            'directorships' => [], // Empty array implies "No records found"
            'active_credit_events' => [],
            'settled_events' => [],
            'exposures' => [],
            'convictions' => [],
        ];
    }
}
