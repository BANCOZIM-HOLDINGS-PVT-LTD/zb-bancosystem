<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeliveryEstimatorService
{
    /**
     * Calculate expected delivery date based on parsed delivery data.
     * 
     * The input data is expected to be an array representing a row from the parsed Excel file
     * or a data array from the delivery tracking form.
     * 
     * @param array $data Parsed data containing:
     *                    - 'dispatch_date' (string|null)
     *                    - 'courier' (string|null)
     *                    - 'destination' (string|null)
     *                    - 'product_type' (string|null)
     * @return Carbon
     */
    public function calculateExpectedDeliveryDate(array $data): Carbon
    {
        try {
            // Default to dispatch date or today if not provided
            $dispatchDate = isset($data['dispatch_date']) && !empty($data['dispatch_date'])
                ? Carbon::parse($data['dispatch_date']) 
                : Carbon::now();
                
            $courier = $data['courier'] ?? 'Zim Post Office';
            $destination = strtolower($data['destination'] ?? '');
            
            // Estimation Logic (Business Days)
            $daysToAdd = 3; // Default buffer
            
            if ($courier === 'Gain Cash & Carry') {
                // Depot collection is usually faster/predictable
                $daysToAdd = 2; 
            } elseif ($courier === 'Zim Post Office') {
                // Post Office estimates
                if (str_contains($destination, 'harare') || str_contains($destination, 'bulawayo')) {
                    $daysToAdd = 2;
                } else {
                    // Rural / Other cities
                    $daysToAdd = 4;
                }
            } elseif ($courier === 'Swift') {
                $daysToAdd = 2;
            } elseif ($courier === 'Bus Courier') {
                $daysToAdd = 1; // Same day or next day usually
            }

            // Calculate business days
            return $dispatchDate->addWeekdays($daysToAdd);
            
        } catch (\Exception $e) {
            Log::error("Error calculating expected delivery date: " . $e->getMessage());
            return Carbon::now()->addDays(3); // Fallback
        }
    }
}
