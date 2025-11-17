<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SME Business Account Opening Form</title>
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
            min-height: 275mm;
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
            max-width: 80px;
            height: auto;
        }
        
        .logo-bancozim img {
            max-width: 80px;
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
        
        .address-text {
            font-size: 8pt;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .legal-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 10px 0;
            font-size: 8pt;
            text-align: justify;
        }
        
        .signature-table {
            width: 100%;
            margin-top: 20px;
        }
        
        .signature-field {
            border-bottom: 1px solid #000;
            min-height: 30px;
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
            <td style="width: 50%; text-align: center; vertical-align: middle;">
                <table style="width: 300px; border: 1px solid #000; border-collapse: collapse; margin: 0 auto;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 2px; width: 33%; font-size: 7pt;">Delivery Status :</td>
                        <td style="border: 1px solid #000; padding: 2px; width: 33%; font-size: 7pt;">{{ $deliveryStatus ?? 'Future' }}</td>
                        <td style="border: 1px solid #000; padding: 2px; width: 34%; font-size: 7pt;">Agent : {{ $agent ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; padding: 2px; font-size: 7pt;">Province :</td>
                        <td style="border: 1px solid #000; padding: 2px; font-size: 7pt;">{{ $province ?? '' }}</td>
                        <td style="border: 1px solid #000; padding: 2px; font-size: 7pt;">Team : {{ $team ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td class="logo-bancozim">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/bancozim.png'))) }}" alt="BancoZim Logo">
            </td>
        </tr>
    </table>
    
    <div class="address-text" style="margin: 5px 0;">
        ZB Chamber, 2nd Floor, corner 1st Street & George Silundika Harare
    </div>
    
    <div class="section-header">SME BUSINESS ACCOUNT OPENING APPLICATION</div>
    
    <div class="legal-box" style="margin: 5px 0; padding: 5px; font-size: 7pt;">
        Qupa Microfinance Ltd (Hereinafter referred to as "the Lender" which expression, unless repugnant to the context or meaning hereof, shall include it's successor(s), administrator(s) or permitted assignee(s)) is a registered microfinance institution established and existing under the laws of Zimbabwe and having its registered corporate offices at 2nd floor, Chambers House, Cnr First and G Silundika, Harare.
        <br/><br/>
        <strong>AND</strong>
    </div>
    
    <div class="section-header">1. BUSINESS DETAILS</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 25%;">Business Name: {{ $businessName ?? '' }}</td>
            <td style="width: 25%;">Registration Number: {{ $businessRegistration ?? '' }}</td>
            <td style="width: 25%;">Business Type: {{ $businessType ?? '' }}</td>
            <td style="width: 25%;">Date Established: {{ $dateEstablished ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2">Physical Address: {{ $businessAddress ?? '' }}</td>
            <td>City: {{ $businessCity ?? '' }}</td>
            <td>Province: {{ $businessProvince ?? '' }}</td>
        </tr>
        <tr>
            <td>Postal Address: {{ $postalAddress ?? '' }}</td>
            <td>Telephone: {{ $businessTelephone ?? '' }}</td>
            <td>Mobile: {{ $businessMobile ?? '' }}</td>
            <td>Email: {{ $businessEmail ?? '' }}</td>
        </tr>
        <tr>
            <td>Industry Sector: {{ $industrySector ?? '' }}</td>
            <td>Number of Employees: {{ $numberOfEmployees ?? '' }}</td>
            <td colspan="2">Monthly Turnover (USD): ${{ $monthlyTurnover ?? '' }}</td>
        </tr>
    </table>
    
    <div class="section-header">2. BUSINESS OWNER/DIRECTOR DETAILS</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 15%;">Title: {{ $title ?? 'Mr/ Mrs/ Miss' }}</td>
            <td style="width: 20%;">Surname: {{ $surname ?? '' }}</td>
            <td style="width: 20%;">First Name: {{ $firstName ?? '' }}</td>
            <td style="width: 15%;">Gender: {{ $gender ?? 'Male/ Female' }}</td>
            <td style="width: 15%;">Date of Birth: {{ $dateOfBirth ?? '' }}</td>
            <td style="width: 15%;">Nationality: {{ $nationality ?? 'Zimbabwean' }}</td>
        </tr>
        <tr>
            <td colspan="3">National ID Number: {{ $nationalIdNumber ?? '' }}</td>
            <td colspan="3">Position in Business: {{ $positionInBusiness ?? '' }}</td>
        </tr>
        <tr>
            <td>Cell Number: {{ $mobile ?? '' }}</td>
            <td>WhatsApp: {{ $whatsApp ?? '' }}</td>
            <td colspan="4">Email Address: {{ $emailAddress ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="6">Residential Address: {{ $residentialAddress ?? '' }}</td>
        </tr>
    </table>
    
    <div class="section-header">3. FINANCIAL INFORMATION</div>
    
    <table class="main-table">
        <tr>
            <th style="width: 50%;">Income Source</th>
            <th style="width: 25%;">Monthly Amount (USD)</th>
            <th style="width: 25%;">Annual Amount (USD)</th>
        </tr>
        <tr>
            <td>Business Revenue</td>
            <td>${{ $monthlyRevenue ?? '' }}</td>
            <td>${{ $annualRevenue ?? '' }}</td>
        </tr>
        <tr>
            <td>Other Income</td>
            <td>${{ $otherMonthlyIncome ?? '' }}</td>
            <td>${{ $otherAnnualIncome ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Total Income</strong></td>
            <td><strong>${{ $totalMonthlyIncome ?? '' }}</strong></td>
            <td><strong>${{ $totalAnnualIncome ?? '' }}</strong></td>
        </tr>
    </table>
    
    <div class="section-header">4. BANKING DETAILS</div>
    
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
    
    <div class="section-header">5. REQUIRED DOCUMENTATION</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 5%;">☐</td>
            <td>Certificate of Incorporation</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>Certificate of Good Standing (Companies Registry)</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>Directors' Resolution to Open Account</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>Directors' Certified Copies of IDs</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>Proof of Business Address</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>Business License/Trading License</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>VAT Registration Certificate (if applicable)</td>
        </tr>
        <tr>
            <td>☐</td>
            <td>Recent Bank Statements (3 months)</td>
        </tr>
    </table>
</div>

<!-- PAGE 2 -->
<div class="page">
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
    
    <div class="section-header">ACCOUNT TYPE SELECTION</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 25%;">Business Current Account <span class="checkbox {{ $accountType === 'Business Current' ? 'checked' : '' }}"></span></td>
            <td style="width: 25%;">Business Savings Account <span class="checkbox {{ $accountType === 'Business Savings' ? 'checked' : '' }}"></span></td>
            <td style="width: 25%;">USD Account <span class="checkbox {{ $accountType === 'USD Account' ? 'checked' : '' }}"></span></td>
            <td style="width: 25%;">Other: {{ $accountType !== 'Business Current' && $accountType !== 'Business Savings' && $accountType !== 'USD Account' ? $accountType : '' }}</td>
        </tr>
    </table>
    
    <div class="section-header">INITIAL DEPOSIT INFORMATION</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 50%;">Initial Deposit Amount (USD): ${{ $initialDeposit ?? '' }}</td>
            <td style="width: 50%;">Deposit Method: {{ $depositMethod ?? 'Cash / Cheque / Transfer' }}</td>
        </tr>
    </table>
    
    <div class="section-header">SERVICES REQUIRED</div>
    
    <table class="main-table">
        <tr>
            <td style="width: 50%;">Internet Banking <span class="checkbox {{ in_array('Internet Banking', $servicesRequired ?? []) ? 'checked' : '' }}"></span></td>
            <td style="width: 50%;">Mobile Banking <span class="checkbox {{ in_array('Mobile Banking', $servicesRequired ?? []) ? 'checked' : '' }}"></span></td>
        </tr>
        <tr>
            <td>Debit Card <span class="checkbox {{ in_array('Debit Card', $servicesRequired ?? []) ? 'checked' : '' }}"></span></td>
            <td>Cheque Book <span class="checkbox {{ in_array('Cheque Book', $servicesRequired ?? []) ? 'checked' : '' }}"></span></td>
        </tr>
        <tr>
            <td>SMS Alerts <span class="checkbox {{ in_array('SMS Alerts', $servicesRequired ?? []) ? 'checked' : '' }}"></span></td>
            <td>Email Statements <span class="checkbox {{ in_array('Email Statements', $servicesRequired ?? []) ? 'checked' : '' }}"></span></td>
        </tr>
    </table>
    
    <div class="section-header">DECLARATION</div>
    
    <div class="legal-box">
        I/We declare that the information given above is accurate and correct. I/We are aware that falsifying information automatically leads to decline of the account opening application. I/We authorize Qupa Microfinance to obtain and use the information obtained for the purposes of this application from recognized sources. I/We undertake to provide all documents required by Qupa Microfinance and to update all records in the event of change of any business or personal details.
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
    
    <div class="section-header" style="margin-top: 30px;">FOR OFFICIAL USE ONLY</div>
    
    <table class="main-table">
        <tr>
            <td class="field-label" style="width: 20%;">Received & Processed by:</td>
            <td style="width: 20%;">Name:</td>
            <td style="width: 20%;"></td>
            <td style="width: 15%;">Signature:</td>
            <td style="width: 15%;"></td>
            <td style="width: 10%;">Date:</td>
        </tr>
        <tr>
            <td class="field-label">Account Number Assigned:</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td class="field-label">Initial Deposit Received:</td>
            <td>Amount: $</td>
            <td></td>
            <td>Receipt No:</td>
            <td colspan="2"></td>
        </tr>
    </table>
    
    <div style="margin-top: 30px; text-align: center;">
        <strong>Welcome to Qupa Microfinance - Your Partner in Business Growth</strong>
    </div>
</div>

</body>
</html>