@php
    // DEFENSIVE: Ensure all required variables are defined to prevent "Undefined variable" errors
    // These variables are used in closure `use` clauses which require the variables to exist
    $formResponses = $formResponses ?? [];
    $documents = $documents ?? [];
    $selfieImage = $selfieImage ?? '';
    $signatureImage = $signatureImage ?? '';

    // Helper to format address objects into readable strings
    $formatAddress = function($address) {
        // Try to decode JSON string first
        if (is_string($address)) {
            $decoded = json_decode($address, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $address = $decoded;
            } else {
                // If it's not JSON, check if it looks like a plain address string
                // (doesn't start with { or [)
                if (!str_starts_with(trim($address), '{') && !str_starts_with(trim($address), '[')) {
                    return $address;
                }
                // Otherwise it's malformed JSON, return empty
                return '';
            }
        }

        if (is_array($address) || is_object($address)) {
            $address = (array)$address;
            $parts = [];

            // NEW: Handle simplified Urban/Rural format
            if (isset($address['type'])) {
                if ($address['type'] === 'urban') {
                    if (!empty($address['addressLine'])) {
                        $parts[] = $address['addressLine'];
                    }
                    if (!empty($address['city'])) {
                        $parts[] = $address['city'];
                    }
                } elseif ($address['type'] === 'rural') {
                    if (!empty($address['addressLine'])) {
                        $parts[] = $address['addressLine'];
                    }
                    if (!empty($address['wardDistrict'])) {
                        $parts[] = $address['wardDistrict'];
                    }
                }
                return implode(', ', array_filter($parts));
            }

            // LEGACY: Add house/building number
            if (!empty($address['houseNumber'])) {
                $parts[] = $address['houseNumber'];
            }

            // Add street name
            if (!empty($address['streetName'])) {
                $parts[] = $address['streetName'];
            }

            // Add suburb
            if (!empty($address['suburb'])) {
                $parts[] = $address['suburb'];
            }

            // Add city
            if (!empty($address['city'])) {
                $parts[] = $address['city'];
            }

            // Add province if available
            if (!empty($address['province'])) {
                $parts[] = $address['province'];
            }

            // Add district for rural addresses
            if (!empty($address['district'])) {
                $parts[] = $address['district'];
            }

            // Add ward for rural addresses
            if (!empty($address['ward'])) {
                $parts[] = 'Ward ' . $address['ward'];
            }

            // Add village for rural addresses
            if (!empty($address['village'])) {
                $parts[] = $address['village'];
            }

            return implode(', ', array_filter($parts));
        }

        return '';
    };

    // Helper to safely get form response values
    $get = function($key, $default = '') use ($formResponses, $formatAddress) {
        $value = $formResponses[$key] ?? $default;

        // Special handling for address fields
        if (strpos($key, 'Address') !== false || strpos($key, 'address') !== false) {
            return $formatAddress($value);
        }

        return is_array($value) ? $default : (string)$value;
    };

    // Helper to get spouse details from spouseDetails array
    $getSpouse = function($key, $default = '') use ($formResponses, $formatAddress) {
        $spouse = $formResponses['spouseDetails'][0] ?? [];
        $value = $spouse[$key] ?? $default;
        if (strpos($key, 'Address') !== false || strpos($key, 'address') !== false) {
            return $formatAddress($value);
        }
        return is_array($value) ? $default : (string)$value;
    };

    // Helper function for checkboxes
    $isChecked = function($key, $value) use ($formResponses) {
        $fieldValue = $formResponses[$key] ?? '';
        // Handle employerType which is an object with boolean keys
        if ($key === 'employerType' && is_array($fieldValue)) {
            $map = [
                'Government' => 'government',
                'Local Company' => 'localCompany',
                'Multinational' => 'multinational',
                'NGO' => 'ngo',
                'Other' => 'other',
            ];
            $subKey = $map[$value] ?? '';
            return !empty($fieldValue[$subKey]);
        }
        if (is_array($fieldValue)) {
            return in_array($value, $fieldValue);
        }
        return $fieldValue === $value || $fieldValue == $value;
    };

    // Helper to get image data for embedding
    $getImageData = function($imageKey) use ($formResponses, $documents, $selfieImage, $signatureImage) {
        try {
            // Check if image data is already processed and passed as variable
            if ($imageKey === 'selfie' && !empty($selfieImage)) {
                $img = $selfieImage;
                // Ensure base64 prefix if missing
                if (is_string($img) && !str_starts_with($img, 'data:image')) {
                     // Assume PNG if unknown, or try to detect? Safest is to leave as is if we can't be sure, 
                     // but likely it needs prefix if it's raw base64.
                     // However, better to rely on robust checking.
                     // Let's check if it looks like base64
                     if (preg_match('/^[a-zA-Z0-9\/\+=]+$/', substr($img, 0, 100))) {
                         return 'data:image/png;base64,' . $img;
                     }
                }
                return $img;
            }
            if ($imageKey === 'signature' && !empty($signatureImage)) {
                 $img = $signatureImage;
                 if (is_string($img) && !str_starts_with($img, 'data:image') && preg_match('/^[a-zA-Z0-9\/\+=]+$/', substr($img, 0, 100))) {
                     return 'data:image/png;base64,' . $img;
                 }
                return $img;
            }

            // Check in documents array
            $imagePath = null;
            if (isset($documents[$imageKey])) {
                $imagePath = $documents[$imageKey];
            } elseif (isset($formResponses[$imageKey])) {
                $imagePath = $formResponses[$imageKey];
            }

            if (empty($imagePath)) {
                return null;
            }

            // If it's already base64 data URL
            if (is_string($imagePath) && str_starts_with($imagePath, 'data:image')) {
                return $imagePath;
            }

            // If it's a file path
            if (is_string($imagePath)) { 
                // Fix for potential public_path crash on null/empty which we handled above, but double check
                 $fullPath = public_path($imagePath);
                 if (file_exists($fullPath) && is_file($fullPath)) {
                    $imageData = file_get_contents($fullPath);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fullPath);
                    finfo_close($finfo);
                    return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }

                // Try storage path
                if (\Storage::disk('public')->exists($imagePath)) {
                    $imageData = \Storage::disk('public')->get($imagePath);
                    $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
                    $mimeType = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
                    // Fallback mime type
                    if (empty($mimeType) || $mimeType === 'image/') $mimeType = 'image/png';
                    
                    return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }
            }

            return null;
        } catch (\Exception $e) {
            // Log error if possible or just ignore to prevent PDF crash
            // \Log::error("PDF Image Error [$imageKey]: " . $e->getMessage());
            return null;
        }
    };

    $selfieImageData = $getImageData('selfie');
    $signatureImageData = $getImageData('signature');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZB Account Opening Form</title>
    <style>
        @page {
            margin: 12mm 10mm;
            size: A4;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
            text-align: right;
            font-size: 8pt;
            letter-spacing: 1px;
        }

        .logo {
            height: 40px;
        }

        .for-you {
            font-size: 18pt;
            color: #333;
            margin-left: 10px;
        }

        /* Page title */
        .page-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 8px 0 2px 0;
        }

        .page-subtitle {
            font-size: 7pt;
            font-style: italic;
            color: #00439C;
            margin-bottom: 5px;
        }

        /* Account number and service centre */
        .account-line {
            margin: 5px 0;
            font-size: 8pt;
        }

        .account-number-boxes {
            display: inline-block;
        }

        .num-box {
            display: inline-block;
            width: 15px;
            height: 18px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            margin: 0 1px;
            padding-top: 2px;
            font-weight: bold;
        }

        /* Section headers */
        .section-header {
            background-color: #8BC34A;
            color: white;
            font-weight: bold;
            padding: 4px 8px;
            font-size: 10pt;
            margin: 6px 0 4px 0;
        }

        .section-note {
            font-size: 7pt;
            font-style: italic;
            color: white;
        }

        /* Form table */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .form-table td {
            border: 1px solid #666;
            padding: 2px 4px;
            font-size: 8pt;
            vertical-align: middle;
        }

        .form-label {
            background-color: #f5f5f5;
            font-weight: normal;
            white-space: nowrap;
        }

        .form-value {
            background-color: white;
        }

        .italic-note {
            font-style: italic;
            color: #00439C;
            font-size: 7pt;
        }

        /* Checkboxes */
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin: 0 3px;
            vertical-align: middle;
        }

        .checkbox-checked::after {
            content: 'X';
            font-weight: bold;
            font-size: 10pt;
        }

        /* Page footer */
        .page-footer {
            position: fixed;
            bottom: 8mm;
            right: 10mm;
            font-size: 8pt;
            color: #666;
        }

        /* Terms text */
        .terms-list {
            font-size: 7pt;
            line-height: 1.3;
            margin: 3px 0;
        }

        .terms-list p {
            margin: 2px 0 2px 15px;
            text-indent: -15px;
        }

        .signature-box {
            border: 1px solid #000;
            min-height: 50px;
            padding: 5px;
        }

        .photo-box {
            border: 2px solid #000;
            min-height: 120px;
            width: 100px;
            text-align: center;
            padding: 5px;
        }
    </style>
</head>
<body>

<!-- PAGE 1 -->
<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo">
            <span class="for-you">for you</span>
        </div>
        <div class="header-right">
            BANKING | INVESTMENTS | INSURANCE
        </div>
    </div>

    <!-- Page Title -->
    <div class="page-title">Individual Customer Account Opening Application Form</div>
    <div class="page-subtitle">(Please complete in black or blue pen with clear CAPITAL LETTERS print)</div>

    <!-- Account Number -->
    <div class="account-line">
        <strong>Account Number:</strong> <span style="font-size: 7pt; color: #666;">(For Official Use Only)</span>
        <div class="account-number-boxes">
            <span class="num-box">4</span>
            <span class="num-box"></span>
            <span class="num-box"></span>
            —
            <span class="num-box"></span>
            <span class="num-box"></span>
            <span class="num-box"></span>
            —
            <span class="num-box"></span>
            <span class="num-box"></span>
            <span class="num-box"></span>
            <span class="num-box"></span>
            <span class="num-box"></span>
        </div>
    </div>

    <div class="account-line">
        <strong>Service Centre for Card Collection:</strong> {{ $get('serviceCenter') }}
    </div>

    <!-- Section A - Account Specifications -->
    <div class="section-header">
        A - ACCOUNT SPECIFICATIONS
    </div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 25%;">CURRENCY OF ACCOUNT:</td>
            <td class="form-value" colspan="5">
                ZWL$ <span class="checkbox {{ $isChecked('accountCurrency', 'ZWL$') ? 'checkbox-checked' : '' }}"></span>
                USD <span class="checkbox {{ $isChecked('accountCurrency', 'USD') ? 'checkbox-checked' : '' }}"></span>
                ZAR <span class="checkbox {{ $isChecked('accountCurrency', 'ZAR') ? 'checkbox-checked' : '' }}"></span>
                BWP <span class="checkbox {{ $isChecked('accountCurrency', 'BWP') ? 'checkbox-checked' : '' }}"></span>
                EURO <span class="checkbox {{ $isChecked('accountCurrency', 'EURO') ? 'checkbox-checked' : '' }}"></span>
                OTHER <span class="checkbox {{ $isChecked('accountCurrency', 'OTHER (Indicate)') ? 'checkbox-checked' : '' }}"></span>
            </td>
        </tr>
    </table>

    <!-- Section B - Customer Personal Details -->
    <div class="section-header">B - CUSTOMER PERSONAL DETAILS</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 8%;">Title:</td>
            <td class="form-value" style="width: 25%;">
                Mr <span class="checkbox {{ $isChecked('title', 'Mr') ? 'checkbox-checked' : '' }}"></span>
                Mrs <span class="checkbox {{ $isChecked('title', 'Mrs') ? 'checkbox-checked' : '' }}"></span>
                Ms <span class="checkbox {{ $isChecked('title', 'Ms') ? 'checkbox-checked' : '' }}"></span>
                Dr <span class="checkbox {{ $isChecked('title', 'Dr') ? 'checkbox-checked' : '' }}"></span>
                Prof <span class="checkbox {{ $isChecked('title', 'Prof') ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label" style="width: 12%;">First Name:</td>
            <td class="form-value" style="width: 22%;">{{ $get('firstName') }}</td>
            <td class="form-label" style="width: 10%;">Surname:</td>
            <td class="form-value" style="width: 23%;">{{ $get('surname') }}</td>
        </tr>
        <tr>
            <td class="form-label">Maiden Name:</td>
            <td class="form-value" colspan="2">{{ $get('maidenName') }}</td>
            <td class="form-label">Other Name(s):</td>
            <td class="form-value" colspan="2">{{ $get('otherNames') }}</td>
        </tr>
        <tr>
            <td class="form-label">Date of Birth:</td>
            <td class="form-value">{{ $get('dateOfBirth') }}</td>
            <td class="form-label">Place of Birth:</td>
            <td class="form-value">{{ $get('placeOfBirth') }}</td>
            <td class="form-label">Nationality:</td>
            <td class="form-value">{{ $get('nationality') }}</td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Marital Status:</td>
            <td class="form-value" colspan="2">
                Single <span class="checkbox {{ $isChecked('maritalStatus', 'Single') ? 'checkbox-checked' : '' }}"></span>
                Married <span class="checkbox {{ $isChecked('maritalStatus', 'Married') ? 'checkbox-checked' : '' }}"></span>
                Other <span class="checkbox {{ $isChecked('maritalStatus', 'Other') ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label">Citizenship:</td>
            <td class="form-value">{{ $get('citizenship') }}</td>
        </tr>
        <tr>
            <td class="form-label" style="width: 8%;">Dependents:</td>
            <td class="form-value" style="width: 25%;">{{ $get('dependents') }}</td>
            <td class="form-label" colspan="2">National ID Number:</td>
            <td class="form-value" colspan="2">{{ $get('nationalIdNumber') }}</td>
        </tr>
        <tr>
            <td class="form-label" colspan="2"></td>
            <td class="form-label">Driver's License No:</td>
            <td class="form-value" colspan="3">{{ $get('driversLicense') }}</td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Passport Number:</td>
            <td class="form-value" colspan="2">{{ $get('passportNumber') }}</td>
            <td class="form-label">Expiry Date:</td>
            <td class="form-value">{{ $get('passportExpiry') }}</td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Country of Residence:</td>
            <td class="form-value" colspan="2">{{ $get('countryOfResidence') }}</td>
            <td class="form-label">Gender:</td>
            <td class="form-value">
                Male <span class="checkbox {{ $isChecked('gender', 'Male') ? 'checkbox-checked' : '' }}"></span>
                Female <span class="checkbox {{ $isChecked('gender', 'Female') ? 'checkbox-checked' : '' }}"></span>
            </td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Highest Educational Qualification:</td>
            <td class="form-value" colspan="2">{{ $get('highestEducation') }}</td>
            <td class="form-label">Hobbies:</td>
            <td class="form-value">{{ $get('hobbies') }}</td>
        </tr>
    </table>

    <!-- Section C - Customer Contact Details -->
    <div class="section-header">C - CUSTOMER CONTACT DETAILS</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 20%;">Residential Address:</td>
            <td class="form-value" colspan="5">{{ $get('residentialAddress') }}</td>
        </tr>
        <tr>
            <td class="form-label">Telephone:</td>
            <td class="form-value">Res: {{ $get('telephoneRes') }}</td>
            <td class="form-value">Mobile: +263-{{ $get('mobile') }}</td>
            <td class="form-value" colspan="2">Bus: {{ $get('bus') }}</td>
        </tr>
        <tr>
            <td class="form-label">Email Address:</td>
            <td class="form-value" colspan="5">{{ $get('emailAddress') }}</td>
        </tr>
    </table>

    <!-- Section D - Customer Employment Details -->
    <div class="section-header">D - CUSTOMER EMPLOYMENT DETAILS</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 20%;">Employer Name:</td>
            <td class="form-value" style="width: 45%;">{{ $get('employerName') }}</td>
            <td class="form-label" style="width: 12%;">Occupation:</td>
            <td class="form-value" style="width: 23%;">{{ $get('occupation') }}</td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Employment Status:</td>
            <td class="form-value" colspan="2">
                Permanent <span class="checkbox {{ $isChecked('employmentStatus', 'Permanent') ? 'checkbox-checked' : '' }}"></span>
                Contract <span class="checkbox {{ $isChecked('employmentStatus', 'Contract') ? 'checkbox-checked' : '' }}"></span>
                Pensioner <span class="checkbox {{ $isChecked('employmentStatus', 'Pensioner') ? 'checkbox-checked' : '' }}"></span>
                Unemployed <span class="checkbox {{ $isChecked('employmentStatus', 'Unemployed') ? 'checkbox-checked' : '' }}"></span>
                Self-Employed <span class="checkbox {{ $isChecked('employmentStatus', 'Self-Employed') ? 'checkbox-checked' : '' }}"></span>
            </td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Business Description:</td>
            <td class="form-value" colspan="2">{{ $get('businessDescription') }}</td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Employer Type:</td>
            <td class="form-value" colspan="2">
                Government <span class="checkbox {{ $isChecked('employerType', 'Government') ? 'checkbox-checked' : '' }}"></span>
                Local Company <span class="checkbox {{ $isChecked('employerType', 'Local Company') ? 'checkbox-checked' : '' }}"></span>
                Multinational <span class="checkbox {{ $isChecked('employerType', 'Multinational') ? 'checkbox-checked' : '' }}"></span>
                NGO <span class="checkbox {{ $isChecked('employerType', 'NGO') ? 'checkbox-checked' : '' }}"></span>
                Other (specify): {{ $get('employerTypeOther') }}
            </td>
        </tr>
        <tr>
            <td class="form-label">Employer Physical Address:</td>
            <td class="form-value" style="width: 45%;">{{ $get('employerAddress') }}</td>
            <td class="form-label">Employer Contact Number:</td>
            <td class="form-value">{{ $get('employerContact') }}</td>
        </tr>
        <tr>
            <td class="form-label">Gross Monthly Salary:</td>
            <td class="form-value">{{ $get('grossMonthlySalary') }}</td>
            <td class="form-label">Other Source(s) of Income:</td>
            <td class="form-value">{{ $get('otherIncome') }}</td>
        </tr>
    </table>

    <!-- Section E - Spouse/Next of Kin -->
    <div class="section-header">E - SPOUSE/ NEXT OF KIN</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 8%;">Title:</td>
            <td class="form-value" style="width: 25%;">
                Mr <span class="checkbox {{ $isChecked('spouseTitle', 'Mr') ? 'checkbox-checked' : '' }}"></span>
                Mrs <span class="checkbox {{ $isChecked('spouseTitle', 'Mrs') ? 'checkbox-checked' : '' }}"></span>
                Ms <span class="checkbox {{ $isChecked('spouseTitle', 'Ms') ? 'checkbox-checked' : '' }}"></span>
                Dr <span class="checkbox {{ $isChecked('spouseTitle', 'Dr') ? 'checkbox-checked' : '' }}"></span>
                Prof <span class="checkbox {{ $isChecked('spouseTitle', 'Prof') ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label" style="width: 12%;">Full Name:</td>
            <td class="form-value" style="width: 55%;">{{ $getSpouse('fullName') }}</td>
        </tr>
        <tr>
            <td class="form-label">Residential Address:</td>
            <td class="form-value" colspan="3">{{ $getSpouse('residentialAddress') }}</td>
        </tr>
        <tr>
            <td class="form-label">National ID No:</td>
            <td class="form-value">{{ $getSpouse('idNumber') }}</td>
            <td class="form-label">Contact Number:</td>
            <td class="form-value">{{ $getSpouse('phoneNumber') }}</td>
        </tr>
        <tr>
            <td class="form-label">Nature of relationship:</td>
            <td class="form-value">{{ $getSpouse('relationship') }}</td>
            <td class="form-label" colspan="2">Gender:
                @php $spouseGender = $getSpouse('gender'); @endphp
                Male <span class="checkbox {{ $spouseGender === 'Male' ? 'checkbox-checked' : '' }}"></span>
                Female <span class="checkbox {{ $spouseGender === 'Female' ? 'checkbox-checked' : '' }}"></span>
            </td>
        </tr>
        <tr>
            <td class="form-label" colspan="2">Email Address:</td>
            <td class="form-value" colspan="2">{{ $getSpouse('email') }}</td>
        </tr>
    </table>

    <!-- Section F - Other Services -->
    <div class="section-header">F - OTHER SERVICES</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 15%;">SMS Alerts:</td>
            <td class="form-value" style="width: 18%;">
                <span class="checkbox {{ $isChecked('smsAlerts', true) ? 'checkbox-checked' : '' }}"></span>
                <span class="checkbox {{ $isChecked('smsAlerts', false) ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label" style="width: 15%;">Mobile Number:</td>
            <td class="form-value" style="width: 22%;">
                @php
                    $mobile = $get('mobile');
                    $digits = str_split(preg_replace('/[^0-9]/', '', $mobile));
                @endphp
                @foreach(range(0, 11) as $i)
                    <span class="num-box">{{ $digits[$i] ?? '' }}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="form-label">E-Statements:</td>
            <td class="form-value">
                <span class="checkbox {{ $isChecked('eStatements', true) ? 'checkbox-checked' : '' }}"></span>
                <span class="checkbox {{ $isChecked('eStatements', false) ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label">Email address:</td>
            <td class="form-value">{{ $get('eStatementsEmail', $get('emailAddress')) }}</td>
        </tr>
    </table>

    <!-- Section G - Digital Banking Services -->
    <div class="section-header">G - DIGITAL BANKING SERVICES</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 25%;">Mobile money e.g. Ecocash Services:</td>
            <td class="form-value" style="width: 10%;">
                Yes <span class="checkbox {{ $isChecked('mobileMoneyEcocash', true) ? 'checkbox-checked' : '' }}"></span>
                No <span class="checkbox {{ !$isChecked('mobileMoneyEcocash', true) ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label" style="width: 15%;">Mobile Number:</td>
            <td class="form-value" style="width: 20%;">
                @php
                    $mobileMoneyNum = $get('mobileMoneyNumber', $get('mobile'));
                    $digits = str_split(preg_replace('/[^0-9]/', '', $mobileMoneyNum));
                @endphp
                @foreach(range(0, 11) as $i)
                    <span class="num-box">{{ $digits[$i] ?? '' }}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="form-label">E-Wallet:</td>
            <td class="form-value">
                <span class="checkbox {{ $isChecked('eWallet', true) ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label">Mobile Number:</td>
            <td class="form-value">
                @php
                    $eWalletNum = $get('eWalletNumber', $get('mobile'));
                    $digits = str_split(preg_replace('/[^0-9]/', '', $eWalletNum));
                @endphp
                @foreach(range(0, 11) as $i)
                    <span class="num-box">{{ $digits[$i] ?? '' }}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="form-label">Whatsapp Banking:</td>
            <td class="form-value" colspan="2">
                <span class="checkbox {{ $isChecked('whatsappBanking', true) ? 'checkbox-checked' : '' }}"></span>
            </td>
            <td class="form-label">Internet Banking:</td>
            <td class="form-value">
                <span class="checkbox {{ $isChecked('internetBanking', true) ? 'checkbox-checked' : '' }}"></span>
            </td>
        </tr>
    </table>

    <div class="page-footer">Page 1 of 4</div>
</div>

<!-- PAGE 2 -->
<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo">
            <span class="for-you">for you</span>
        </div>
        <div class="header-right">
            BANKING | INVESTMENTS | INSURANCE
        </div>
    </div>

    <!-- Section H - ZB Life Funeral Cash Cover -->
    <div class="section-header">H - ZB LIFE FUNERAL CASH COVER</div>

    <div style="font-size: 8pt; margin: 4px 0;">
        Details of dependents to be covered by this application is up to eight (8) dependents. <span style="font-style: italic;">Please tick (√) the appropriate box to show supplementary benefits to be included.</span>
    </div>

    <table class="form-table" style="font-size: 7pt;">
        <tr style="background-color: #f0f0f0;">
            <th style="width: 12%;">Surname</th>
            <th style="width: 15%;">Forename(s)</th>
            <th style="width: 12%;">Relationship</th>
            <th style="width: 12%;">Date of Birth</th>
            <th style="width: 18%;">Birth Entry/ National ID No.</th>
            <th style="width: 13%;">Cover Amount Per Dependant $</th>
            <th style="width: 18%;">Premium Per Month $</th>
        </tr>
        @php
            $funeralCover = $formResponses['funeralCover'] ?? [];
            $dependents = $funeralCover['dependents'] ?? $formResponses['funeralDependents'] ?? [];
            if (!is_array($dependents)) $dependents = [];
            while(count($dependents) < 8) {
                $dependents[] = ['surname' => '', 'forenames' => '', 'relationship' => '', 'dateOfBirth' => '', 'idNumber' => '', 'coverAmount' => '', 'premium' => ''];
            }
        @endphp
        @foreach($dependents as $index => $dependent)
        <tr>
            <td>{{ $dependent['surname'] ?? '' }}</td>
            <td>{{ $dependent['forenames'] ?? '' }}</td>
            <td>{{ $dependent['relationship'] ?? '' }}</td>
            <td>{{ $dependent['dateOfBirth'] ?? '' }}</td>
            <td>{{ $dependent['idNumber'] ?? '' }}</td>
            <td>{{ $dependent['coverAmount'] ?? '' }}</td>
            <td>{{ $dependent['premium'] ?? '' }}</td>
        </tr>
        @endforeach
        <tr style="background-color: #f5f5f5;">
            <td colspan="7" style="text-align: center; font-weight: bold;">Principal Member</td>
        </tr>
        <tr>
            <td colspan="3" rowspan="5" style="vertical-align: top;">
                <strong>Supplementary Benefits (Tick (√) appropriate box)</strong><br/><br/>
                <table style="width: 100%; border: none;">
                    <tr><td style="border: none; padding: 2px;">Memorial Cash Benefit:</td><td style="border: none;">Amount of Cover Per Person</td></tr>
                    <tr><td style="border: none; padding: 2px;">Tombstone Cash Benefit:</td><td style="border: none;">Amount of Cover Per Person</td></tr>
                    <tr><td style="border: none; padding: 2px;">Grocery Benefit:</td><td style="border: none;">Amount of Cover</td></tr>
                    <tr><td style="border: none; padding: 2px;">School Fees Benefit:</td><td style="border: none;">Amount of Cover</td></tr>
                    <tr><td style="border: none; padding: 2px;">Personal Accident Benefit:</td><td style="border: none;">Please supply details below</td></tr>
                </table>
            </td>
            <td colspan="4"></td>
        </tr>
        <tr><td colspan="4"></td></tr>
        <tr><td colspan="4"></td></tr>
        <tr><td colspan="4"></td></tr>
        <tr>
            <td colspan="4" style="text-align: center; font-weight: bold;">Total Monthly Premium</td>
        </tr>
    </table>

    <!-- Section I - Personal Accident Benefit -->
    <div class="section-header">I - PERSONAL ACCIDENT BENEFIT</div>

    <table class="form-table">
        <tr style="background-color: #f0f0f0;">
            <th style="width: 50%;">Surname</th>
            <th style="width: 50%;">Forename(s)</th>
        </tr>
        @for($i = 0; $i < 4; $i++)
        <tr>
            <td style="height: 25px;"></td>
            <td></td>
        </tr>
        @endfor
    </table>

    <!-- Section J - Summary of Terms and Conditions -->
    <div class="section-header">J - SUMMARY OF TERMS AND CONDITIONS OF MEMBERSHIP</div>

    <div class="terms-list">
        <p><strong>1.0</strong> Funeral assurance cover under the Plan shall commence on the first day of the month coinciding with or next following the payment of the first premium.</p>
        <p><strong>1.1</strong> The Plan does not cover death by suicide or by the hand of Justice within a period of twenty-four (24) months from the date of Joining the Plan.</p>
        <p><strong>1.2</strong> Save as herein provided, Membership shall apply if any premium is not paid when due and no right Reinstated nor on account of previous payment shall exist.</p>
        <p><strong>1.3</strong> A grace period of one calendar month is allowed for the payment of each and every premium.</p>
        <p><strong>1.4</strong> Coverage under the Plan shall terminate on the death of the Principal Member or on the voluntary termination by the Principal Member or on the lapse of Membership of the Plan as a result of non-payment of premiums.</p>
        <p><strong>1.5</strong> Extended Family Member means, in respect of a Principal Member, a valid registered Spouse (under 65) and his / her biological children (under 25) and an (8) consecutive months in respect of an Extended Family Member, from the Date of Joining the Plan or date of reinstatement or date of registration of a Dependant.</p>
        <p><strong>1.6</strong> Immediate Family Member means, in respect of the Principal Member, a valid registered Spouse, children (under 21), other children of the Principal Member, and dependants (nature or adoptive parents or parents-in-law of the Principal Member).</p>
        <p><strong>1.7</strong> Extended Family Member means a Dependent who is not an Immediate Family Member.</p>
        <p><strong>1.8</strong> The qualifying period for coverage to be effectively stated in paragraph 1.6 above and ninety (90) days in respect of any increase in the funeral benefit cover of each insured person.</p>
        <p><strong>1.9</strong> Claims shall be settled only if they are reported to ZB Bank Limited within three (3) months from the date of death of an insured person.</p>
        <p><strong>1.10</strong> The maximum cover for each person shall not exceed the limit set from time to time.</p>
    </div>

    <!-- Section K - Declaration -->
    <div class="section-header">K - DECLARATION</div>

    <div style="font-size: 8pt; margin: 4px 0;">
        I confirm that to the best of my knowledge, the above information is true and correct and that all the persons registered above are not on medication for any disease or illness. Should anything change, I undertake to advise ZB Bank immediately.
    </div>

    <table class="form-table" style="font-size: 8pt;">
        <tr>
            <td class="form-label" style="width: 15%; padding: 3px 4px;">Full Name:</td>
            <td class="form-value" style="width: 35%; padding: 3px 4px;">{{ $get('firstName') }} {{ $get('surname') }}</td>
            <td class="form-label" style="width: 20%; padding: 3px 4px;">Applicant's Signature:</td>
            <td class="form-value" style="width: 20%; padding: 3px 4px; vertical-align: middle;">
                @if($signatureImageData)
                    <img src="{{ $signatureImageData }}" style="max-width: 100%; max-height: 30px; display: block;" alt="Signature">
                @endif
            </td>
            <td class="form-label" style="width: 10%; padding: 3px 4px;">Date:</td>
            <td class="form-value" style="padding: 3px 4px;">
                @php
                    $date = date('d/m/Y');
                    $dateBoxes = str_split($date);
                @endphp
                @foreach($dateBoxes as $char)
                    @if($char === '/')
                        /
                    @else
                        <span class="num-box" style="width: 12px; height: 14px; font-size: 7pt;">{{ $char }}</span>
                    @endif
                @endforeach
            </td>
        </tr>
    </table>

    <div class="page-footer">Page 2 of 4</div>
</div>

<!-- PAGE 3 -->
<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo">
            <span class="for-you">for you</span>
        </div>
        <div class="header-right">
            BANKING | INVESTMENTS | INSURANCE
        </div>
    </div>

    <!-- Section L - Declaration by Applicant -->
    <div class="section-header">L - DECLARATION BY APPLICANT</div>

    <div style="font-size: 8pt; margin: 4px 0;">
        I authorise ZB Bank to deduct the premiums stated above each month from my account when funded.
    </div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 20%;">Accountholder's name:</td>
            <td class="form-value" style="width: 30%;">{{ $get('firstName') }} {{ $get('surname') }}</td>
            <td class="form-label" style="width: 15%;">Accountholder's ID:</td>
            <td class="form-value" style="width: 35%;">{{ $get('nationalIdNumber') }}</td>
        </tr>
        <tr>
            <td class="form-label">Account number:</td>
            <td class="form-value"></td>
            <td class="form-label">Accountholder's Signature:</td>
            <td class="form-value" style="height: 40px; vertical-align: middle;">
                @if($signatureImageData)
                    <img src="{{ $signatureImageData }}" style="max-width: 100%; max-height: 35px; display: block;" alt="Signature">
                @endif
            </td>
        </tr>
    </table>

    <!-- Section M - For Completion at Branch Upon Card Collection -->
    <div class="section-header">M - FOR COMPLETION AT BRANCH UPON CARD COLLECTION</div>

    <div style="font-size: 8pt; margin: 4px 0;">
        I confirm safe receipt of my PIN (Personal Identification Number) and acknowledge receipt of my card.
    </div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 20%;">Name of Cardholder:</td>
            <td class="form-value" style="width: 30%;"></td>
            <td class="form-label" style="width: 20%;">Signature of Cardholder:</td>
            <td class="form-value" style="width: 15%;"></td>
            <td class="form-label" style="width: 15%;">Date:</td>
            <td class="form-value"></td>
        </tr>
    </table>

    <!-- Section N - Terms and Conditions -->
    <div class="section-header">N - TERMS AND CONDITIONS</div>

    <div class="terms-list" style="font-size: 6.5pt; column-count: 2; column-gap: 10px;">
        <p style="margin: 1px 0;"><strong>1. DEFINITIONS:</strong> The following terms as stated in these terms and conditions shall have the following meanings:</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.1</strong> Customer: The person in whose name the Services is henceforth referred as "Customer".</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.2</strong> Bank: The Bank means ZB BANK LIMITED.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.3</strong> Services: Means any of the services provided by ZB Bank Limited henceforth referred as "Service".</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.4</strong> Card: The card means the ZB Bank Debit Card.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.5</strong> PIN: Means the Personal Identification Number to be used by the Bank.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.6</strong> Cardholder: The person who is permitted to use the card holder with the Bank.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>1.7</strong> Accountholder: The person in whose name the account is opened and maintained and for whose use a card is issued to use in conjunction with the PIN for any benefit, be it personally or electronically or any third party nominated as per the agreement.</p>

        <p style="margin: 3px 0 1px 0;"><strong>2. APPLICATION OF THE CARD</strong></p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>2.1</strong> All applications for cards are subject to the Bank's processes and approval procedures.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>2.2</strong> The Bank reserves the right to refuse the cardholder access to the card in its sole and absolute discretion.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>2.3</strong> Additional cards may be issued to such third persons as advised by Account Holder.</p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>2.4</strong> You will be charged service fees and other account charges at the rate fixed by ZB Bank from time to time.</p>

        <p style="margin: 3px 0 1px 0;"><strong>3. USE OF THE CARD</strong></p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>3.1</strong> The card may only be used:</p>
        <p style="margin: 1px 0; margin-left: 15px;"><strong>3.1.1</strong> Subject to the terms and provisions of this Agreement that the physical PIN shall not be kept together with the card.</p>
        <p style="margin: 1px 0; margin-left: 15px;"><strong>3.1.2</strong> Subject to the terms and provisions of this Agreement and as amended from time to time. 3.1.3 Within the period of validity of the card shown on the card and after the required activation of the Account.</p>

        <p style="margin: 3px 0 1px 0;"><strong>3.2</strong> The Card</p>
        <p style="margin: 1px 0; margin-left: 10px;">ZB Bank shall not be liable for any loss of funds arising from any unauthorised transaction on Account holder's account after stolen of card.</p>

        <p style="margin: 3px 0 1px 0;"><strong>4. LIABILITY</strong></p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>4.1</strong> When transactions are made by a person other than the holder of the Account and/or the Cardholder, both shall verify and severally be responsible for verifying the PIN when making any use of the Card, the Bank shall reduce the liability of the other.</p>

        <p style="margin: 3px 0 1px 0;"><strong>5. FUNDS IN THE ACCOUNT</strong></p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>5.1</strong> The Bank shall not be obliged to act on or give effect to any payment or disbursement initiated through the use of the card where there are insufficient funds or such payment or disbursement to be made or affects suitable arrangements have been agreed to by the Bank.</p>

        <p style="margin: 3px 0 1px 0;"><strong>6. COMMUNICATION</strong></p>
        <p style="margin: 1px 0; margin-left: 10px;"><strong>6.1</strong> The Bank, its officers and servants, shall not be responsible or accountable to the Cardholder for any loss or damage, actual or consequential, arising directly or indirectly out of or in connection with the issue of the card facilities in being recorded either in or out of date, malfunction, failure or unavailability of the card facilities, the loss or destruction of any data, the failure or interruption or distortion of communication lines, any delay or in acting on any request made or instruction received, incomplete or inaccurate information or signature supplied through the use of the card, any fraudulent or by any person who incorrectly, incompletely or inaccurately supplies information obtained through the use of any transaction or any misuse or theft of any data.</p>
    </div>

    <div style="margin-top: 8px;">
        <table class="form-table">
            <tr>
                <td class="form-label" style="width: 20%;">Customer Signature:</td>
                <td class="form-value" style="width: 30%; height: 50px; vertical-align: middle;">
                    @if($signatureImageData)
                        <img src="{{ $signatureImageData }}" style="max-width: 100%; max-height: 45px; display: block;" alt="Signature">
                    @endif
                </td>
                <td class="form-label" style="width: 10%;">Date:</td>
                <td class="form-value" style="width: 40%;">{{ date('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="page-footer">Page 3 of 4</div>
</div>

<!-- PAGE 4 -->
<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo">
            <span class="for-you">for you</span>
        </div>
        <div class="header-right">
            BANKING | INVESTMENTS | INSURANCE
        </div>
    </div>

    <!-- Two Column Layout for O and P sections -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                <!-- Section O - Indemnity by Applicant -->
                <div class="section-header" style="font-size: 9pt;">O - INDEMNITY BY APPLICANT</div>

                <div class="terms-list" style="font-size: 6pt;">
                    <p style="margin: 1px 0;"><strong>a.</strong> I agree that the Bank reserves the right to close my account compulsorily without warning if it is conducted unsatisfactorily or rules are broken.</p>
                    <p style="margin: 1px 0;"><strong>b.</strong> (a) When used withdraw/redraw/request reveal that I am of questionable credit worthiness.</p>
                    <p style="margin: 1px 0;">(b) Stolen cheques/cards being deposited has been subject to constant requests being honoured in respect of cheques without adequate proof of such report to the Bank within 14 days after being notified of such deposits by the bank.</p>
                    <p style="margin: 1px 0;"><strong>c.</strong> (c) Where it is proven by the Bank that overpayments deposits have erroneously been made into my account or any fraudulent deposits are made into the account and I fail to repay any such amount within the agreed time period as my be stipulated.</p>
                    <p style="margin: 1px 0;"><strong>d.</strong> I also authorize the Bank to retrieve any entries erroneously made into my account or any fraudulent deposits to my account. The amount of such retrieval shall constitute a debt owed by me.</p>
                    <p style="margin: 1px 0;"><strong>e.</strong> Where my account has been debited, I acknowledge that I am unable to pay the Bank the said amount even if payment is made or affects suitable arrangements have not been agreed to by the Bank.</p>
                    <p style="margin: 1px 0;"><strong>f.</strong> In respect of my personal details, contact details; employment details etc;</p>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                <!-- Section P - ZB Ecocash Banking Services Terms -->
                <div class="section-header" style="font-size: 9pt;">P - ZB ECOCASH BANKING SERVICES TERMS AND CONDITIONS</div>

                <div class="terms-list" style="font-size: 6pt;">
                    <p style="margin: 1px 0;"><strong>a.</strong> I hereby certify that all the information provided is correct and authorize ZB Bank to use the information contained on this form for the purposes of opening an account and the Ecocash Banking Platform.</p>
                    <p style="margin: 1px 0;"><strong>b.</strong> I hereby indemnify ZB Bank Limited against any losses, claims damages, whether direct, special or consequential nature, arising from my registration to and use of the Ecocash Banking Services being offline or unavailable for any reason, the mobile phone number provided being registered to my mobile by another person or the Ecocash Banking Services or direct or indirect losses which the Bank could not reasonably have foreseen.</p>
                    <p style="margin: 1px 0;"><strong>c.</strong> I further agree to be bound by the Terms and Conditions governing use by me registered for the event of the termination of the Ecocash Banking Services registration being done by me against any loss caused by termination of the use of services or records.</p>
                    <p style="margin: 1px 0;"><strong>d.</strong> In the event that I wish to terminate the Ecocash Banking Services registration or withdraw Ecocash Banking Services, I understand that the termination can only be done subject to me providing prior written notice to the Bank.</p>
                    <p style="margin: 1px 0;"><strong>e.</strong> The Ecocash PIN Number used to access any other ecocash Services shall be the same used to access Ecocash Banking Services and I agree not to disclose the Personal Identification Number whether in Bank, I authorize Ecocash Banking Services to use the same PIN number to access the Ecocash Banking Services shall remain confidential and secure.</p>
                    <p style="margin: 1px 0;"><strong>f.</strong> I understand and acknowledge that SMS notification charges apply when using Ecocash Banking Services.</p>
                    <p style="margin: 1px 0;"><strong>g.</strong> The above terms and conditions shall be read together with the other terms and conditions as they appear on the Ecocash User Registration Form and Bank Account Opening form and any other Ecocash Services guidelines as may be amended by the Bank or Ecocash from time to time.</p>
                    <p style="margin: 1px 0;"><strong>h.</strong> The bank reserves the right, from time, to review and or amend the terms and conditions applicable to the use of Ecocash Services and shall advise Ecocash registered users of those changes accordingly.</p>
                </div>
            </td>
        </tr>
    </table>

    <table class="form-table" style="margin-top: 8px;">
        <tr>
            <td class="form-label" style="width: 15%;">Print Name:</td>
            <td class="form-value" style="width: 35%;">{{ $get('firstName') }} {{ $get('surname') }}</td>
            <td class="form-label" style="width: 25%;">Authorised Signatory Specimen Signature:</td>
            <td class="form-value" style="width: 25%; height: 50px; vertical-align: middle;">
                @if($signatureImageData)
                    <img src="{{ $signatureImageData }}" style="max-width: 100%; max-height: 45px; display: block;" alt="Signature">
                @endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 5px; text-align: right;">
        <div class="photo-box" style="display: inline-block; float: right; text-align: center; padding: 10px;">
            @if($selfieImageData)
                <img src="{{ $selfieImageData }}" style="max-width: 100px; max-height: 110px; display: block; margin: 0 auto;" alt="Photo of Applicant">
            @else
                Photo of Applicant:
            @endif
        </div>
    </div>
    <div style="clear: both;"></div>

    <!-- For Official Use Only -->
    <div class="section-header" style="margin-top: 10px;">FOR OFFICIAL USE ONLY</div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 30%;">TYPE OF ACCOUNT:</td>
            <td class="form-value" colspan="5">
                Individual Transactional Account <span class="checkbox"></span>
                Senior Citizen Transactional Account <span class="checkbox"></span>
                Informal Trader Transactional Account <span class="checkbox"></span>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="form-value" colspan="5">
                Individual Current Account <span class="checkbox"></span>
                Stash Transactional Account <span class="checkbox"></span>
                Other <span style="font-style: italic;">(Specify)</span> __________
            </td>
        </tr>
    </table>

    <!-- Supporting KYC Checklist -->
    <div class="section-header">SUPPORTING KYC CHECKLIST</div>

    <div style="font-size: 8pt; margin: 4px 0;">
        Please attach certified copies of the following and indicate by marking:
    </div>

    <table class="form-table">
        <tr>
            <td class="form-label" style="width: 45%;">(i) Two (2) recent passport-sized photos</td>
            <td class="form-value" style="width: 18%;"><span class="checkbox"></span></td>
            <td class="form-label" style="width: 18%;">(ii) Proof of residence (within 3-months)</td>
            <td class="form-value" style="width: 19%;"><span class="checkbox"></span></td>
        </tr>
        <tr>
            <td class="form-label">(iii) Payslip <span style="font-style: italic;">(where applicable)</span></td>
            <td class="form-value"><span class="checkbox"></span></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td class="form-label" colspan="4">(iv) Current Identification Documents: (mark applicable): National ID Card <span class="checkbox"></span> Passport <span class="checkbox"></span> Drivers' License <span class="checkbox"></span></td>
        </tr>
    </table>

    <!-- Account Opening & KYC Checker Section -->
    <div class="section-header">ACCOUNT OPENING & KYC CHECKER SECTION</div>

    <table class="form-table" style="font-size: 7pt;">
        <tr>
            <td class="form-label" style="width: 25%;">Service Center Name:</td>
            <td class="form-value" style="width: 25%;"></td>
            <td class="form-label" style="width: 25%;">Domicile Service Center Code:</td>
            <td class="form-value" style="width: 10%;"></td>
            <td class="form-label" style="width: 15%;">ZB ID:</td>
            <td class="form-value"></td>
        </tr>
        <tr>
            <td class="form-label">Financial Clearing Bureau Vetting:</td>
            <td class="form-value">Favourable <span class="checkbox"></span> Unfavourable <span class="checkbox"></span></td>
            <td class="form-label">Politically Exposed Persons Screening:</td>
            <td class="form-value" colspan="3">Favourable <span class="checkbox"></span> Unfavourable <span class="checkbox"></span></td>
        </tr>
        <tr>
            <td class="form-label">Sanctions Screening (UN&OFAC Watch lists):</td>
            <td class="form-value">Favourable <span class="checkbox"></span> Unfavourable <span class="checkbox"></span></td>
            <td class="form-label">RBZ Credit Registry Clearance:</td>
            <td class="form-value" colspan="3">Favourable <span class="checkbox"></span> Unfavourable <span class="checkbox"></span></td>
        </tr>
        <tr>
            <td class="form-label">A/C Opened By:</td>
            <td class="form-value"></td>
            <td class="form-label">Signature:</td>
            <td class="form-value"></td>
            <td class="form-label">ZB Life Certificate No.:</td>
            <td class="form-value"></td>
        </tr>
        <tr>
            <td class="form-label">ZB Life Agent No (if applicable):</td>
            <td class="form-value"></td>
            <td class="form-label">Date:</td>
            <td class="form-value" colspan="3"></td>
        </tr>
    </table>

    <!-- Account Opening Approver & Reviewer Section -->
    <div class="section-header" style="font-size: 8pt;">ACCOUNT OPENING APPROVER & REVIEWER SECTION <span style="font-style: italic; font-size: 7pt;">(Note: Service Centre Manager or Service Centre Consultant in Charge)</span></div>

    <table class="form-table" style="font-size: 7pt;">
        <tr>
            <td class="form-label" style="width: 20%;">Account Opened:</td>
            <td class="form-value" style="width: 15%;"><span class="checkbox"></span> <span class="checkbox"></span></td>
            <td class="form-label" style="width: 30%;">All Mandatory Fields Captured:</td>
            <td class="form-value"><span class="checkbox"></span> <span class="checkbox"></span></td>
        </tr>
        <tr>
            <td class="form-label">KYC Complete:</td>
            <td class="form-value"><span class="checkbox"></span> <span class="checkbox"></span></td>
            <td class="form-label">Customer Risk Review Profile:</td>
            <td class="form-value">High <span class="checkbox"></span> Medium <span class="checkbox"></span> Low <span class="checkbox"></span></td>
        </tr>
        <tr>
            <td colspan="4" style="font-size: 7pt;">
                <strong>KEY:</strong> High Risk Customer - Annual Review &nbsp;&nbsp;&nbsp; Medium Risk Customer - 2-3 Years Review &nbsp;&nbsp;&nbsp; Low Risk Customer - 5 Years Review
            </td>
        </tr>
        <tr>
            <td class="form-label">Next KYC Review Date:</td>
            <td class="form-value" colspan="3"></td>
        </tr>
        <tr>
            <td class="form-label">Approved by:</td>
            <td class="form-value" style="width: 30%;"></td>
            <td class="form-label">Signature:</td>
            <td class="form-value"></td>
        </tr>
        <tr>
            <td class="form-label">Date:</td>
            <td class="form-value" colspan="3"></td>
        </tr>
    </table>

    <div style="margin-top: 15px; text-align: center;">
        <div style="border: 2px solid #000; width: 180px; height: 90px; display: inline-block; padding-top: 30px; color: #ccc; font-size: 16pt;">
            SERVICE CENTER<br/>STAMP
        </div>
    </div>

    <div class="page-footer">Page 4 of 4</div>
</div>



{{-- Include Document Attachments (ID, Payslip, etc.) --}}
@include('forms.partials.pdf_attachments')

</body>
</html>

