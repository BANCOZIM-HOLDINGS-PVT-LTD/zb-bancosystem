<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSB Form - Qupa Microfinance & BancoZim</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            margin: 0;
            padding: 0;
        }

        .page {
            page-break-after: always;
            padding: 0;
            margin: 0;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-qupa {
            color: #5cb85c;
            font-size: 48px;
            font-weight: bold;
        }

        .logo-bancozim {
            color: #ff6633;
            font-size: 36px;
            font-weight: bold;
        }

        .tagline {
            color: #666;
            font-size: 11px;
        }

        .address {
            margin: 10px 0;
            font-size: 11px;
        }

        .header-table {
            width: 300px;
            border-collapse: collapse;
            float: right;
        }

        .header-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 10px;
        }

        .section-title {
            background: #9acd32;
            color: white;
            padding: 8px;
            font-weight: bold;
            margin: 15px 0 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        table td, table th {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 11px;
            vertical-align: top;
        }

        .notice-box {
            border: 2px solid #000;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            font-size: 11px;
        }

        .declaration-box {
            border: 2px solid #000;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            font-size: 10px;
            line-height: 1.6;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .signature-field {
            display: inline-block;
            margin: 0 10px;
        }

        .signature-field label {
            font-weight: bold;
            margin-right: 10px;
        }

        .signature-field .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            height: 30px;
            display: inline-block;
        }

        .checkbox-group {
            display: flex;
            gap: 15px;
            margin: 10px 0;
        }

        .small-box {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 1px solid #000;
            margin: 0 3px;
            text-align: center;
            vertical-align: middle;
        }

        .stamp-box {
            width: 150px;
            height: 100px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            padding-top: 80px;
            font-size: 10px;
        }

        .checklist {
            margin: 20px 0;
        }

        .checklist ol {
            margin-left: 30px;
        }

        .checklist li {
            margin: 5px 0;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .fingerprint {
            width: 120px;
            height: 150px;
            background: #6699cc;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            margin: 5px 0;
            height: 20px;
        }

        .filled-field {
            border-bottom: 1px solid #000;
            min-height: 20px;
            display: inline-block;
            padding: 2px;
        }
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            display: inline-block;
            vertical-align: middle;
            position: relative;
            margin: 0 2px;
            background-color: #000;
        }

        .checkbox-x {
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            display: inline-block;
            vertical-align: middle;
            position: relative;
            margin: 0 2px;
        }

        .checkbox-x::before,
        .checkbox-x::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 1px;
            background: black;
            top: 50%;
            left: 0;
            transform-origin: center;
        }

        .checkbox-x::before {
            transform: translateY(-50%) rotate(45deg);
        }

        .checkbox-x::after {
            transform: translateY(-50%) rotate(-45deg);
        }

        .declaration-box {
            border: 1px solid #000;
            padding: 3px;
            margin: 2px 0;
            font-size: 8pt;
            text-align: justify;
            line-height: 1.1;
        }

        .input-box {
            border: 1px solid #000;
            display: inline-block;
            width: 12px;
            height: 12px;
            text-align: center;
            margin: 0 1px;
            vertical-align: middle;
            line-height: 12px;
            font-size: 8pt;
        }

        .stamp-box {
            width: 100px;
            height: 60px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            line-height: 60px;
            font-size: 8pt;
        }

        .address-text {
            font-size: 8pt;
            margin-bottom: 4px;
        }

        .section-spacing {
            margin: 6px 0;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .page {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

<!-- PAGE 1 -->
<div class="page">
    <!-- Header with logos and delivery status -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/images/qupa.png') }}" alt="Qupa Logo" class="logo-img">
            </td>
            <td>
                <table class="header-table" style="margin: 0;">
                    <tr>
                        <td>Delivery Status</td>
                        <td>:</td>
                        <td>{{ $formResponses['deliveryStatus'] ?? 'Future' }}</td>
                        <td>Agent</td>
                        <td>:</td>
                        <td>{{ $formResponses['agent'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Province</td>
                        <td>:</td>
                        <td>{{ $formResponses['province'] ?? '' }}</td>
                        <td>Team</td>
                        <td>:</td>
                        <td>{{ $formResponses['team'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td class="logo-cell">
                <img src="{{ public_path('assets/images/bancozim.png') }}" alt="BancoZim Logo" class="logo-img">
            </td>
        </tr>
    </table>

    <div class="address-text">
        ZB Chamber, 2nd Floor, corner 1st Street & George Silundika<br>
        Harare, Zimbabwe
    </div>

    <div class="green-header">SSB LOAN APPLICATION AND CONTRACT FORM</div>

    <div class="declaration-box">
        Qupa Microfinance Ltd (Hereinafter referred to as "the Lender" which expression, unless repugnant to the context or meaning hereof, shall include it's successor(s), administrator(s) or permitted assignee(s)) is a registered microfinance institution established and existing under the laws of Zimbabwe and having its registered corporate offices at 2nd floor, Chambers House, Cnr First and G.Silundika, Harare.
        <br>
        <center><strong>AND</strong></center>
    </div>

    <div class="green-header">1.CUSTOMER/PERSONAL DETAILS</div>

    <!-- Row 1: Title, Surname, First Name, Gender, Date of Birth -->
    <table class="form-table">
        <tr>
            <td>Title: {{ $formResponses['title'] ?? 'Mr/ Mrs/ Miss' }}</td>
            <td>Surname: {{ $formResponses['surname'] ?? '' }}</td>
            <td>First Name: {{ $formResponses['firstName'] ?? '' }}</td>
            <td>Gender: {{ $formResponses['gender'] ?? 'Male/ Female' }}</td>
            <td>Date of Birth: {{ $formResponses['dateOfBirth'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 2: Marital Status, Nationality, ID Number -->
    <table class="form-table">
        <tr>
            <td colspan="2">Marital Status: {{ $formResponses['maritalStatus'] ?? 'Single/ Married/ Divorced/ Widowed' }}</td>
            <td>Nationality: {{ $formResponses['nationality'] ?? 'Zimbabwean' }}</td>
            <td colspan="2">I.D Number: {{ $formResponses['nationalIdNumber'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 3: Cell Number, WhatsApp, Email Address -->
    <table class="form-table">
        <tr>
            <td>Cell Number: {{ $formResponses['mobile'] ?? '' }}</td>
            <td>WhatsApp: {{ $formResponses['whatsApp'] ?? '' }}</td>
            <td colspan="3">Email Address: {{ $formResponses['emailAddress'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 4: Ministry -->
    <table class="form-table">
        <tr>
            <td colspan="5">Name of Responsible Ministry: {{ $formResponses['responsibleMinistry'] ?? 'Education / Health / Home Affairs / Justice / Other ........................' }}</td>
        </tr>
    </table>

    <!-- Row 5: Employer, Employer Address -->
    <table class="form-table">
        <tr>
            <td colspan="2">Name of Employer: {{ $formResponses['employerName'] ?? '' }}</td>
            <td colspan="3">Employer Address: {{ $formResponses['employerAddress'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 6: Permanent Address -->
    <table class="form-table">
        <tr>
            <td colspan="5">Permanent Address (if different from above): {{ $formResponses['residentialAddress'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 7: Property Ownership -->
    <table class="form-table">
        <tr>
            <td>Property Ownership: Owned 
                @if(isset($formResponses['propertyOwnership']) && $formResponses['propertyOwnership'] == 'Owned')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Employer Owned 
                @if(isset($formResponses['propertyOwnership']) && $formResponses['propertyOwnership'] == 'Employer Owned')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Rented 
                @if(isset($formResponses['propertyOwnership']) && $formResponses['propertyOwnership'] == 'Rented')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Mortgaged 
                @if(isset($formResponses['propertyOwnership']) && $formResponses['propertyOwnership'] == 'Mortgaged')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Parents Owned 
                @if(isset($formResponses['propertyOwnership']) && $formResponses['propertyOwnership'] == 'Parents Owned')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Row 8: Period at current address -->
    <table class="form-table">
        <tr>
            <td>Period at current address:</td>
            <td>Less than One Year 
                @if(isset($formResponses['periodAtAddress']) && $formResponses['periodAtAddress'] == 'Less than One Year')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Between 1-2 years 
                @if(isset($formResponses['periodAtAddress']) && $formResponses['periodAtAddress'] == 'Between 1-2 years')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Between 2-5 years 
                @if(isset($formResponses['periodAtAddress']) && $formResponses['periodAtAddress'] == 'Between 2-5 years')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>More than 5 years 
                @if(isset($formResponses['periodAtAddress']) && $formResponses['periodAtAddress'] == 'More than 5 years')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Row 9: Status of employment -->
    <table class="form-table">
        <tr>
            <td>Status of employment:</td>
            <td>Permanent 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Permanent')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Contract 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Contract')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td>Part time 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Part time')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td></td>
        </tr>
    </table>

    <!-- Row 10: Job Title, Date of Employment, Name of Head -->
    <table class="form-table">
        <tr>
            <td>Job Title: {{ $formResponses['jobTitle'] ?? '' }}</td>
            <td colspan="2">Date of Employment: {{ $formResponses['dateOfEmployment'] ?? '' }}</td>
            <td colspan="2" rowspan="2">Name of Head of Institution: {{ $formResponses['headOfInstitution'] ?? 'Mr /Mrs /Miss....................' }}<br>
                Cell No of Head of Institution: {{ $formResponses['headOfInstitutionCell'] ?? '' }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2"></td>
        </tr>
    </table>

    <!-- Row 11: Employment number, Current Net Salary -->
    <table class="form-table">
        <tr>
            <td colspan="2">Employment number: {{ $formResponses['employeeNumber'] ?? '' }}</td>
            <td colspan="3">Current Net Salary (USD): ${{ $formResponses['currentNetSalary'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td colspan="3"></td>
        </tr>
    </table>

    <div class="green-header">2.SPOUSE AND NEXT OF KIN DETAILS</div>

    <table class="form-table">
        <tr>
            <td>Full names</td>
            <td>Relationship</td>
            <td>Phone Numbers</td>
            <td>Residential address</td>
        </tr>
        <tr>
            <td>1. {{ isset($formResponses['spouseDetails'][0]['fullName']) ? $formResponses['spouseDetails'][0]['fullName'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][0]['relationship']) ? $formResponses['spouseDetails'][0]['relationship'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][0]['phoneNumber']) ? $formResponses['spouseDetails'][0]['phoneNumber'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][0]['residentialAddress']) ? $formResponses['spouseDetails'][0]['residentialAddress'] : '' }}</td>
        </tr>
        <tr>
            <td>2. {{ isset($formResponses['spouseDetails'][1]['fullName']) ? $formResponses['spouseDetails'][1]['fullName'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][1]['relationship']) ? $formResponses['spouseDetails'][1]['relationship'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][1]['phoneNumber']) ? $formResponses['spouseDetails'][1]['phoneNumber'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][1]['residentialAddress']) ? $formResponses['spouseDetails'][1]['residentialAddress'] : '' }}</td>
        </tr>
        <tr>
            <td>3. {{ isset($formResponses['spouseDetails'][2]['fullName']) ? $formResponses['spouseDetails'][2]['fullName'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][2]['relationship']) ? $formResponses['spouseDetails'][2]['relationship'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][2]['phoneNumber']) ? $formResponses['spouseDetails'][2]['phoneNumber'] : '' }}</td>
            <td>{{ isset($formResponses['spouseDetails'][2]['residentialAddress']) ? $formResponses['spouseDetails'][2]['residentialAddress'] : '' }}</td>
        </tr>
    </table>

    <div class="green-header">3.BANKING/MOBILE ACCOUNT DETAILS</div>

    <table class="form-table">
        <tr>
            <td>BANK</td>
            <td>BRANCH</td>
            <td>ACCOUNT NUMBER</td>
        </tr>
        <tr>
            <td>{{ $formResponses['bankName'] ?? '' }}</td>
            <td>{{ $formResponses['branch'] ?? '' }}</td>
            <td>{{ $formResponses['accountNumber'] ?? '' }}</td>
        </tr>
    </table>

    <div class="green-header">4.LOANS WITH OTHER INSTITUTIONS (ALSO INCLUDE QUPA LOAN)</div>

    <table class="form-table">
        <tr>
            <td>INSTITUTION</td>
            <td>MONTHLY INSTALMENT</td>
            <td>CURRENT LOAN BALANCE</td>
            <td>MATURITY DATE</td>
        </tr>
        <tr>
            <td>1. {{ isset($formResponses['otherLoans'][0]['institution']) ? $formResponses['otherLoans'][0]['institution'] : '' }}</td>
            <td>{{ isset($formResponses['otherLoans'][0]['monthlyInstallment']) ? $formResponses['otherLoans'][0]['monthlyInstallment'] : '' }}</td>
            <td>{{ isset($formResponses['otherLoans'][0]['currentBalance']) ? $formResponses['otherLoans'][0]['currentBalance'] : '' }}</td>
            <td>{{ isset($formResponses['otherLoans'][0]['maturityDate']) ? $formResponses['otherLoans'][0]['maturityDate'] : '' }}</td>
        </tr>
        <tr>
            <td>2. {{ isset($formResponses['otherLoans'][1]['institution']) ? $formResponses['otherLoans'][1]['institution'] : '' }}</td>
            <td>{{ isset($formResponses['otherLoans'][1]['monthlyInstallment']) ? $formResponses['otherLoans'][1]['monthlyInstallment'] : '' }}</td>
            <td>{{ isset($formResponses['otherLoans'][1]['currentBalance']) ? $formResponses['otherLoans'][1]['currentBalance'] : '' }}</td>
            <td>{{ isset($formResponses['otherLoans'][1]['maturityDate']) ? $formResponses['otherLoans'][1]['maturityDate'] : '' }}</td>
        </tr>
    </table>

    <div class="green-header">5.CREDIT FACILITY APPLICATION DETAILS</div>

    <table class="form-table">
        <tr>
            <td>Price/ Applied Amount: ${{ $formResponses['loanAmount'] ?? '............' }}</td>
            <td style="text-align: center;">3 months<br>
                @if(isset($formResponses['loanTenure']) && $formResponses['loanTenure'] == '3')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td style="text-align: center;">6 months<br>
                @if(isset($formResponses['loanTenure']) && $formResponses['loanTenure'] == '6')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td style="text-align: center;">9 months<br>
                @if(isset($formResponses['loanTenure']) && $formResponses['loanTenure'] == '9')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td style="text-align: center;">12 months<br>
                @if(isset($formResponses['loanTenure']) && $formResponses['loanTenure'] == '12')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td style="text-align: center;">Other:<br>
                @if(isset($formResponses['loanTenure']) && !in_array($formResponses['loanTenure'], ['3', '6', '9', '12']))
                    {{ $formResponses['loanTenure'] }}
                @else
                    _______
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="6" style="height: 80px; vertical-align: top; padding-top: 5px;">
                Purpose/ Asset Applied For<br>
                <div style="margin-top: 10px; height: 45px;">
                    {{ $formResponses['purposeAsset'] ?? $formResponses['purposeOfLoan'] ?? '' }}
                </div>
            </td>
        </tr>
    </table>
</div>
<!-- PAGE 2 -->
<div class="page">
    <table style="width: 100%; margin-bottom: 8px;">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/images/qupa.png') }}" alt="Qupa Logo" class="logo-img">
            </td>
            <td style="text-align: right; font-size: 8pt; vertical-align: top;">
                ZB Chamber, 2nd Floor, corner 1st Street & George Silundika<br>
                Harare, Zimbabwe<br>
                Tel: +263 867 700 2005 Email: loans@qupa.co.zw
            </td>
        </tr>
    </table>

    <div class="green-header">EARLY CONTRACT TERMINATION</div>

    <div style="padding: 2px; font-size: 8pt;">
        The Borrower has the Option to pay up the credit earlier than the maturity date. The Borrower shall, however, pay Qupa Microfinance early termination administration fees if the cancellation is voluntarily requested by the Borrower.
    </div>

    <div class="green-header">IMPORTANT NOTICE</div>

    <div style="padding: 2px; font-size: 8pt;">
        <ol style="margin: 0; padding-left: 12px;">
            <li style="margin-bottom: 2px;">The terms and conditions highlighted in this agreement can be explained in the Borrower's local language upon request of the Borrower.</li>
            <li style="margin-bottom: 2px;">Qupa Microfinance reserves the right to decrease or increase the instalment amount or adjust the tenure where credit performance is an issue or statutory changes.</li>
            <li>For all queries or enquiries customers should contact Qupa through the ZB branch network or Bancozim.</li>
        </ol>
    </div>

    <div class="green-header">CREDIT CESSION AND COLLATERAL</div>

    <div style="padding: 2px; font-size: 8pt;">
        <ol style="margin: 0; padding-left: 12px;">
            <li style="margin-bottom: 2px;">The Borrower hereby conclusively and unconditionally cede the credit to Bancozim.</li>
            <li>The Borrower hereby cede the purchased item(s) as collateral for the credit and further authorises Qupa Microfinance through the Merchant to repossess or deny the use of the pledged asset in the event of a default.</li>
        </ol>
    </div>

    <div class="green-header">DECLARATION</div>

    <div class="declaration-box">
        I declare that the information given above is accurate and correct. I am aware that falsifying information automatically leads to decline of my credit application. I authorise Qupa Microfinance to obtain and use the information obtained for the purposes of this application from the recognised credit bureau. I authorise Qupa Microfinance to obtain references from Bancozim if there is need. I undertake to provide all documents required by Qupa Microfinance and to update all records in the event of change of any personal details. I hereby consent to be covered by Qupa microfinance insurance policy which Qupa takes out with the insurance company of its choosing and further cede my rights to Qupa microfinance in the event of a claim.
    </div>

    <table style="width: 100%; margin-top: 4px; font-size: 8pt;">
        <tr>
            <td style="width: 60px;">Full Name:</td>
            <td style="width: 200px; border: 1px solid #000; height: 15px; padding: 1px;">
                {{ isset($formResponses['declaration']['fullName']) ? $formResponses['declaration']['fullName'] : $formResponses['firstName'] . ' ' . $formResponses['surname'] }}
            </td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 150px; border: 1px solid #000; height: 15px; padding: 1px;">
                @if(isset($documents['signature']) && $documents['signature'])
                    <img src="{{ $documents['signature'] }}" alt="Signature" style="max-height: 15px; max-width: 150px;">
                @endif
            </td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border: 1px solid #000; height: 15px; padding: 1px;">
                {{ isset($formResponses['declaration']['date']) ? $formResponses['declaration']['date'] : date('Y-m-d') }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 4px; font-size: 8pt;">
        <strong>Witness</strong>
    </div>

    <table style="width: 100%; margin-top: 2px; font-size: 8pt;">
        <tr>
            <td style="width: 60px;">Full Name:</td>
            <td style="width: 200px; border: 1px solid #000; height: 15px; padding: 1px;"></td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 150px; border: 1px solid #000; height: 15px; padding: 1px;"></td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border: 1px solid #000; height: 15px; padding: 1px;"></td>
        </tr>
    </table>

    <div class="green-header" style="margin-top: 6px;">FOR OFFICIAL USE ONLY</div>

    <table class="form-table">
        <tr>
            <td>Received & Checked by:</td>
            <td>Name:</td>
            <td>Signature:</td>
            <td>Date:</td>
        </tr>
        <tr>
            <td>Approved by:</td>
            <td>Name:</td>
            <td>Signature:</td>
            <td>Date:</td>
        </tr>
    </table>

    <div style="margin-top: 12px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <strong style="font-size: 8pt;">KYC CHECKLIST</strong>
                    <ol style="font-size: 8pt; margin: 4px 0; padding-left: 15px;">
                        <li>Copy of ID or Valid Passport</li>
                        <li>Current Pay Slip/ Business records</li>
                        <li>Confirmation Letter from employer (serves as proof of residence)</li>
                    </ol>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top;">
                    <div class="stamp-box">
                        Bank Stamp:
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- PAGE 3 -->
<div class="page">
    <table style="width: 100%; margin-bottom: 8px;">
        <tr>
            <td style="width: 25%; vertical-align: top; font-size: 8pt; line-height: 1.4;">
                The Manager<br><br>
                Salary Service Bureau<br><br>
                P. O Box CY Causeway
            </td>
            <td style="width: 50%; text-align: left; vertical-align: top; padding-left: 20px;">
                <h2 style="font-size: 11pt; margin: 4px 0; font-weight: bold; text-align: left;">DEDUCTION ORDER FORM - TY 30<br>
                (Please give effect to the following deduction)</h2>

                <div style="margin: 6px 0; font-size: 8pt; text-align: left;">
                    New: <span class="checkbox-x"></span> &nbsp;&nbsp;&nbsp;&nbsp;
                    Change: <span class="checkbox"></span> &nbsp;&nbsp;&nbsp;&nbsp;
                    Delete: <span class="checkbox"></span>
                </div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: top;">
                <div class="logo-cell">
                    <img src="{{ public_path('assets/images/qupa.png') }}" alt="Qupa Logo" class="logo-img">
                </div>
            </td>
        </tr>
    </table>

    <div class="green-header">CUSTOMER DETAILS</div>

    <!-- Row 1: First Name, Surname, ID Number -->
    <table class="form-table">
        <tr>
            <td>First Name:</td>
            <td>{{ $formResponses['firstName'] ?? '' }}</td>
            <td>Surname:</td>
            <td>{{ $formResponses['surname'] ?? '' }}</td>
            <td>ID Number:</td>
            <td>{{ $formResponses['nationalIdNumber'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 2: Ministry, Province -->
    <table class="form-table">
        <tr>
            <td>Ministry:</td>
            <td colspan="3">{{ $formResponses['responsibleMinistry'] ?? '' }}</td>
            <td>Province:</td>
            <td>{{ $formResponses['province'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Row 3: Employee Code Number, Check Letter -->
    <table class="form-table">
        <tr>
            <td>Employee Code Number:</td>
            <td>
                @php
                    $employeeNumber = $formResponses['employeeNumber'] ?? '';
                    $employeeNumberArray = str_split($employeeNumber);
                    for ($i = 0; $i < 8; $i++) {
                        echo '<span class="input-box">' . (isset($employeeNumberArray[$i]) ? $employeeNumberArray[$i] : '') . '</span>';
                    }
                @endphp
            </td>
            <td>Check Letter:</td>
            <td>
                <span class="input-box">{{ isset($formResponses['checkLetter']) ? $formResponses['checkLetter'] : '' }}</span>
            </td>
        </tr>
    </table>

    <!-- Row 4: Station Code, Payee Code -->
    <table class="form-table">
        <tr>
            <td>Station Code:</td>
            <td>
                @php
                    $stationCode = $formResponses['paypoint'] ?? '';
                    $stationCodeArray = str_split($stationCode);
                    for ($i = 0; $i < 4; $i++) {
                        echo '<span class="input-box">' . (isset($stationCodeArray[$i]) ? $stationCodeArray[$i] : '') . '</span>';
                    }
                @endphp
            </td>
            <td>Payee Code:</td>
            <td>
                @php
                    $payeeCode = $formResponses['payrollNumber'] ?? '';
                    $payeeCodeArray = str_split($payeeCode);
                    for ($i = 0; $i < 4; $i++) {
                        echo '<span class="input-box">' . (isset($payeeCodeArray[$i]) ? $payeeCodeArray[$i] : '') . '</span>';
                    }
                @endphp
            </td>
        </tr>
    </table>

    <!-- Row 5: Monthly Rate, From Date, To Date -->
    <table class="form-table">
        <tr>
            <td>Monthly Rate (instalment amount):</td>
            <td>${{ $formResponses['monthlyPayment'] ?? '' }}</td>
            <td>From date:</td>
            <td>
                @php
                    $fromDate = isset($formResponses['loanStartDate']) ? date('d/m/Y', strtotime($formResponses['loanStartDate'])) : date('d/m/Y');
                    $fromDateArray = str_split(str_replace('/', '', $fromDate));
                    $index = 0;
                    for ($i = 0; $i < 10; $i++) {
                        if ($i == 2 || $i == 5) {
                            echo '<span>/</span>';
                        } else {
                            echo '<span class="input-box">' . (isset($fromDateArray[$index]) ? $fromDateArray[$index] : '') . '</span>';
                            $index++;
                        }
                    }
                @endphp
            </td>
            <td>To Date:</td>
            <td>
                @php
                    $toDate = '';
                    if (isset($formResponses['loanStartDate']) && isset($formResponses['loanTenure'])) {
                        $startDate = new DateTime($formResponses['loanStartDate']);
                        $startDate->modify('+' . $formResponses['loanTenure'] . ' months');
                        $toDate = $startDate->format('d/m/Y');
                    } else {
                        $toDate = date('d/m/Y', strtotime('+12 months'));
                    }
                    $toDateArray = str_split(str_replace('/', '', $toDate));
                    $index = 0;
                    for ($i = 0; $i < 10; $i++) {
                        if ($i == 2 || $i == 5) {
                            echo '<span>/</span>';
                        } else {
                            echo '<span class="input-box">' . (isset($toDateArray[$index]) ? $toDateArray[$index] : '') . '</span>';
                            $index++;
                        }
                    }
                @endphp
            </td>
        </tr>
    </table>

    <div class="green-header">DECLARATION</div>

    <div class="declaration-box" style="text-align: justify;">
        I acknowledge receipt of a contract dated {{ date('d/m/Y') }} and confirm that I have read understood, and accept the loan under the terms, conditions and warranties as stated therein and authorise Qupa Microfinance and SSB to deduct money from my earnings or terminal benefits in the event of death or termination of employment according to the above instruction.
    </div>

    <table style="width: 100%; margin-top: 6px; font-size: 8pt; line-height: 1.2;">
        <tr>
            <td style="width: 60px;">Full Name:</td>
            <td style="width: 200px; border: 1px solid #000; height: 18px; padding: 2px;">
                {{ isset($formResponses['declaration']['fullName']) ? $formResponses['declaration']['fullName'] : $formResponses['firstName'] . ' ' . $formResponses['surname'] }}
            </td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 150px; border: 1px solid #000; height: 18px; padding: 2px;">
                @if(isset($documents['signature']) && $documents['signature'])
                    <img src="{{ $documents['signature'] }}" alt="Signature" style="max-height: 18px; max-width: 150px;">
                @endif
            </td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border: 1px solid #000; height: 18px; padding: 2px;">
                {{ isset($formResponses['declaration']['date']) ? $formResponses['declaration']['date'] : date('Y-m-d') }}
            </td>
        </tr>
    </table>

    <div class="green-header" style="margin-top: 8px;">FOR OFFICIAL USE ONLY</div>

    <table style="width: 100%; margin-top: 4px; font-size: 8pt; line-height: 1.2;">
        <tr>
            <td style="width: 100px;">Authorised Signatory:</td>
            <td style="width: 40px;">Name:</td>
            <td style="width: 150px; border: 1px solid #000; height: 18px; padding: 2px;"></td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 120px; border: 1px solid #000; height: 18px; padding: 2px;"></td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border: 1px solid #000; height: 18px; padding: 2px;"></td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 2px; font-size: 8pt; line-height: 1.2;">
        <tr>
            <td style="width: 100px;">Authorised Signatory:</td>
            <td style="width: 40px;">Name:</td>
            <td style="width: 150px; border: 1px solid #000; height: 18px; padding: 2px;"></td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 120px; border: 1px solid #000; height: 18px; padding: 2px;"></td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border: 1px solid #000; height: 18px; padding: 2px;"></td>
        </tr>
    </table>

    <div style="margin-top: 12px; text-align: right;">
        <div class="stamp-box">
            Qupa MFI Stamp:
        </div>
    </div>
</div>

<!-- PAGE 4 - Product Order Form -->
<div class="page">
    <!-- BancoZim Logo -->
    <div style="text-align: center; margin-bottom: 8px;">
        <img src="{{ public_path('assets/images/bancozim.png') }}" alt="BancoZim Logo" class="bancozim-logo">
    </div>

    <h2 style="text-align: center; font-size: 12pt; margin-bottom: 8px; text-decoration: underline;">PRODUCT ORDER FORM (P.O.F)</h2>

    <!-- Customer Information Table -->
    <table class="form-table" style="margin-bottom: 6px;">
        <tr>
            <td style="width: 20%; font-weight: bold;">Date</td>
            <td style="width: 80%;">{{ date('Y-m-d') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Client Name</td>
            <td>{{ $formResponses['firstName'] ?? '' }} {{ $formResponses['surname'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">E.C Number</td>
            <td>{{ $formResponses['employeeNumber'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Delivery Address</td>
            <td>{{ $formResponses['employerAddress'] ?? $formResponses['residentialAddress'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Product Details Table -->
    <table class="form-table" style="margin-bottom: 8px;">
        <tr style="font-weight: bold; text-align: center;">
            <td style="width: 50%;">PRODUCT/ITEM DESCRIPTION</td>
            <td style="width: 16%;">PRODUCT CODE</td>
            <td style="width: 16%;">QUANTITY</td>
            <td style="width: 18%;">INSTALMENT</td>
        </tr>
        <tr>
            <td>1) {{ isset($formResponses['purposeAsset']) ? $formResponses['purposeAsset'] : (isset($formResponses['purposeOfLoan']) ? $formResponses['purposeOfLoan'] : '') }}</td>
            <td style="text-align: center;"></td>
            <td style="text-align: center;">1</td>
            <td style="text-align: center;">${{ $formResponses['monthlyPayment'] ?? '' }}</td>
        </tr>
        <tr>
            <td>2)</td>
            <td style="text-align: center;"></td>
            <td style="text-align: center;"></td>
            <td style="text-align: center;"></td>
        </tr>
        <tr>
            <td>3)</td>
            <td style="text-align: center;"></td>
            <td style="text-align: center;"></td>
            <td style="text-align: center;"></td>
        </tr>
        <tr style="font-weight: bold;">
            <td style="text-align: right;">TOTAL:</td>
            <td></td>
            <td style="text-align: center;">1</td>
            <td style="text-align: center;">${{ $formResponses['monthlyPayment'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Declaration Section -->
    <div style="margin-bottom: 4px;">
        <strong style="text-decoration: underline;">DECLARATION BY ORDERING CLIENT -:</strong>
    </div>

    <div style="font-size: 8pt; text-align: justify; line-height: 1.1; margin-bottom: 6px;">
        I the undersigned {{ $formResponses['firstName'] ?? '' }} {{ $formResponses['surname'] ?? '' }} (also known as the client)
        do hereby confirm that today I have indeed ordered the above stated product(s)/item(s) from
        Bancozim (also known as the product supplier) as per their product catalogue and /or pricelist
        shown to me. This being made possible and facilitated by a zero rated deposit S.S.B micro financing
        funding arrangement by Qupa Microfinance (also known as the financier) who will be deemed to be
        the custodians of the consequential loan and if successfully processed I will be fully indebted to
        them for all future instalments due. I further confirm that I have not been requested to pay an
        advance payment or deposit of any kind and neither have i paid any such to monies to the Bancozim
        representative attending to me. I authorise Qupa Microfinance to compensate Bancozim to the
        value of loan amount as stated on the application form directly to them in exchange for my yet to be
        delivered ordered product/item. I do hereby acknowledge that once I have appended by signature
        on the loan application form and submitted all my KYC documents, then the process will then be
        deemed to be irrevocably and therefore cannot be cancelled. I confirm that the delivery of the
        product will be done to my employer's address as stated above free of charge and delivery to any
        other address other than this will be done as at Bancozim's management sole discretion and may
        require an extra transportation charge depending on the distance involved.
    </div>

    <!-- Penalty Declaration Box -->
    <div style="border: 2px solid #000; padding: 4px; margin-bottom: 6px; font-size: 9pt; font-weight: bold; text-align: center;">
        I declare that if I initiate cancellation of this order after signing this form, I will be liable to pay a penalty fee of 10% of the total order value.
    </div>

    <!-- Signature Section -->
    <table style="width: 100%; margin-top: 10px; font-size: 8pt;">
        <tr>
            <td style="width: 60px;">Full Name:</td>
            <td style="width: 200px; border-bottom: 1px solid #000; height: 15px; padding: 1px;">
                {{ isset($formResponses['declaration']['fullName']) ? $formResponses['declaration']['fullName'] : $formResponses['firstName'] . ' ' . $formResponses['surname'] }}
            </td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 150px; border-bottom: 1px solid #000; height: 15px; padding: 1px;">
                @if(isset($documents['signature']) && $documents['signature'])
                    <img src="{{ $documents['signature'] }}" alt="Signature" style="max-height: 15px; max-width: 150px;">
                @endif
            </td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border-bottom: 1px solid #000; height: 15px; padding: 1px;">
                {{ isset($formResponses['declaration']['date']) ? $formResponses['declaration']['date'] : date('Y-m-d') }}
            </td>
        </tr>
    </table>

    <!-- Witness Section -->
    <div style="margin-top: 10px; font-size: 8pt;">
        <strong>Witness</strong>
    </div>

    <table style="width: 100%; margin-top: 2px; font-size: 8pt;">
        <tr>
            <td style="width: 60px;">Full Name:</td>
            <td style="width: 200px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
            <td style="width: 60px;">Signature:</td>
            <td style="width: 150px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
            <td style="width: 40px;">Date:</td>
            <td style="width: 100px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
        </tr>
    </table>

    <!-- For Official Use Section -->
    <div style="margin-top: 20px;">
        <div style="font-weight: bold; text-decoration: underline; margin-bottom: 4px;">FOR OFFICIAL USE ONLY</div>
        
        <table style="width: 100%; margin-top: 4px; font-size: 8pt;">
            <tr>
                <td style="width: 120px;">Processed by:</td>
                <td style="width: 200px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
                <td style="width: 60px;">Signature:</td>
                <td style="width: 150px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
                <td style="width: 40px;">Date:</td>
                <td style="width: 100px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
            </tr>
        </table>
        
        <table style="width: 100%; margin-top: 4px; font-size: 8pt;">
            <tr>
                <td style="width: 120px;">Approved by:</td>
                <td style="width: 200px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
                <td style="width: 60px;">Signature:</td>
                <td style="width: 150px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
                <td style="width: 40px;">Date:</td>
                <td style="width: 100px; border-bottom: 1px solid #000; height: 15px; padding: 1px;"></td>
            </tr>
        </table>
    </div>

    <!-- Stamp Section -->
    <div style="margin-top: 20px; text-align: right;">
        <div class="stamp-box">
            Bancozim Stamp:
        </div>
    </div>
</div>

<!-- PAGE 5 - Attachments -->
@include('forms.partials.pdf_attachments')

</body>
</html>
