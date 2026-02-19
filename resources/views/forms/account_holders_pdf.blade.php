@php
    // DEFENSIVE: Ensure all required variables are defined to prevent "Undefined variable" errors
    // These variables may be used throughout the template
    $signatureImage = $signatureImage ?? '';
    $selfieImage = $selfieImage ?? '';
    $documents = $documents ?? [];
    $formResponses = $formResponses ?? [];
    $firstName = $firstName ?? '';
    $surname = $surname ?? '';
    $firstName = $firstName ?? '';
    $surname = $surname ?? '';
    $fcbData = $fcbData ?? null;
    $guarantor = $guarantor ?? ($formResponses['guarantor'] ?? []);
    $otherLoans = $otherLoans ?? ($formResponses['otherLoans'] ?? []);

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

        // NEW: Handle simplified Urban/Rural format
        if (isset($decoded['type'])) {
            $parts = [];
            if ($decoded['type'] === 'urban') {
                if (!empty($decoded['addressLine'])) {
                    $parts[] = $decoded['addressLine'];
                }
                if (!empty($decoded['city'])) {
                    $parts[] = $decoded['city'];
                }
            } elseif ($decoded['type'] === 'rural') {
                if (!empty($decoded['addressLine'])) {
                    $parts[] = $decoded['addressLine'];
                }
                if (!empty($decoded['wardDistrict'])) {
                    $parts[] = $decoded['wardDistrict'];
                }
            }
            return implode(', ', $parts);
        }

        // LEGACY: Build address from old format components
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
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Holders Application Form</title>
    <style>
        @page {
            margin: 5mm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.1;
            margin: 0;
            padding: 0;
            color: #000;
        }
        
        .page {
            page-break-after: always;
            position: relative;
            padding: 5mm;
        }
        
        .page:last-child {
            page-break-after: avoid;
        }
        
        /* Header styles */
        .header-table {
            width: 100%;
            margin-bottom: 5px;
            border-collapse: collapse;
        }
        
        .logo-qupa {
            width: 150px;
            text-align: left;
        }
        
        .logo-bancozim {
            text-align: right;
        }
        
        .logo-qupa img {
            max-width: 120px;
            height: auto;
        }

        .logo-bancozim img {
            max-width: 120px;
            height: auto;
        }
        
        .micro-finance {
            font-size: 8pt;
            color: #666;
            margin-top: 2px;
        }
        
        .registered {
            font-size: 6pt;
            color: #8BC34A;
            font-style: italic;
            margin-top: 1px;
        }
        
        /* Main table styles */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
            font-size: 8pt;
        }
        
        .main-table th {
            border: 1px solid #000;
            padding: 2px 4px;
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left;
            font-size: 8pt;
        }
        
        /* Section headers */
        .section-header {
            background-color: #8BC34A;
            color: white;
            font-weight: bold;
            padding: 3px 8px;
            font-size: 9pt;
            margin: 8px 0 3px 0;
            text-transform: uppercase;
        }
        
        /* No border cells */
        .no-border {
            border: none !important;
        }
        
        .no-border-top {
            border-top: none !important;
        }
        
        .no-border-bottom {
            border-bottom: none !important;
        }
        
        .no-border-left {
            border-left: none !important;
        }
        
        .no-border-right {
            border-right: none !important;
        }
        
        /* Field styles */
        .field-label {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 150px;
            font-size: 8pt;
        }
        
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin: 0 4px;
            vertical-align: middle;
        }
        
        .checkbox.checked {
            background-color: #000;
        }
        
        .dotted-line {
            border-bottom: 1px dotted #000;
            min-height: 20px;
        }
        
        /* Address and header text */
        .address-text {
            font-size: 8pt;
            text-align: center;
            margin-bottom: 10px;
        }
        
        /* Legal text box */
        .legal-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 10px 0;
            font-size: 8pt;
            text-align: justify;
        }
        
        /* Signature fields */
        .signature-table {
            width: 100%;
            margin-top: 20px;
        }
        
        .signature-field {
            border-bottom: 1px solid #000;
            min-height: 30px;
        }
        
        /* KYC section */
        .kyc-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 20px;
        }
        
        .stamp-box {
            width: 150px;
            height: 100px;
            border: 2px solid #000;
            text-align: center;
            padding-top: 40px;
            float: right;
        }

        /* Document cards */
        .doc-card {
            border: 2px solid #000;
            background-color: #fff;
            padding: 10px;
            position: relative;
            text-align: center;
            vertical-align: middle;
        }

        .doc-card img {
            max-width: 100%;
            max-height: 150px;
            display: block;
            margin: 20px auto 5px auto;
        }

        .doc-card .doc-label {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 10px;
            display: block;
        }

        .doc-card .no-doc {
            color: #999;
            font-size: 8pt;
            text-align: center;
            padding: 40px 10px;
        }
    </style>
</head>
<body>

<!-- PAGE 1 -->
<div class="page">
    <!-- Header with logos -->
    <table class="header-table">
        <tr>
            <td class="logo-qupa">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/qupa.png'))) }}" alt="Qupa Logo">
                <div class="micro-finance">Micro-Finance</div>
                <div class="registered">Registered Microfinance</div>
            </td>
            <td style="text-align: center; vertical-align: middle;">
            </td>
            <td class="logo-bancozim">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim.png'))) }}" alt="BancoZim Logo">
            </td>
        </tr>
    </table>
    
    <div class="address-text" style="margin: 5px 0;">
        ZB Chamber, 2nd Floor, corner 1st Street & George Silundika Harare
    </div>
    
    <div class="section-header">LOAN APPLICATION AND CONTRACT FORM for Z B BANK ACCOUNT HOLDERS</div>
    
    <div class="legal-box" style="margin: 5px 0; padding: 5px; font-size: 7pt;">
        Qupa Microfinance Ltd (Hereinafter referred to as "the Lender" which expression, unless repugnant to the context or meaning hereof, shall include it's successor(s), administrator(s) or permitted assignee(s)) is a registered microfinance institution established and existing under the laws of Zimbabwe and having its registered corporate offices at 2nd floor, Chambers House, Cnr First and G Silundika, Harare.
        <br/><br/>
        <strong>AND</strong>
    </div>
    
    <div class="section-header">1.CUSTOMER PERSONAL DETAILS</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 15%;">Title: {{ $title ?? 'Mr/ Mrs/ Miss' }}</td>
            <td style="width: 20%;">Surname: {{ $surname ?? '' }}</td>
            <td style="width: 20%;">First Name: {{ $firstName ?? '' }}</td>
            <td style="width: 15%;">Gender: {{ $gender ?? 'Male/ Female' }}</td>
            <td style="width: 15%;">Date of Birth: {{ $dateOfBirth ?? '' }}</td>
            <td style="width: 15%;"></td>
        </tr>
        <tr>
            <td colspan="2">Marital Status: {{ $maritalStatus ?? 'Single/ Married /Divorced/ Widowed' }}</td>
            <td>Nationality: {{ $nationality ?? 'Zimbabwean' }}</td>
            <td colspan="3">I.D Number: {{ $nationalIdNumber ?? '' }}</td>
        </tr>
        <tr>
            <td>Cell Number :</td>
            <td>{{ $mobile ?? '' }}</td>
            <td>WhatsApp: {{ $whatsApp ?? '' }}</td>
            <td colspan="3">Email Address: {{ $emailAddress ?? '' }}</td>
        </tr>
        <tr>
            <td>Name of Responsible Paymaster :</td>
            <td>Church <span class="checkbox {{ $responsiblePaymaster === 'Church' ? 'checked' : '' }}"></span></td>
            <td colspan="4">Other: {{ $responsiblePaymaster !== 'Church' ? $responsiblePaymaster : '…………………………………………………………………………………….' }}</td>
        </tr>
        <tr>
            <td>Name of Institution :</td>
            <td>{{ $employerName ?? '' }}</td>
            <td colspan="4">Employer Address: {{ $formatAddress($employerAddress ?? '') }}</td>
        </tr>
        <tr>
            <td colspan="6">Permanent Address: {{ $formatAddress($residentialAddress ?? '') }}</td>
        </tr>
        <tr>
            <td>Property Ownership:</td>
            <td>Owned <span class="checkbox {{ $propertyOwnership === 'Owned' ? 'checked' : '' }}"></span></td>
            <td>Employer Owned <span class="checkbox {{ $propertyOwnership === 'Employer Owned' ? 'checked' : '' }}"></span></td>
            <td>Rented <span class="checkbox {{ $propertyOwnership === 'Rented' ? 'checked' : '' }}"></span></td>
            <td>Mortgaged <span class="checkbox {{ $propertyOwnership === 'Mortgaged' ? 'checked' : '' }}"></span></td>
            <td>Parents Owned <span class="checkbox {{ $propertyOwnership === 'Parents Owned' ? 'checked' : '' }}"></span></td>
        </tr>
        <tr>
            <td>Period at current address:</td>
            <td>Less than One Year <span class="checkbox {{ $periodAtAddress === 'Less than One Year' ? 'checked' : '' }}"></span></td>
            <td>Between 1-2 years <span class="checkbox {{ $periodAtAddress === 'Between 1-2 years' ? 'checked' : '' }}"></span></td>
            <td>Between 2-5 years <span class="checkbox {{ $periodAtAddress === 'Between 2-5 years' ? 'checked' : '' }}"></span></td>
            <td colspan="2">More than 5 years <span class="checkbox {{ $periodAtAddress === 'More than 5 years' ? 'checked' : '' }}"></span></td>
        </tr>
        <tr>
            <td>Status of employment:</td>
            <td>Permanent <span class="checkbox {{ $employmentStatus === 'Permanent' ? 'checked' : '' }}"></span></td>
            <td>Contract <span class="checkbox {{ $employmentStatus === 'Contract' ? 'checked' : '' }}"></span></td>
            <td colspan="3">Part time <span class="checkbox {{ $employmentStatus === 'Part time' ? 'checked' : '' }}"></span></td>
        </tr>
        <tr>
            <td>Job Title: {{ $jobTitle ?? '' }}</td>
            <td>Date of Employment: {{ $dateOfEmployment ?? '' }}</td>
            <td colspan="2">Name of Head of Institution: {{ $headOfInstitution ?? 'Mr /Mrs /Miss' }}</td>
            <td colspan="2">Cell No of Head of Institution: {{ $headOfInstitutionCell ?? '' }}</td>
        </tr>
        <tr>
            <td>Employment number:</td>
            <td colspan="2">{{ $employmentNumber ?? '' }}</td>
            <td colspan="2">Current Net Salary (USD):</td>
            <td>${{ $currentNetSalary ?? '' }}</td>
        </tr>
    </table>
    
    <div class="section-header">2.SPOUSE AND NEXT OF KIN DETAILS</div>
    
    <table class="main-table">
        <tr>
            <th style="width: 5%;">&nbsp;</th>
            <th style="width: 30%;">Full Names</th>
            <th style="width: 20%;">Relationship</th>
            <th style="width: 20%;">Phone Numbers</th>
            <th style="width: 25%;">Residential address</th>
        </tr>
        @if(isset($spouseDetails) && is_array($spouseDetails))
            @foreach($spouseDetails as $index => $kin)
                <tr>
                    <td>{{ $index + 1 }}.)</td>
                    <td>{{ $kin['fullName'] ?? '' }}</td>
                    <td>{{ $kin['relationship'] ?? '' }}</td>
                    <td>{{ $kin['phoneNumber'] ?? '' }}</td>
                    <td>{{ $formatAddress($kin['residentialAddress'] ?? '') }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>1.)</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>2.)</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>3.)</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
    </table>
    
    <div class="section-header">2(a). GUARANTOR DETAILS</div>
    
    <table class="main-table">
        <tr>
            <th style="width: 35%;">Full Name</th>
            <th style="width: 35%;">Phone Number</th>
            <th style="width: 30%;">I.D Number</th>
        </tr>
        <tr>
            <td style="height: 25px;">{{ $guarantor['name'] ?? '' }}</td>
            <td>{{ $guarantor['phoneNumber'] ?? '' }}</td>
            <td>{{ $guarantor['idNumber'] ?? '' }}</td>
        </tr>
    </table>
    
    <div class="section-header">3.BANKING/MOBILE ACCOUNT DETAILS</div>
    
    <table class="main-table">
        <tr>
            <th style="width: 33%;">BANK</th>
            <th style="width: 33%;">BRANCH</th>
            <th style="width: 34%;">ACCOUNT NUMBER</th>
        </tr>
        <tr>
            <td style="height: 25px;">{{ $bankName ?? '' }}</td>
            <td>{{ $branch ?? '' }}</td>
            <td>{{ $accountNumber ?? '' }}</td>
        </tr>
    </table>
    
    <div class="section-header">4.LOANS WITH OTHER INSTITUTIONS (ALSO INCLUDE QUPA LOAN)</div>
    
    <table class="main-table">
        <tr>
            <th style="width: 25%;">INSTITUTION</th>
            <th style="width: 25%;">REPAYMENT</th>
            <th style="width: 25%;">CURRENT LOAN BALANCE</th>
            <th style="width: 25%;">MATURITY DATE</th>
        </tr>
        @if(isset($otherLoans) && is_array($otherLoans))
            @foreach($otherLoans as $loan)
                <tr>
                    <td style="height: 25px;">{{ $loan['institution'] ?? '' }}</td>
                    <td>{{ $loan['repayment'] ?? '' }}</td>
                    <td>{{ $loan['currentBalance'] ?? '' }}</td>
                    <td>{{ $loan['maturityDate'] ?? '' }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td style="height: 25px;"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="height: 25px;"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="height: 25px;"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
    </table>
    
    <div class="section-header">5.CREDIT FACILITY APPLICATION DETAILS</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 20%;">Monthly Installment: ${{ $monthlyPayment ?? '' }}</td>
            <td style="width: 13%; text-align: center;">3 months<br/>
                <span class="checkbox {{ $loanTenure == 3 ? 'checked' : '' }}"></span>
            </td>
            <td style="width: 13%; text-align: center;">6 months<br/>
                <span class="checkbox {{ $loanTenure == 6 ? 'checked' : '' }}"></span>
            </td>
            <td style="width: 13%; text-align: center;">9 months<br/>
                <span class="checkbox {{ $loanTenure == 9 ? 'checked' : '' }}"></span>
            </td>
            <td style="width: 13%; text-align: center;">12 months<br/>
                <span class="checkbox {{ $loanTenure == 12 ? 'checked' : '' }}"></span>
            </td>
            <td style="width: 13%; text-align: center;">Other months:<br/>
                <span class="checkbox {{ !in_array($loanTenure, [3, 6, 9, 12]) ? 'checked' : '' }}"></span>
            </td>
            <td style="width: 15%;">{{ !in_array($loanTenure, [3, 6, 9, 12]) ? $loanTenure : '' }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top;">Purpose: Applied Asset</td>
            <td colspan="6" style="border-bottom: 1px dotted #000; height: 60px; vertical-align: bottom;">
                {{ $purposeAsset ?? $purposeOfLoan ?? '' }}
                <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 15px;"></div>
                <div style="border-bottom: 1px dotted #000; height: 15px;"></div>
            </td>
        </tr>
    </table>
</div>

<!-- PAGE 2 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="logo-qupa">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/qupa.png'))) }}" alt="Qupa Logo">
                <div class="micro-finance">Micro-Finance</div>
                <div class="registered">Registered Microfinance</div>
            </td>
            <td style="text-align: right; font-size: 8pt;">
                ZB Chamber, 2nd Floor, corner 1st Street & George Silundika<br/>
                Harare, Zimbabwe<br/>
                Tel: +263 867 700 2005 Email: loans@qupa.co.zw
            </td>
        </tr>
    </table>
    
    <div class="section-header">EARLY CONTRACT TERMINATION</div>
    
    <div style="padding: 10px; font-size: 9pt;">
        The Borrower has the Option to pay up the credit earlier than the maturity date. The Borrower shall, however, pay Qupa Microfinance early termination administration fees if the cancellationis voluntarily requested by the Borrower.
    </div>
    
    <div class="section-header">IMPORTANT NOTICE</div>
    
    <div style="padding: 10px; font-size: 9pt;">
        <ol>
            <li>The terms and conditions highlighted in this agreement can be explained in the Borrower's local language upon request of the Borrower.</li>
            <li style="margin-top: 10px;">Qupa Microfinance reserves the right to decrease or increase the instalment amount or adjust the tenure where credit performance is an issue or statutory changes.</li>
            <li style="margin-top: 10px;">For all queries or enquiries customers should contact Qupa through the ZB branch network or Bancozim.</li>
        </ol>
    </div>
    
    <div class="section-header">CREDIT CESSION AND COLLATERAL</div>
    
    <div style="padding: 10px; font-size: 9pt;">
        <ol>
            <li>The Borrower hereby conclusively and unconditionally cede the credit to Bancozim.</li>
            <li>The Borrower hereby cede the purchased item(s) as collateral for the credit and further authorises Qupa Microfinance through the Merchant to repossess or deny the use of the pledged asset in the event of a default.</li>
        </ol>
    </div>
    
    <div class="section-header">DECLARATION</div>
    
    <div class="legal-box">
        I declare that the information given above is accurate and correct. I am aware that falsifying information automatically leads to decline of my credit application. I authorise Qupa Microfinance to obtain and use the information obtained for the purposes of this application from the recognised credit bureau. I authorise Qupa Microfinance to obtain references from Bancozim if there is need. I undertake to provide all documents required by Qupa Microfinance and to update all records in the event of change of any personal details. I hereby consent to be covered by Qupa microfinance insurance policy which Qupa takes out with the insurance company of its choosing and further cede my rights to Qupa microfinance in the event of a claim.
    </div>
    
    <table class="signature-table">
        <tr>
            <td style="width: 15%;">Full Name:</td>
            <td style="width: 35%; border-bottom: 1px solid #000;">{{ $firstName ?? '' }} {{ $surname ?? '' }}</td>
            <td style="width: 15%; text-align: right; padding-right: 10px;">Signature:</td>
            <td style="width: 20%; border-bottom: 1px solid #000;">
                @if(isset($signatureImage) && !empty($signatureImage))
                    <img src="{{ $signatureImage }}" style="max-width: 100px; max-height: 30px;" alt="Digital Signature">
                @endif
            </td>
            <td style="width: 5%; text-align: right;">Date:</td>
            <td style="width: 10%; border-bottom: 1px solid #000;">{{ $applicationDate ?? '' }}</td>
        </tr>
    </table>

    <div class="section-header">FOR OFFICIAL USE ONLY</div>
    
    <table class="main-table" style="margin-top: 10px;">
        <tr>
            <td class="field-label" style="width: 20%;">Received & Checked by:</td>
            <td style="width: 20%;">Name: {{ $metadata['zb_checker']['name'] ?? '' }}</td>
            <td style="width: 20%;">{{ $metadata['zb_checker']['designation'] ?? '' }}</td>
            <td style="width: 15%;">Signature:</td>
            <td style="width: 15%;">
                @if(isset($metadata['zb_checker']))
                    <span style="font-family: monospace; font-size: 8pt;">[DIGITALLY SIGNED]</span>
                @endif
            </td>
            <td style="width: 10%;">Date: {{ isset($metadata['zb_checker']['date']) ? date('Y-m-d', strtotime($metadata['zb_checker']['date'])) : '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Approved by:</td>
            <td>Name: {{ $metadata['zb_approver']['name'] ?? '' }}</td>
            <td></td>
            <td>Signature:</td>
            <td>
                @if(isset($metadata['zb_approver']))
                     <span style="font-family: monospace; font-size: 8pt;">[DIGITALLY APPROVED]</span>
                @endif
            </td>
            <td>Date: {{ isset($metadata['zb_approver']['date']) ? date('Y-m-d', strtotime($metadata['zb_approver']['date'])) : '' }}</td>
        </tr>
    </table>
    
    <div class="kyc-box">
        <h4 style="margin: 0 0 10px 0;">KYC CHECKLIST</h4>
        <ol style="margin: 5px 0;">
            <li>Copy of ID or Valid Passport</li>
            <li>Current Pay Slip</li>
            <li>Confirmation and Commitment Letter from employer</li>
        </ol>
        
        <div class="stamp-box">
            Bank Stamp:
        </div>
        
        <div style="clear: both;"></div>
    </div>
</div>

<!-- PAGE 3 -->
<div class="page">
    <!-- Header with logos -->
    <table class="header-table">
        <tr>
            <td class="logo-qupa">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/qupa.png'))) }}" alt="Qupa Logo">
                <div class="micro-finance">Micro-Finance</div>
                <div class="registered">Registered Microfinance</div>
            </td>
        </tr>
    </table>
    
    <div class="address-text" style="margin: 5px 0;">
        ZB Chamber, 2nd Floor, corner 1st Street & George Silundika Harare
    </div>
    
    <h2 style="text-align: center; margin: 15px 0; font-size: 12pt; text-decoration: underline;">DEBIT ORDER AUTHORIZATION</h2>
    
    <h3 style="text-align: center; font-size: 10pt; margin: 10px 0;">TO BE COMPLETED BY THE APPLICANT AND ZB ACCOUNT HOLDER</h3>
    
    <table class="main-table" style="margin-top: 20px;">
        <tr>
            <td colspan="2" style="text-align: center; background-color: #f0f0f0; font-weight: bold;">APPLICANTS DETAILS</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px;">
                <strong>PART A</strong><br/><br/>
                Surname: {{ $surname ?? '' }}<br/><br/>
                Forename(s): {{ $firstName ?? '' }} {{ $otherNames ?? '' }}<br/><br/>
                Address: {{ $formatAddress($residentialAddress ?? '') }}<br/><br/>
                Telephone Nos. Cell No: {{ $mobile ?? '' }} Work: {{ $telephoneRes ?? '' }}<br/><br/>
                Email Address: {{ $emailAddress ?? '' }} National ID: {{ $nationalIdNumber ?? '' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; background-color: #f0f0f0; font-weight: bold;">DEBIT DETAILS</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px;">
                <strong>PART B</strong><br/><br/>
                Monthly Repayment to be debited Amount: ${{ $monthlyPayment ?? '' }} For {{ $loanTenure ?? '' }} months (tenure)<br/><br/>
                From ZB Account Number: {{ $accountNumber ?? '' }} Branch: {{ $branch ?? '' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; background-color: #f0f0f0; font-weight: bold;">CREDIT DETAILS</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px;">
                Due Dates Of Payments: {{ $applicationDate ?? '' }}<br/><br/>
                ZB Qupa Account No to be credited: 
            </td>
        </tr>
    </table>
    
    <div style="border: 1px solid #000; padding: 10px; margin-top: 20px; font-size: 8pt;">
        <ol>
            <li>I, the undersigned request QUPA MICROFINANCE (PRIVATE) LIMITED to arrange with ZB Bank for credit repayments payable in terms of the conditions of the finance facility or as they may be amended from time to time, credit repayments to be drawn against my account wherever it may be according to the debit order system, without prejudice to QUPA MICROFINANCE (PRIVATE) LIMITED'S rights.</li>
            <li>I realize that I am not entitled to recover any amount drawn on my account in terms of this debit order and that should Bank/Building Society repay such an amount to me, I should have to refund it to QUPA MICROFINANCE (PRIVATE) LIMITED.</li>
            <li>I will not revoke this arrangement at any time without the consent and authorization of QUPA MICROFINANCE (PRIVATE) LIMITED whose authorization shall be in writing, but the revocation will have no effect on deductions which have been or could have been made in accordance with this authority.</li>
            <li>I agree that I shall have no claim against the Bank or the Building Society in the event of any of the payments not being paid on the due date for any reason whatsoever. I undertake to ensure that the account is adequately funded to provide for credit instalments or repayments as and when requested by my Bank/Building Society or by QUPA MICROFINANCE (PRIVATE) LIMITED. I understand and agree that I will be liable for any penalties levied by the Bank / Building Society on any debit order request made by QUPA MICROFINANCE (PRIVATE) LIMITED in terms of this authorization when my account is not adequately funded.</li>
        </ol>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <p>Signature(s) of account holder
            @if(isset($signatureImage) && !empty($signatureImage))
                <img src="{{ $signatureImage }}" style="max-width: 100px; max-height: 30px; vertical-align: middle;" alt="Digital Signature">
            @endif
            Date: {{ $applicationDate ?? '' }}
        </p>
    </div>
</div>

<!-- PAGE 4 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim.png'))) }}" alt="BancoZim Logo" style="max-width: 80px; height: auto;">
            </td>
        </tr>
    </table>

    <h3 style="text-align: center; text-decoration: underline; margin: 20px 0;">PRODUCT ORDER FORM (P.O.F)</h3>
    
    <table class="main-table">
        <tr>
            <td class="field-label">Date</td>
            <td colspan="3">{{ $applicationDate ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Client Name</td>
            <td colspan="3">{{ $firstName ?? '' }} {{ $surname ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">E.C Number</td>
            <td colspan="3">{{ $employmentNumber ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Delivery Address</td>
            <td colspan="3">{{ $deliveryAddress ?: $formatAddress($residentialAddress ?? '') }}</td>
        </tr>
    </table>
    
    <table class="main-table" style="margin-top: 20px;">
        <tr>
            <th style="width: 40%;">PRODUCT/ITEM DESCRIPTION</th>
            <th style="width: 20%;">PRODUCT CODE</th>
            <th style="width: 20%;">QUANTITY</th>
            <th style="width: 20%;">INSTALMENT</th>
        </tr>
        @if(isset($lineItems) && count($lineItems) > 0)
            @php $itemCount = 0; @endphp
            @foreach($lineItems as $item)
                @php $itemCount++; @endphp
                <tr>
                    <td>{{ $itemCount }}) {{ $item['name'] }} @if(!empty($item['specification'])) <br><span style="font-size: 8pt;">{{ $item['specification'] }}</span> @endif</td>
                    <td>{{ $item['code'] ?? '' }}</td>
                    <td style="text-align: center;">{{ $item['quantity'] }}</td>
                    <td style="text-align: center;">
                        @if($loop->last)
                            ${{ $monthlyPayment ?? '' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>1) {{ $productDescription ?: ($productName ?? $purposeAsset ?? '') }}</td>
                <td></td>
                <td style="text-align: center;">1</td>
                <td style="text-align: center;">${{ $monthlyPayment ?? '' }}</td>
            </tr>
            <tr>
                <td>2)</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>3)</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td style="text-align: right; font-weight: bold;">TOTAL:-</td>
            <td></td>
            <td>1</td>
            <td>${{ $monthlyPayment ?? '' }}</td>
        </tr>
    </table>
    
    <div style="margin-top: 20px; font-weight: bold;">DECLARATION BY ORDERING CLIENT:-</div>
    
    <div style="padding: 10px; font-size: 9pt; text-align: justify;">
        I the undersigned {{ $firstName ?? '' }} {{ $surname ?? '' }} (also known as the client) do hereby confirm that today I have indeed ordered the above stated products(s)/item(s) from Bancozim (also known as the Merchant supplier) .This being made possible and facilitated by Qupa Microfinance (also known as the financier )who will be deemed to be the custodians of the consequential loan and if successfully processed I will be fully indebted to them for all loan related issues .I authorize Qupa Microfinance or Microfinance pay out to the full value of the product.
    </div>
    
    <h4 style="margin-top: 20px;">I declare that if I initiate cancellation of this application for whatever reason then I authorize and agree to be charged a penalty equivalent to one month instalment. Which amount deemed as an administration cost shall be deducted directly from my salary without prejudice.</h4>
    
    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td style="width: 15%;">Signature</td>
            <td style="width: 85%; border-bottom: 1px solid #000; height: 50px; vertical-align: bottom;">
                @if(isset($signatureImage) && !empty($signatureImage))
                    <img src="{{ $signatureImage }}" style="max-width: 150px; max-height: 40px;" alt="Digital Signature">
                @endif
            </td>
        </tr>
    </table>

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
                <td>{{ $firstName ?? '' }} {{ $surname ?? '' }}</td>
            </tr>
            <tr>
                <td>INVOICE NO</td>
                <td>{{ $nationalIdNumber ?? $idNumber ?? '' }}</td>
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
                    <td>{{ $creditFacilityType ?? $productName ?? $business ?? $purposeAsset ?? $loanPurpose ?? $category ?? '' }}</td>
                    <td>{{ $productCode ?? '' }}</td>
                    <td>${{ number_format((float)str_replace(',', '', $loanAmount ?? $amount ?? $finalPrice ?? $netLoan ?? $productAmount ?? $sellingPrice ?? '0'), 2) }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="invoice-total-row">
                    <td colspan="2" style="text-align: right;">TOTAL DUE:</td>
                    <td>${{ number_format((float)str_replace(',', '', $loanAmount ?? $amount ?? $finalPrice ?? $netLoan ?? $productAmount ?? $sellingPrice ?? '0'), 2) }}</td>
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

    <div style="margin-top: 10px; font-size: 7pt; text-align: center; color: #666;">
        <em>End of Application Form</em>
    </div>
</div>

{{-- Include Document Attachments (ID, Payslip, etc.) --}}
@include('forms.partials.pdf_attachments')

    <!-- FCB Report Attachment -->
    @if(isset($fcbData))
        @include('reports.fcb_report', ['data' => $fcbData])
    @endif
</body>
</html>