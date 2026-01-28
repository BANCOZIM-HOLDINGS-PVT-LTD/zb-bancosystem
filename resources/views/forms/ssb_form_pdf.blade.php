@php
    // DEFENSIVE: Ensure all required variables are defined to prevent "Undefined variable" errors
    // These variables may be used throughout the template and in closures
    $signatureImageData = $signatureImageData ?? ['data' => '', 'type' => '', 'width' => 0, 'height' => 0, 'aspectRatio' => 1];
    $selfieImageData = $selfieImageData ?? ['data' => '', 'type' => '', 'width' => 0, 'height' => 0, 'aspectRatio' => 1];
    $signatureImage = $signatureImage ?? '';
    $selfieImage = $selfieImage ?? '';
    $documents = $documents ?? [];

    // Handle both direct data and wrapped formData scenarios
    // If formData exists, use it; otherwise, build it from direct variables
    if (!isset($formData)) {
        $formData = [
            'formResponses' => $formResponses ?? [],
            'monthlyPayment' => $monthlyPayment ?? ''
        ];
    }

    // Ensure formData is properly structured
    $formData = $formData ?? [];
    $formResponses = $formData['formResponses'] ?? [];

    // Ensure spouseDetails exists
    if (!isset($formResponses['spouseDetails']) || !is_array($formResponses['spouseDetails'])) {
        $formResponses['spouseDetails'] = [];
    }

    // Helper to safely get string values
    $safeString = function($value, $default = '') {
        if (is_array($value)) {
            return $default;
        }
        return (string)($value ?? $default);
    };

    // Helper to safely get array values
    $safeArray = function($value, $default = []) {
        if (!is_array($value)) {
            return $default;
        }
        return $value;
    };

    // Helper to safely get values
    $get = function($key, $default = '') use ($formResponses) {
        $value = $formResponses[$key] ?? $default;
        return is_array($value) ? $default : (string)$value;
    };

    // Helper to get from multiple keys
    $getAny = function($keys, $default = '') use ($formResponses, $formData) {
        // Handle if $keys is a single value
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            if (is_string($key) && isset($formResponses[$key]) && !empty($formResponses[$key]) && !is_array($formResponses[$key])) {
                return (string)$formResponses[$key];
            }
        }

        // Check in documents array for signature-specific fields
        if (in_array('signature', $keys) || in_array('clientSignature', $keys) || in_array('signatureImage', $keys)) {
            if (isset($formData['documents']['signature']) && !empty($formData['documents']['signature'])) {
                return $formData['documents']['signature'];
            }
            if (isset($formData['signatureImage']) && !empty($formData['signatureImage'])) {
                return $formData['signatureImage'];
            }
        }

        return $default;
    };

    // Helper to format address from JSON or string
    $formatAddress = function($value) {
        if (empty($value)) {
            return '';
        }

        // If it's already a string (not JSON), return it
        if (is_string($value) && !str_starts_with(trim($value), '{')) {
            return $value;
        }

        // Try to decode JSON
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($decoded)) {
            return is_string($value) ? $value : '';
        }

        // Build address from components
        $parts = [];

        if (!empty($decoded['houseNumber'])) {
            $parts[] = $decoded['houseNumber'];
        }
        if (!empty($decoded['streetName'])) {
            $parts[] = $decoded['streetName'];
        }
        if (!empty($decoded['suburb'])) {
            $parts[] = $decoded['suburb'];
        }
        if (!empty($decoded['city'])) {
            $parts[] = $decoded['city'];
        }
        if (!empty($decoded['province'])) {
            $parts[] = $decoded['province'];
        }
        if (!empty($decoded['district'])) {
            $parts[] = $decoded['district'];
        }
        if (!empty($decoded['ward'])) {
            $parts[] = $decoded['ward'];
        }
        if (!empty($decoded['village'])) {
            $parts[] = $decoded['village'];
        }

        return implode(', ', $parts);
    };

    // Helper to get address with formatting
    $getAddress = function($keys, $default = '') use ($formResponses, $formatAddress) {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            if (isset($formResponses[$key]) && !empty($formResponses[$key])) {
                return $formatAddress($formResponses[$key]);
            }
        }
        return $default;
    };

    // Monthly payment
    $monthlyPayment = $formData['monthlyPayment'] ?? '';

    // Spouse details - already initialized above
    $spouseDetails = $formResponses['spouseDetails'];
    while (count($spouseDetails) < 2) {
        $spouseDetails[] = ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => ''];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSB Loan Application Form</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 15px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .page {
            page-break-after: always;
            min-height: 250mm;
            padding: 5mm 0;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        /* Header with logos */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .logo-left {
            flex: 0 0 auto;
            text-align: left;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .logo-right {
            flex: 0 0 auto;
            text-align: right;
            display: flex;
            align-items: flex-start;
        }

        .qupa-logo {
            height: 60px;
            width: auto;
            display: block;
        }

        .bancozim-logo {
            height: 60px;
            width: auto;
            display: block;
        }

        .tagline {
            color: #666;
            font-size: 10px;
            margin-top: 2px;
        }

        .section-header {
            background: #7AC943;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            margin: 12px 0 8px;
            text-transform: uppercase;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        table td, table th {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            font-size: 15px;
            vertical-align: top;
            height: 24px;
        }

        .notice-box {
            border: 2px solid #333;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            font-size: 15px;
            line-height: 1.5;
        }

        .filled-field {
            display: inline-block;
            min-width: 90px;
            padding: 2px 4px;
            font-weight: bold;
            font-size: 15px;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            width: 150px;
            height: 40px;
            display: inline-block;
            vertical-align: bottom;
        }

        .signature-image {
            max-width: 150px;
            max-height: 50px;
            display: inline-block;
            vertical-align: bottom;
            border: 1px solid #ddd;
            padding: 2px;
        }

        .document-image {
            max-width: 200px;
            max-height: 150px;
            display: block;
            margin: 5px auto;
            border: 1px solid #ddd;
            padding: 2px;
        }

        .checkbox {
            width: 15px;
            height: 15px;
            border: 1px solid #333;
            display: inline-block;
            text-align: center;
            margin: 0 3px;
        }

        .code-box {
            width: 18px;
            height: 18px;
            border: 1px solid #333;
            display: inline-block;
            text-align: center;
            margin: 0 1px;
            font-size: 12px;
            line-height: 16px;
        }

        .stamp-box {
            width: 150px;
            height: 100px;
            border: 2px solid #333;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding-top: 35px;
            font-size: 10px;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .page-number {
            position: absolute;
            bottom: 10mm;
            right: 0;
            font-size: 9px;
            color: #666;
        }

        .fingerprint-box {
            width: 80px;
            height: 100px;
            background: #6699cc;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- PAGE 1: SSB LOAN APPLICATION AND CONTRACT FORM -->
<div class="page">
    <div class="header">
        <div class="logo-left">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/qupa.png'))) }}" alt="Qupa Microfinance" class="qupa-logo">
            <div class="tagline">Micro-Finance<br>Registered Microfinance</div>
        </div>
        <div class="logo-right">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim.png'))) }}" alt="BancoZim" class="bancozim-logo">
        </div>
    </div>

    <div class="center" style="margin: 10px 0;">
        <strong>ZB Chamber, 2nd Floor, corner 1st Street & George Silundika</strong><br>
        <strong>Harare, Zimbabwe</strong>
    </div>

    <div class="section-header">SSB LOAN APPLICATION AND CONTRACT FORM</div>

    <div class="notice-box" style="font-size: 12px;">
        Qupa Microfinance Ltd (Hereinafter referred to as "the Lender" which expression, unless repugnant to the context or meaning hereof, shall include it's successor(s), administrator(s) or permitted assignee(s)) is a registered microfinance institution established and existing under the laws of Zimbabwe and having its registered corporate offices at 2nd floor, Chambers House, Cnr First and G.Silundika, Harare.
    </div>

    <div class="center" style="margin: 12px 0; font-weight: bold; font-size: 14px;">AND</div>

    <div class="section-header">1. CUSTOMER PERSONAL DETAILS</div>

    <table>
        <tr>
            <td width="15%">Title:</td>
            <td width="18%"><span class="filled-field">{{ $get('title') }}</span></td>
            <td width="12%">Surname:</td>
            <td width="18%"><span class="filled-field">{{ $get('surname') }}</span></td>
            <td width="12%">First Name:</td>
            <td><span class="filled-field">{{ $get('firstName') }}</span></td>
        </tr>
        <tr>
            <td>Gender:</td>
            <td><span class="filled-field">{{ $get('gender') }}</span></td>
            <td>Date of Birth:</td>
            <td><span class="filled-field">{{ $get('dateOfBirth') }}</span></td>
            <td>Marital Status:</td>
            <td><span class="filled-field">{{ $get('maritalStatus') }}</span></td>
        </tr>
        <tr>
            <td>Nationality:</td>
            <td><span class="filled-field">{{ $get('nationality', 'Zimbabwean') }}</span></td>
            <td>I.D Number:</td>
            <td><span class="filled-field">{{ $getAny(['idNumber', 'nationalIdNumber']) }}</span></td>
            <td>Cell Number:</td>
            <td><span class="filled-field">{{ $getAny(['cellNumber', 'mobile']) }}</span></td>
        </tr>
        <tr>
            <td>WhatsApp:</td>
            <td><span class="filled-field">{{ $getAny(['whatsApp', 'cellNumber']) }}</span></td>
            <td>Email Address:</td>
            <td colspan="3"><span class="filled-field">{{ $get('emailAddress') }}</span></td>
        </tr>
        <tr>
            <td>Ministry:</td>
            <td colspan="5">     <span class="filled-field">{{ $get('responsibleMinistry') }}</span></td>
        </tr>
        <tr>
            <td>Name of Employer:</td>
            <td colspan="2"><span class="filled-field">{{ $get('employerName') }}</span></td>
            <td>Employer Address:</td>
            <td colspan="2"><span class="filled-field">{{ $getAddress('employerAddress') }}</span></td>
        </tr>
        <tr>
            <td>Permanent Address:</td>
            <td colspan="5"><span class="filled-field">{{ $getAddress(['permanentAddress', 'residentialAddress']) }}</span></td>
        </tr>
        <tr>
            <td>Property Ownership:</td>
            <td><span class="filled-field">{{ $get('propertyOwnership') }}</span></td>
            <td>Period at Address:</td>
            <td><span class="filled-field">{{ $get('periodAtAddress') }} years</span></td>
            <td>Employment Status:</td>
            <td><span class="filled-field">{{ $get('employmentStatus') }}</span></td>
        </tr>
        <tr>
            <td>Job Title:</td>
            <td><span class="filled-field">{{ $get('jobTitle') }}</span></td>
            <td>Employment Date:</td>
            <td><span class="filled-field">{{ $get('dateOfEmployment') }}</span></td>
            <td>Employment No:</td>
            <td><span class="filled-field">{{ $getAny(['employmentNumber', 'employeeNumber']) }}</span></td>
        </tr>
        <tr>
            <td>Head of Institution:</td>
            <td><span class="filled-field">{{ $getAny(['headOfInstitution', 'responsiblePaymaster']) }}</span></td>
            <td>Contact:</td>
            <td><span class="filled-field">{{ $get('headOfInstitutionCell') }}</span></td>
            <td>Net Salary (USD):</td>
            <td><span class="filled-field">${{ $getAny(['currentNetSalary', 'netSalary']) }}</span></td>
        </tr>
    </table>

    <div class="section-header">2. SPOUSE AND NEXT OF KIN DETAILS</div>

    <table>
        <tr>
            <th width="35%">Full names</th>
            <th width="20%">Relationship</th>
            <th width="20%">Phone Numbers</th>
            <th>Residential address</th>
        </tr>
        @php
            $spouseDetails = $safeArray($formResponses['spouseDetails']);
            // Ensure we have at least 2 rows for the form
            while (count($spouseDetails) < 2) {
                $spouseDetails[] = ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => ''];
            }
        @endphp
        @foreach(array_slice($spouseDetails, 0, 2) as $index => $spouse)
        <tr>
            <td><span class="filled-field">{{ $safeString($spouse['fullName'] ?? '') }}</span></td>
            <td><span class="filled-field">{{ $safeString($spouse['relationship'] ?? '') }}</span></td>
            <td><span class="filled-field">{{ $safeString($spouse['phoneNumber'] ?? '') }}</span></td>
            <td><span class="filled-field">{{ $formatAddress($spouse['residentialAddress'] ?? '') }}</span></td>
        </tr>
        @endforeach
    </table>

    <div class="section-header">3. BANKING/MOBILE ACCOUNT DETAILS</div>

    <table>
        <tr>
            <th width="34%">BANK</th>
            <th width="33%">BRANCH</th>
            <th>ACCOUNT NUMBER</th>
        </tr>
        <tr>
            <td><span class="filled-field">{{ $get('bankName') }}</span></td>
            <td><span class="filled-field">{{ $getAny(['branch', 'bankBranch']) }}</span></td>
            <td><span class="filled-field">{{ $get('accountNumber') }}</span></td>
        </tr>
    </table>
    <div class="section-header">4. LOANS WITH OTHER INSTITUTIONS (ALSO INCLUDE QUPA LOAN)</div>

    <table class="form-table">
        <tr>
            <td>INSTITUTION</td>
            <td>MONTHLY INSTALMENT</td>
            <td>CURRENT LOAN BALANCE</td>
            <td>MATURITY DATE</td>
        </tr>
        @if(isset($formResponses['hasOtherLoans']) && $formResponses['hasOtherLoans'] === 'No')
            <tr>
                <td colspan="4" style="text-align: center;">N/A - No other loans</td>
            </tr>
        @else
            @php
                $otherLoans = isset($formResponses['otherLoans']) && is_array($formResponses['otherLoans'])
                    ? $formResponses['otherLoans']
                    : [];
                // Ensure at least 2 rows for the form
                while (count($otherLoans) < 2) {
                    $otherLoans[] = ['institution' => '', 'monthlyInstallment' => '', 'currentBalance' => '', 'maturityDate' => ''];
                }
            @endphp
            @foreach(array_slice($otherLoans, 0, 2) as $index => $loan)
            <tr>
                <td>{{ ($index + 1) }}. <span class="filled-field">{{ $loan['institution'] ?? '' }}</span></td>
                <td><span class="filled-field">{{ $loan['monthlyInstallment'] ?? $loan['monthlyInstalment'] ?? '' }}</span></td>
                <td><span class="filled-field">{{ $loan['currentBalance'] ?? '' }}</span></td>
                <td><span class="filled-field">{{ $loan['maturityDate'] ?? '' }}</span></td>
            </tr>
            @endforeach
        @endif
    </table>

    <div class="section-header">5. CREDIT FACILITY APPLICATION DETAILS</div>

    <table>
        <tr>
            <td width="25%">Applied Amount (USD):</td>
            <td width="15%"><span class="filled-field">${{ $getAny(['loanAmount', 'amount', 'finalPrice']) }}</span></td>
            <td width="15%">Tenure:</td>
            <td><span class="filled-field">{{ $getAny(['loanTenure', 'term'], '12') }} months</span></td>
        </tr>
        <tr>
            <td>Purpose/ Asset Applied For:</td>
            <td colspan="3"><span class="filled-field">{{ $getAny(['creditFacilityType', 'business', 'loanPurpose']) }}</span></td>
        </tr>
    </table>

    <div class="page-number">Page 1 of 5</div>
</div>

<!-- PAGE 2: EARLY CONTRACT TERMINATION, DECLARATION, KYC -->
<div class="page">
    <div class="header">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/qupa.png'))) }}" alt="Qupa Microfinance" class="qupa-logo">
        <div style="text-align: right; font-size: 11px;">
            ZB Chamber, 2nd Floor, corner 1st Street & George Silundika<br>
            Harare, Zimbabwe<br>
            Tel: +263 867 700 2005 Email: loans@Qupa.co.zw
        </div>
    </div>

    <div class="section-header">EARLY CONTRACT TERMINATION</div>
    <p style="margin: 8px 0;">
        The Borrower has the option to pay up the credit earlier than the maturity date. The Borrower shall, however, pay Qupa Microfinance early termination administration fees if the cancellation is voluntarily requested by the Borrower.
    </p>

    <div class="section-header">IMPORTANT NOTICE</div>
    <ol style="margin: 8px 0 8px 20px; font-size: 11px;">
        <li>The terms and conditions highlighted in this agreement can be explained in the Borrower's local language upon request of the Borrower.</li>
        <li>Qupa Microfinance reserves the right to decrease or increase the instalment amount or adjust the tenure where credit performance is an issue or statutory changes.</li>
        <li>For all queries or enquiries customers should contact Qupa through the ZB branch network or Bancozim.</li>
    </ol>

    <div class="section-header">CREDIT CESSION AND COLLATERAL</div>
    <ol style="margin: 8px 0 8px 20px; font-size: 11px;">
        <li>The Borrower hereby conclusively and unconditionally cede the credit to Bancozim.</li>
        <li>The Borrower hereby cede the purchased item(s) as collateral for the credit and further authorises Qupa Microfinance through the Merchant to repossess or deny the use of the pledged asset in the event of a default.</li>
    </ol>

    <div class="section-header">DECLARATION</div>
    <div class="notice-box">
        I declare that the information given above is accurate and correct. I am aware that falsifying information automatically leads to decline of my credit application. I authorise Qupa Microfinance to obtain and use the information obtained for the purposes of this application from the recognised credit bureau. I authorise Qupa Microfinance to obtain references from Bancozim if there is need. I undertake to provide all documents required by Qupa Microfinance and to update all records in the
    </div>

    <table style="border: none; margin-top: 15px;">
        <tr style="border: none;">
            <td style="border: none; width: 40%;">
                <strong>Full Name:</strong><br>
                <span class="filled-field" style="width: 200px; border-bottom: 1px solid #333;">{{ $get('firstName') }} {{ $getAny(['lastName', 'surname']) }}</span>
            </td>
            <td style="border: none; width: 30%;">
                <strong>Signature:</strong><br>
                @php
                    // Check for processed signature data first (from PDFGeneratorService)
                    $signature = $signatureImageData['data'] ?? null;
                    // Fallback to raw signature fields
                    if (empty($signature)) {
                        $signature = $getAny(['signature', 'clientSignature', 'signatureImage']);
                    }
                @endphp
                @if(!empty($signature))
                    @if(str_starts_with($signature, 'data:image'))
                        {{-- Base64 data URI (already processed) --}}
                        <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    @elseif(str_starts_with($signature, 'http'))
                        {{-- External URL --}}
                        <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    @else
                        {{-- File path - try to load from storage or public --}}
                        @php
                            $signaturePath = null;
                            // Try storage/app/public path
                            if (file_exists(storage_path('app/public/' . $signature))) {
                                $signaturePath = storage_path('app/public/' . $signature);
                            }
                            // Try public path
                            elseif (file_exists(public_path($signature))) {
                                $signaturePath = public_path($signature);
                            }
                            // Try absolute path
                            elseif (file_exists($signature)) {
                                $signaturePath = $signature;
                            }
                        @endphp
                        @if($signaturePath && file_exists($signaturePath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($signaturePath)) }}" class="signature-image" alt="Signature">
                        @else
                            <div class="signature-line">  </div>
                        @endif
                    @endif
                @else
                    <div class="signature-line">  </div>
                @endif
            </td>
            <td style="border: none;">
                <strong>Date:</strong><br>
                <span class="filled-field" style="border-bottom: 1px solid #333;">{{ date('Y-m-d') }}</span>
            </td>
        </tr>
    </table>

    <div class="section-header">FOR OFFICIAL USE ONLY</div>
    <table>
        <tr>
            <td width="25%">Received & Checked by:</td>
            <td width="30%"><span class="signature-line"></span></td>
            <td width="15%">Date:</td>
            <td width="30%"><span class="signature-line"></span></td>
        </tr>
        <tr>
            <td>Approved by:</td>
            <td><span class="signature-line"></span></td>
            <td>Date:</td>
            <td><span class="signature-line"></span></td>
        </tr>
    </table>

    <div style="margin-top: 15px; text-align: right;">
        <div class="stamp-box">Bank Stamp</div>
    </div>

    <div style="margin-top: 15px;">
        <strong>KYC Checklist:</strong>
        <ol style="margin: 5px 0 0 20px; font-size: 15px;">
            <li>Copy of ID or Valid Passport</li>
            <li>Current Pay Slip/Business records</li>
            <li>Confirmation Letter from employer (serves as proof of residence)</li>
        </ol>
    </div>

    <div class="page-number">Page 2 of 5</div>
</div>

<!-- PAGE 3: DEDUCTION ORDER FORM - TY 30 -->
<div class="page">
    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
        <div style="font-size: 12px;">
            The Manager<br>
            Salary Service Bureau<br>
            P.O Box CY Causeway
        </div>

        <div>
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/qupa.png'))) }}" alt="Qupa Microfinance" class="qupa-logo" style="height: 80px;">
            <div class="tagline">Micro Finance<br>Registered Microfinance</div>
        </div>
    </div>

    <div style="font-size: 16px; font-weight: bold; margin: 15px 0;">
        DEDUCTION ORDER FORM - TY 30<br>
        <span style="font-size: 12px; font-weight: normal;">(Please give effect to the following deduction)</span>
    </div>

    <div style="margin: 10px 0;">
        <span class="checkbox">X</span> New &nbsp;&nbsp;&nbsp;
        <span class="checkbox"></span> Change &nbsp;&nbsp;&nbsp;
        <span class="checkbox"></span> Delete
    </div>

    <div class="section-header">CUSTOMER DETAILS</div>

    <table>
        <tr>
            <td width="15%">First Name:</td>
            <td width="20%"><span class="filled-field">{{ $get('firstName') }}</span></td>
            <td width="15%">Surname:</td>
            <td width="20%"><span class="filled-field">{{ $getAny(['lastName', 'surname']) }}</span></td>
            <td width="15%">ID Number:</td>
            <td><span class="filled-field">{{ $getAny(['idNumber', 'nationalIdNumber']) }}</span></td>
        </tr>
        <tr>
            <td>Ministry:</td>
            <td><span class="filled-field">{{ $get('responsibleMinistry') }}</span></td>
            <td>Province:</td>
            <td><span class="filled-field">{{ $get('province', 'Harare') }}</span></td>
            <td>Employee Code:</td>
            <td>
                @php
                    $empNumString = $getAny(['employmentNumber', 'employeeNumber'], '');
                    $empNum = str_pad($empNumString, 8, ' ', STR_PAD_RIGHT);
                    $empArray = str_split(substr($empNum, 0, 8));
                @endphp
                @foreach($empArray as $digit)
                    <span class="code-box">{{ $digit }}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td>Monthly Amount:</td>
            <td><span class="filled-field">${{ $formData['monthlyPayment'] ?? '' }}</span></td>
            <td>From Date:</td>
            <td><span class="filled-field">{{ date('Y-m-01', strtotime('first day of next month')) }}</span></td>
            <td>To Date:</td>
            <td><span class="filled-field">{{ date('Y-m-t', strtotime('first day of next month +' . ($get('loanTenure', '12') - 1) . ' months')) }}</span></td>
        </tr>
    </table>

    <div class="section-header">DECLARATION</div>
    <div class="notice-box">
        I acknowledge receipt of a contract dated <span class="filled-field">{{ date('Y-m-d') }}</span> and confirm that I have read, understood, and accept the loan under the terms, conditions and warranties as stated therein and authorise Qupa Microfinance and SSB to deduct money from my earnings or terminal benefits in the event of death or termination of employment according to the above instruction.
    </div>

    <table style="border: none; margin-top: 15px;">
        <tr style="border: none;">
            <td style="border: none; width: 40%;">
                <strong>Full Name:</strong><br>
                <span class="filled-field" style="width: 200px; border-bottom: 1px solid #333;">{{ $get('firstName') }} {{ $getAny(['lastName', 'surname']) }}</span>
            </td>
            <td style="border: none; width: 30%;">
                <strong>Signature:</strong><br>
                @php
                    // Check for processed signature data first (from PDFGeneratorService)
                    $signature = $signatureImageData['data'] ?? null;
                    // Fallback to raw signature fields
                    if (empty($signature)) {
                        $signature = $getAny(['signature', 'clientSignature', 'signatureImage']);
                    }
                @endphp
                @if(!empty($signature))
                    @if(str_starts_with($signature, 'data:image'))
                        {{-- Base64 data URI (already processed) --}}
                        <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    @elseif(str_starts_with($signature, 'http'))
                        {{-- External URL --}}
                        <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    @else
                        {{-- File path - try to load from storage or public --}}
                        @php
                            $signaturePath = null;
                            if (file_exists(storage_path('app/public/' . $signature))) {
                                $signaturePath = storage_path('app/public/' . $signature);
                            } elseif (file_exists(public_path($signature))) {
                                $signaturePath = public_path($signature);
                            } elseif (file_exists($signature)) {
                                $signaturePath = $signature;
                            }
                        @endphp
                        @if($signaturePath && file_exists($signaturePath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($signaturePath)) }}" class="signature-image" alt="Signature">
                        @else
                            <div class="signature-line"></div>
                        @endif
                    @endif
                @else
                    <div class="signature-line"></div>
                @endif
            </td>
            <td style="border: none;">
                <strong>Date:</strong><br>
                <span class="filled-field" style="border-bottom: 1px solid #333;">{{ date('Y-m-d') }}</span>
            </td>
        </tr>
    </table>

    <div class="section-header">FOR OFFICIAL USE ONLY</div>
    <table>
        <tr>
            <td width="25%">Authorised Signatory:</td>
            <td width="30%"><span class="signature-line"></span></td>
            <td width="15%">Date:</td>
            <td width="30%"><span class="signature-line"></span></td>
        </tr>
        <tr>
            <td>Authorised Signatory:</td>
            <td><span class="signature-line"></span></td>
            <td>Date:</td>
            <td><span class="signature-line"></span></td>
        </tr>
    </table>

    <div style="margin-top: 15px; text-align: right;">
        <div class="stamp-box">Qupa MFI Stamp</div>
    </div>

    <div class="page-number">Page 3 of 5</div>
</div>

<!-- PAGE 4: BANCOZIM PRODUCT ORDER FORM (P.O.F) -->
<div class="page">
    <div class="center" style="margin-bottom: 20px;">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim.png'))) }}" alt="BancoZim" class="bancozim-logo" style="height: 65px;">
    </div>

    <div class="center" style="font-size: 16px; font-weight: bold; margin: 15px 0;">
        PRODUCT ORDER FORM (P.O.F)
    </div>

    <table>
        <tr>
            <td width="15%">Date:</td>
            <td width="35%"><span class="filled-field">{{ date('Y-m-d') }}</span></td>
            <td width="15%">Client Name:</td>
            <td><span class="filled-field">{{ $get('firstName') }} {{ $get('surname') }}</span></td>
        </tr>
        <tr>
            <td>E.C Number:</td>
            <td><span class="filled-field">{{ $getAny(['employmentNumber', 'employeeNumber']) }}</span></td>
            <td>Delivery Address:</td>
            <td><span class="filled-field">{{ $getAddress(['residentialAddress', 'permanentAddress']) }}</span></td>
        </tr>
    </table>

    <table style="margin-top: 15px;">
        <tr>
            <th width="50%">PRODUCT/ITEM DESCRIPTION</th>
            <th width="15%">PRODUCT CODE</th>
            <th width="15%">QUANTITY</th>
            <th>INSTALMENT</th>
        </tr>
        <tr>
            <td><span class="filled-field">{{ $getAny(['creditFacilityType', 'business']) }}</span></td>
            <td>........................</td>
            <td class="center">1</td>
            <td><span class="filled-field">${{ $formData['monthlyPayment'] ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="right" colspan="3"><strong>TOTAL:</strong></td>
            <td><span class="filled-field">${{ $formData['monthlyPayment'] ?? '' }}</span></td>
        </tr>
    </table>

    <div style="font-weight: bold; margin: 15px 0;">DECLARATION BY ORDERING CLIENT:-</div>
    <div class="notice-box">
        I the undersigned <span class="filled-field">{{ $get('firstName') }} {{ $get('surname') }}</span> (also known as the client) do hereby confirm that today I have ordered the stated product/item from BancoZim (also known as the product supplier) as per their product catalogue and/or price list as selected by me.
     And acknowledge that this credit scheme is being funded by BancoZim's principle herein after referred to as the "financier", who will be the custodians of the consequential loan and the client will the fully indebted to the financier for all future installments due.
        The client authorises the financier to compensate the product supplier to the value of the loan amount. The client hereby acknowledges that once the form application process has been executed on the platform and the application is successfully processed,
        then the process is irreversible and irrevocable. The client agrees not to move his salary account to another bank for the duration of the loan.
    </div>

    <div style="font-size: 13px; font-weight: bold; margin: 15px 0; text-align: center;">
        I declare that if I initiate cancellation of this application for whatever reason then I authorize and agree to be charged a penalty equivalent to one month instalment. Which amount deemed as an administration cost shall be deducted directly from my salary without prejudice.
    </div>

    <table style="border: none; margin-top: 30px;">
        <tr style="border: none;">
            <td style="border: none; width: 50%; text-align: center;">
                @php
                    // Check for processed signature data first (from PDFGeneratorService)
                    $signature = $signatureImageData['data'] ?? null;
                    // Fallback to raw signature fields
                    if (empty($signature)) {
                        $signature = $getAny(['signature', 'clientSignature', 'signatureImage']);
                    }
                @endphp
                @if(!empty($signature))
                    @if(str_starts_with($signature, 'data:image'))
                        {{-- Base64 data URI (already processed) --}}
                        <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    @elseif(str_starts_with($signature, 'http'))
                        {{-- External URL --}}
                        <img src="{{ $signature }}" class="signature-image" alt="Signature">
                    @else
                        {{-- File path - try to load from storage or public --}}
                        @php
                            $signaturePath = null;
                            if (file_exists(storage_path('app/public/' . $signature))) {
                                $signaturePath = storage_path('app/public/' . $signature);
                            } elseif (file_exists(public_path($signature))) {
                                $signaturePath = public_path($signature);
                            } elseif (file_exists($signature)) {
                                $signaturePath = $signature;
                            }
                        @endphp
                        @if($signaturePath && file_exists($signaturePath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($signaturePath)) }}" class="signature-image" alt="Signature">
                        @else
                            <div class="signature-line"></div>
                        @endif
                    @endif
                @else
                    <div class="signature-line"></div>
                @endif
                <br>
                <strong>Signature</strong>
            </td>
            <td style="border: none; width: 50%; text-align: center;">
                <div class="signature-line"></div><br>
                <strong>I.D Number</strong>
            </td>
        </tr>
    </table>
    <div class="page-number">Page 4 of 5</div>
</div>



{{-- PAGE 6: INVOICE --}}
<div class="page" style="padding: 30px;">
    <style>
        .invoice-container {
            font-family: 'Times New Roman', serif;
            font-size: 14px;
        }
        .invoice-logo-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .invoice-logo-header img {
            height: 60px;
        }
        .invoice-logo-text {
            color: #e63946;
            font-size: 32px;
            font-weight: bold;
            font-style: italic;
            font-family: 'Times New Roman', serif;
        }
        .invoice-title-main {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            margin: 25px 0 35px 0;
        }
        .invoice-info-table {
            width: 75%;
            margin: 0 auto 35px auto;
            border-collapse: collapse;
        }
        .invoice-info-table td {
            padding: 8px 12px;
            border: 1px solid #000;
            font-size: 14px;
        }
        .invoice-info-table td:first-child {
            width: 130px;
            font-weight: bold;
        }
        .invoice-product-table {
            width: 70%;
            margin: 35px auto;
            border-collapse: collapse;
        }
        .invoice-product-table th,
        .invoice-product-table td {
            padding: 12px;
            border: 1px solid #000;
            text-align: center;
            font-size: 14px;
        }
        .invoice-product-table th {
            font-weight: bold;
            background-color: #f5f5f5;
            font-size: 13px;
        }
        .invoice-product-table td {
            height: 45px;
        }
        .invoice-total-row td {
            font-weight: bold;
            font-size: 15px;
        }
        .invoice-payment-section {
            margin-top: 45px;
            font-size: 14px;
        }
        .invoice-payment-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .invoice-payment-details {
            margin-left: 25px;
        }
        .invoice-payment-details p {
            margin: 6px 0;
        }
    </style>

    <div class="invoice-container">
        {{-- Logo Header --}}
        <div class="invoice-logo-header">
            @if(file_exists(public_path('assets/images/bancozim.png')))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim.png'))) }}" alt="Bancozim Logo">
            @else
                <div class="invoice-logo-text">BancoZim</div>
            @endif
        </div>

        {{-- Invoice Title --}}
        <div class="invoice-title-main">INVOICE</div>

        {{-- Invoice Info Table --}}
        <table class="invoice-info-table">
            <tr>
                <td>DATE</td>
                <td>{{ date('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>TO</td>
                <td>QUPA MICROFINANCE</td>
            </tr>
            <tr>
                <td>On Behalf Of</td>
                <td>{{ $get('firstName') }} {{ $getAny(['lastName', 'surname']) }}</td>
            </tr>
            <tr>
                <td>INVOICE NO</td>
                <td>{{ $getAny(['idNumber', 'nationalIdNumber']) }}</td>
            </tr>
        </table>

        {{-- Product Table --}}
        <table class="invoice-product-table">
            <thead>
                <tr>
                    <th style="width: 50%;">PRODUCT DESCRIPTION</th>
                    <th style="width: 25%;">PRODUCT CODE</th>
                    <th style="width: 25%;">PRICE (USD)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $getAny(['creditFacilityType', 'business', 'productName', 'loanPurpose', 'category']) }}</td>
                    <td>{{ $getAny(['productCode']) }}</td>
                    <td>${{ number_format((float)str_replace(',', '', $getAny(['loanAmount', 'amount', 'finalPrice', 'netLoan', 'sellingPrice'], '0')), 2) }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="invoice-total-row">
                    <td colspan="2" style="text-align: right;">TOTAL DUE:</td>
                    <td>${{ number_format((float)str_replace(',', '', $getAny(['loanAmount', 'amount', 'finalPrice', 'netLoan', 'sellingPrice'], '0')), 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Method of Payment Section --}}
        <div class="invoice-payment-section">
            <div class="invoice-payment-title">METHOD OF PAYMENT :</div>
            <div class="invoice-payment-details">
                <p>Please remit amount to the following bank account</p>
                <p><strong>Account Name</strong> : BancoZim Holdings (Pvt) Ltd</p>
                <p><strong>Bank & Branch</strong> : Z.B-Westend</p>
                <p><strong>Account No</strong> : 4151092423405</p>
            </div>
        </div>
    </div>

    <div class="page-number">End of Application Form</div>
</div>

{{-- Include Document Attachments (ID, Payslip, etc.) --}}
@include('forms.partials.pdf_attachments')

</body>
</html>

