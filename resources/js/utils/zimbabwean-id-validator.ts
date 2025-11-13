/**
 * Zimbabwean National ID Validator
 *
 * Format: XX-XXXXXXX-Y-ZZ
 * - XX: District code (2 digits)
 * - XXXXXXX: Registration number (6-7 digits)
 * - Y: Letter (1 character A-Z)
 * - ZZ: Check digits (2 digits)
 *
 * Total: 11-12 characters (without dashes)
 * Examples: 08-2047823-Q-29, 082047823Q29, 63-1234567-A-12
 */

export interface ZimbabweanIDValidationResult {
  valid: boolean;
  message?: string;
  formatted?: string;
}

// Known district codes (partial list - can be expanded)
const VALID_DISTRICT_CODES = [
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
 * Validates a Zimbabwean National ID number
 */
export function validateZimbabweanID(id: string): ZimbabweanIDValidationResult {
  if (!id || typeof id !== 'string') {
    return {
      valid: false,
      message: 'ID number is required'
    };
  }

  // Remove spaces and convert to uppercase
  const cleanedId = id.trim().replace(/\s+/g, '').toUpperCase();

  // Pattern 1: With dashes (XX-XXXXXXX-Y-ZZ)
  const patternWithDashes = /^(\d{2})-(\d{6,7})-([A-Z])-(\d{2})$/;

  // Pattern 2: Without dashes (XXXXXXXXXXXX or XXXXXXXXXXX)
  const patternWithoutDashes = /^(\d{2})(\d{6,7})([A-Z])(\d{2})$/;

  let match = cleanedId.match(patternWithDashes) || cleanedId.match(patternWithoutDashes);

  if (!match) {
    return {
      valid: false,
      message: 'Invalid ID format. Expected format: XX-XXXXXXX-Y-ZZ (e.g., 08-2047823-Q-29)'
    };
  }

  const [, districtCode, registrationNumber, letter, checkDigits] = match;

  // Validate district code
  if (!VALID_DISTRICT_CODES.includes(districtCode)) {
    return {
      valid: false,
      message: `Invalid district code: ${districtCode}. Please verify your ID number.`
    };
  }

  // Validate registration number length
  if (registrationNumber.length < 6 || registrationNumber.length > 7) {
    return {
      valid: false,
      message: 'Registration number must be 6-7 digits'
    };
  }

  // Validate check digits
  if (checkDigits.length !== 2) {
    return {
      valid: false,
      message: 'Check digits must be exactly 2 digits'
    };
  }

  // Format the ID with dashes for consistency
  const formatted = `${districtCode}-${registrationNumber}-${letter}-${checkDigits}`;

  return {
    valid: true,
    formatted,
    message: 'Valid Zimbabwean National ID'
  };
}

/**
 * Formats a Zimbabwean ID to the standard format with dashes
 */
export function formatZimbabweanID(id: string): string {
  const result = validateZimbabweanID(id);
  return result.formatted || id;
}

/**
 * Extracts the district code from a Zimbabwean ID
 */
export function getDistrictCode(id: string): string | null {
  const cleanedId = id.trim().replace(/\s+/g, '').toUpperCase();
  const match = cleanedId.match(/^(\d{2})/);
  return match ? match[1] : null;
}

/**
 * Gets the district name from the district code (partial mapping)
 */
export function getDistrictName(districtCode: string): string {
  const districtMap: Record<string, string> = {
    '08': 'Bulawayo',
    '63': 'Harare',
    '03': 'Mberengwa',
    '21': 'Insiza'
    // Add more district mappings as needed
  };
  return districtMap[districtCode] || 'Unknown District';
}