<?php

namespace App\Services;

class ZimbabweanIDValidator
{
    /**
     * Known district codes (partial list - can be expanded)
     */
    private const VALID_DISTRICT_CODES = [
        '08', // Bulawayo
        '63', // Harare
        '03', // Mberengwa
        '21', // Insiza
        '01', '02', '04', '05', '06', '07', '09', '10',
        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
        '22', '23', '24', '25', '26', '27', '28', '29', '30',
        '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
        '41', '42', '43', '44', '45', '46', '47', '48', '49', '50',
        '51', '52', '53', '54', '55', '56', '57', '58', '59', '60',
        '61', '62', '64', '65', '66', '67', '68', '69', '70'
    ];

    /**
     * District name mappings
     */
    private const DISTRICT_NAMES = [
        '08' => 'Bulawayo',
        '63' => 'Harare',
        '03' => 'Mberengwa',
        '21' => 'Insiza'
    ];

    /**
     * Validates a Zimbabwean National ID number
     *
     * Format: XX-XXXXXXX-Y-ZZ
     * - XX: District code (2 digits)
     * - XXXXXXX: Registration number (6-7 digits)
     * - Y: Letter (1 character A-Z)
     * - ZZ: Check digits (2 digits)
     *
     * @param string $id The ID number to validate
     * @return array ['valid' => bool, 'message' => string, 'formatted' => string|null]
     */
    public static function validate(string $id): array
    {
        if (empty($id)) {
            return [
                'valid' => false,
                'message' => 'ID number is required',
                'formatted' => null
            ];
        }

        // Remove spaces and convert to uppercase
        $cleanedId = strtoupper(trim(preg_replace('/\s+/', '', $id)));

        // Pattern with dashes: XX-XXXXXXX-Y-ZZ
        $patternWithDashes = '/^(\d{2})-(\d{6,7})-([A-Z])-(\d{2})$/';

        // Pattern without dashes: XXXXXXXXXXXX or XXXXXXXXXXX
        $patternWithoutDashes = '/^(\d{2})(\d{6,7})([A-Z])(\d{2})$/';

        $matches = [];
        if (!preg_match($patternWithDashes, $cleanedId, $matches) &&
            !preg_match($patternWithoutDashes, $cleanedId, $matches)) {
            return [
                'valid' => false,
                'message' => 'Invalid ID format. Expected format: XX-XXXXXXX-Y-ZZ (e.g., 08-2047823-Q-29)',
                'formatted' => null
            ];
        }

        [, $districtCode, $registrationNumber, $letter, $checkDigits] = $matches;

        // Validate district code
        if (!in_array($districtCode, self::VALID_DISTRICT_CODES, true)) {
            return [
                'valid' => false,
                'message' => "Invalid district code: {$districtCode}. Please verify your ID number.",
                'formatted' => null
            ];
        }

        // Validate registration number length
        if (strlen($registrationNumber) < 6 || strlen($registrationNumber) > 7) {
            return [
                'valid' => false,
                'message' => 'Registration number must be 6-7 digits',
                'formatted' => null
            ];
        }

        // Validate check digits
        if (strlen($checkDigits) !== 2) {
            return [
                'valid' => false,
                'message' => 'Check digits must be exactly 2 digits',
                'formatted' => null
            ];
        }

        // Format the ID with dashes for consistency
        $formatted = "{$districtCode}-{$registrationNumber}-{$letter}-{$checkDigits}";

        return [
            'valid' => true,
            'message' => 'Valid Zimbabwean National ID',
            'formatted' => $formatted
        ];
    }

    /**
     * Formats a Zimbabwean ID to the standard format with dashes
     *
     * @param string $id The ID number to format
     * @return string The formatted ID or original if invalid
     */
    public static function format(string $id): string
    {
        $result = self::validate($id);
        return $result['formatted'] ?? $id;
    }

    /**
     * Extracts the district code from a Zimbabwean ID
     *
     * @param string $id The ID number
     * @return string|null The district code or null if invalid
     */
    public static function getDistrictCode(string $id): ?string
    {
        $cleanedId = strtoupper(trim(preg_replace('/\s+/', '', $id)));
        if (preg_match('/^(\d{2})/', $cleanedId, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Gets the district name from the district code
     *
     * @param string $districtCode The district code
     * @return string The district name or 'Unknown District'
     */
    public static function getDistrictName(string $districtCode): string
    {
        return self::DISTRICT_NAMES[$districtCode] ?? 'Unknown District';
    }

    /**
     * Checks if an ID is valid (simple boolean check)
     *
     * @param string $id The ID number to check
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $id): bool
    {
        return self::validate($id)['valid'];
    }
}