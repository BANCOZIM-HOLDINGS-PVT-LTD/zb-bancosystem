@php
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

<!-- PAGE 5: KYC DOCUMENTS -->
<div class="page">
    <div class="section-header">NAME OF APPLICANT: {{ $get('firstName') }} {{ $getAny(['lastName', 'surname']) }}</div>

    <style>
        .documents-container {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 30px;
            margin: 30px 0;
            padding: 0 20px;
        }

        .top-documents {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 20px;
            width: 100%;
        }

        .doc-card {
            border: 2px solid #000;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            position: relative;
        }

        .doc-card-small {
            width: 220px;
            height: 200px;
            flex-shrink: 0;
        }

        .doc-card-medium {
            width: 340px;
            height: 200px;
            flex-grow: 1;
        }

        .doc-card-large {
            width: 100%;
            height: 280px;
        }

        .doc-card img {
            max-width: 100%;
            max-height: 90%;
            object-fit: contain;
            margin-top: 10px;
        }

        .doc-card .doc-label {
            font-weight: bold;
            font-size: 11px;
            position: absolute;
            top: 8px;
            left: 10px;
            text-transform: uppercase;
            color: #000;
        }

        .doc-card .no-doc {
            color: #999;
            font-size: 10px;
            text-align: center;
            margin-top: 20px;
        }
    </style>

    <div class="documents-container">
        <!-- Top Row: Selfie and ID Document -->
        <div class="top-documents">
            <!-- Selfie Card (Small) -->
            <div class="doc-card doc-card-small">
                <div class="doc-label">Selfie</div>
                @php
                    // Check for processed selfie data first (from PDFGeneratorService)
                    $selfie = $selfieImageData['data'] ?? null;
                    // Fallback to raw selfie fields
                    if (empty($selfie)) {
                        $selfie = $formData['documents']['selfie']
                               ?? $formData['selfieImage']
                               ?? $formData['selfie']
                               ?? '';
                    }
                @endphp
                @if(!empty($selfie))
                    @if(str_starts_with($selfie, 'data:image'))
                        {{-- Base64 data URI (already processed) --}}
                        <img src="{{ $selfie }}" alt="Selfie">
                    @elseif(str_starts_with($selfie, 'http'))
                        {{-- External URL --}}
                        <img src="{{ $selfie }}" alt="Selfie">
                    @else
                        {{-- File path - try to load from storage or public --}}
                        @php
                            $selfiePath = null;
                            if (file_exists(storage_path('app/public/' . $selfie))) {
                                $selfiePath = storage_path('app/public/' . $selfie);
                            } elseif (file_exists(public_path($selfie))) {
                                $selfiePath = public_path($selfie);
                            } elseif (file_exists($selfie)) {
                                $selfiePath = $selfie;
                            }
                        @endphp
                        @if($selfiePath && file_exists($selfiePath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($selfiePath)) }}" alt="Selfie">
                        @else
                            <div class="no-doc">No selfie uploaded</div>
                        @endif
                    @endif
                @else
                    <div class="no-doc">No selfie uploaded</div>
                @endif
            </div>
            <!-- ID Document Card (Medium) -->
            <div class="doc-card doc-card-medium">
                <div class="doc-label">ID Document</div>
                @php
                        // Check for processed ID data first (from PDFGeneratorService)
                        $idDocument = $idImageData['data'] ?? null;

                        // Fallback to various possible locations for ID document
                        if (empty($idDocument)) {
                            // Try uploadedDocuments array
                    if (isset($formData['documents']['uploadedDocuments']['national_id'][0]['path'])) {
                        $idDocument = $formData['documents']['uploadedDocuments']['national_id'][0]['path'];
                    } elseif (isset($formData['documents']['uploadedDocuments']['nationalId'][0]['path'])) {
                        $idDocument = $formData['documents']['uploadedDocuments']['nationalId'][0]['path'];
                    } elseif (isset($formData['documents']['uploadedDocuments']['id'][0]['path'])) {
                        $idDocument = $formData['documents']['uploadedDocuments']['id'][0]['path'];
                            }
                            // Try documentReferences array
                            elseif (isset($formData['documents']['documentReferences']['national_id'][0]['path'])) {
                        $idDocument = $formData['documents']['documentReferences']['national_id'][0]['path'];
                            } elseif (isset($formData['documents']['documentReferences']['nationalId'][0]['path'])) {
                                $idDocument = $formData['documents']['documentReferences']['nationalId'][0]['path'];
                            }
                            // Try direct document keys
                            elseif (isset($formData['documents']['nationalId'])) {
                        $idDocument = $formData['documents']['nationalId'];
                    } elseif (isset($formData['documents']['id'])) {
                        $idDocument = $formData['documents']['id'];
                            } elseif (isset($formData['documents']['national_id'])) {
                                $idDocument = $formData['documents']['national_id'];
                            }
                            // Try root level
                            elseif (isset($formData['nationalId'])) {
                                $idDocument = $formData['nationalId'];
                            } elseif (isset($formData['idDocument'])) {
                                $idDocument = $formData['idDocument'];
                            }
                    }
                @endphp
                @if(!empty($idDocument))
                    @if(str_starts_with($idDocument, 'data:image'))
                        <img src="{{ $idDocument }}" alt="ID Document">
                    @elseif(str_starts_with($idDocument, 'http'))
                        <img src="{{ $idDocument }}" alt="ID Document">
                    @else
                        @php
                            $idDocPath = null;
                            // Check if path starts with 'storage/' and try public path
                            if (str_starts_with($idDocument, 'storage/')) {
                                $publicPath = public_path($idDocument);
                                if (file_exists($publicPath)) {
                                    $idDocPath = $publicPath;
                                }
                            }
                            // Try other paths if not found
                            if (!$idDocPath && file_exists(storage_path('app/public/' . $idDocument))) {
                                $idDocPath = storage_path('app/public/' . $idDocument);
                            } elseif (!$idDocPath && file_exists(public_path($idDocument))) {
                                $idDocPath = public_path($idDocument);
                            } elseif (!$idDocPath && file_exists($idDocument)) {
                                $idDocPath = $idDocument;
                            }
                        @endphp
                        @if($idDocPath && file_exists($idDocPath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($idDocPath)) }}" alt="ID Document">
                        @else
                            <div class="no-doc">No ID uploaded</div>
                        @endif
                    @endif
                @else
                    <div class="no-doc">No ID uploaded</div>
                @endif
            </div>
        </div>

        <!-- Bottom Row: Payslip Card (Large) -->
        <!-- Bottom Row: Payslip Card (Large) -->
        <div class="doc-card doc-card-large">
            <div class="doc-label">Payslip</div>
            @php
                // Check for processed payslip data first (from PDFGeneratorService)
                $payslip = $payslipImageData['data'] ?? null;

                // Fallback to various possible locations for payslip
                if (empty($payslip)) {
                    // Try uploadedDocuments array
                if (isset($formData['documents']['uploadedDocuments']['payslip'][0]['path'])) {
                    $payslip = $formData['documents']['uploadedDocuments']['payslip'][0]['path'];
                    } elseif (isset($formData['documents']['uploadedDocuments']['pay_slip'][0]['path'])) {
                        $payslip = $formData['documents']['uploadedDocuments']['pay_slip'][0]['path'];
                    }
                    // Try documentReferences array
                    elseif (isset($formData['documents']['documentReferences']['payslip'][0]['path'])) {
                    $payslip = $formData['documents']['documentReferences']['payslip'][0]['path'];
                    } elseif (isset($formData['documents']['documentReferences']['pay_slip'][0]['path'])) {
                        $payslip = $formData['documents']['documentReferences']['pay_slip'][0]['path'];
                    }
                    // Try direct document keys
                    elseif (isset($formData['documents']['payslip'])) {
                    $payslip = $formData['documents']['payslip'];
                    } elseif (isset($formData['documents']['pay_slip'])) {
                        $payslip = $formData['documents']['pay_slip'];
                    }
                    // Try root level
                    elseif (isset($formData['payslip'])) {
                        $payslip = $formData['payslip'];
                    } elseif (isset($formData['payslipImage'])) {
                        $payslip = $formData['payslipImage'];
                    }
                }
            @endphp
            @if(!empty($payslip))
                @if(str_starts_with($payslip, 'data:image'))
                    <img src="{{ $payslip }}" alt="Payslip">
                @elseif(str_starts_with($payslip, 'http'))
                    <img src="{{ $payslip }}" alt="Payslip">
                @else
                    @php
                        $payslipPath = null;
                        // Check if path starts with 'storage/' and try public path
                        if (str_starts_with($payslip, 'storage/')) {
                            $publicPath = public_path($payslip);
                            if (file_exists($publicPath)) {
                                $payslipPath = $publicPath;
                            }
                        }
                        // Try other paths if not found
                        if (!$payslipPath && file_exists(storage_path('app/public/' . $payslip))) {
                            $payslipPath = storage_path('app/public/' . $payslip);
                        } elseif (!$payslipPath && file_exists(public_path($payslip))) {
                            $payslipPath = public_path($payslip);
                        } elseif (!$payslipPath && file_exists($payslip)) {
                            $payslipPath = $payslip;
                        }
                    @endphp
                    @if($payslipPath && file_exists($payslipPath))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($payslipPath)) }}" alt="Payslip">
                        @else
                        <div class="no-doc">No payslip uploaded</div>
                        @endif
                    @endif
                @else
                <div class="no-doc">No payslip uploaded</div>
                @endif
        </div>
    </div>
    <div class="page-number">Page 5 of 6</div>
</div>

{{-- PAGE 6: INVOICE --}}
<div class="page" style="padding: 20px;">
    <style>
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .invoice-logo {
            height: 60px;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .invoice-subtitle {
            font-size: 12px;
            color: #666;
        }
        .invoice-details {
            margin: 20px 0;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .invoice-table th,
        .invoice-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .invoice-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .invoice-row-highlight {
            background-color: #e8f5e9;
        }
        .invoice-total {
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
        }
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .invoice-date {
            font-size: 12px;
            color: #333;
            margin-bottom: 20px;
        }
    </style>

    <div class="invoice-header">
        @if(file_exists(public_path('assets/images/bancozim_logo.png')))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim_logo.png'))) }}" alt="Bancozim Logo" class="invoice-logo">
        @elseif(file_exists(public_path('assets/images/zb_logo.png')))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="invoice-logo">
        @endif
        <div class="invoice-title">LOAN APPLICATION INVOICE</div>
        <div class="invoice-subtitle">Bancozim Microfinance - A Division of ZB Bank</div>
    </div>

    <div class="invoice-date">
        <strong>Invoice Date:</strong> {{ date('d F Y') }}<br>
        <strong>Reference No:</strong> {{ $formResponses['referenceNumber'] ?? $formData['referenceNumber'] ?? 'BZ-' . date('YmdHis') }}
    </div>

    <div class="invoice-details">
        <table class="invoice-table">
            <tr>
                <th colspan="2" style="background-color: #2e7d32; color: white;">Applicant Details</th>
            </tr>
            <tr>
                <td><strong>Full Name:</strong></td>
                <td>{{ $get('firstName') }} {{ $get('surname') }}</td>
            </tr>
            <tr>
                <td><strong>National ID:</strong></td>
                <td>{{ $get('idNumber') ?: $get('nationalIdNumber') }}</td>
            </tr>
            <tr>
                <td><strong>Employer:</strong></td>
                <td>{{ $get('employerName') }}</td>
            </tr>
        </table>

        <table class="invoice-table">
            <tr>
                <th colspan="2" style="background-color: #2e7d32; color: white;">Loan Details</th>
            </tr>
            <tr>
                <td><strong>Product/Category:</strong></td>
                <td>{{ $formData['category'] ?? $get('category') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Description:</strong></td>
                <td>{{ $formData['business'] ?? $get('productName') ?? 'N/A' }} {{ $formData['scale'] ? '- ' . $formData['scale'] : '' }}</td>
            </tr>
            <tr>
                <td><strong>Net Loan (Selling Price):</strong></td>
                <td>${{ number_format(($formData['netLoan'] ?? $formData['sellingPrice'] ?? $formData['amount'] ?? 0) / 1.06, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Bank Admin Fee (6%):</strong></td>
                <td>${{ number_format(($formData['bankAdminFee'] ?? (($formData['amount'] ?? 0) - (($formData['amount'] ?? 0) / 1.06))), 2) }}</td>
            </tr>
            <tr class="invoice-row-highlight">
                <td><strong>Gross Loan (incl. 6% admin fee):</strong></td>
                <td class="invoice-total">${{ number_format($formData['grossLoan'] ?? $formData['amount'] ?? 0, 2) }}</td>
            </tr>
        </table>

        <table class="invoice-table">
            <tr>
                <th colspan="2" style="background-color: #1565c0; color: white;">Payment Schedule</th>
            </tr>
            <tr>
                <td><strong>Loan Term:</strong></td>
                <td>{{ $formData['creditTerm'] ?? 'N/A' }} months</td>
            </tr>
            <tr>
                <td><strong>Monthly Payment:</strong></td>
                <td class="invoice-total">${{ number_format($formData['monthlyPayment'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td><strong>First Payment Date:</strong></td>
                <td>{{ isset($formData['firstPaymentDate']) ? date('F Y', strtotime($formData['firstPaymentDate'])) : date('F Y', strtotime('first day of next month')) }}</td>
            </tr>
            <tr>
                <td><strong>Last Payment Date:</strong></td>
                <td>{{ isset($formData['lastPaymentDate']) ? date('F Y', strtotime($formData['lastPaymentDate'])) : date('F Y', strtotime('+' . ($formData['creditTerm'] ?? 12) . ' months')) }}</td>
            </tr>
        </table>
    </div>

    <div class="invoice-footer">
        <p><strong>Terms & Conditions:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Interest Rate: 96% per annum</li>
            <li>A 6% bank administration fee is included in the Gross Loan amount</li>
            <li>Payments are due on the 1st of each month</li>
            <li>Late payments may incur additional charges</li>
        </ul>
        <p style="margin-top: 15px;"><em>This invoice is generated automatically by the Bancozim loan management system.</em></p>
        <p><strong>Contact:</strong> info@bancozim.co.zw | +263 24 2785 080</p>
    </div>
    <div class="page-number">Page 6 of 6</div>
</div>

</body>
</html>

