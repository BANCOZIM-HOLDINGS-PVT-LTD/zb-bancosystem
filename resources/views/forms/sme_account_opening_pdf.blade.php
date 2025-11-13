<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SME Business Application Form</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.0;
            margin: 0;
            padding: 0;
            background: white;
            color: #000;
        }

        .page {
            page-break-after: always;
            padding: 3mm;
            min-height: 275mm;
            background: white;
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

        .logo-cell {
            width: 150px;
            text-align: left;
            vertical-align: top;
        }

        .logo-img {
            max-width: 120px;
            height: auto;
        }

        .address-cell {
            text-align: right;
            vertical-align: top;
            font-size: 8pt;
            line-height: 1.3;
        }

        /* Form title */
        .form-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 8px 0 5px 0;
            color: #333;
            text-align: center;
        }

        /* Top checkboxes */
        .top-checkboxes {
            margin-bottom: 8px;
        }

        .checkbox-group {
            display: inline-block;
            margin-right: 20px;
            font-size: 8pt;
        }

        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-left: 3px;
            vertical-align: middle;
        }
        
        .checkbox.checked {
            background-color: #000;
        }

        /* Section headers */
        .section-header {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            padding: 3px 5px;
            font-size: 9pt;
            margin: 5px 0 2px 0;
            text-transform: uppercase;
        }

        /* Form tables */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
            font-size: 8pt;
        }

        .form-table td {
            border: 1px solid #000;
            padding: 1px 3px;
            vertical-align: middle;
            height: 14px;
        }

        .form-table th {
            border: 1px solid #000;
            padding: 1px 3px;
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }

        /* Field labels */
        .field-label {
            font-weight: normal;
            white-space: nowrap;
        }

        /* Input lines */
        .input-line {
            border-bottom: 1px solid #000;
            min-height: 12px;
            display: inline-block;
            width: 200px;
        }

        .input-line-long {
            border-bottom: 1px solid #000;
            min-height: 12px;
            display: inline-block;
            width: 300px;
        }

        /* Checkbox inline */
        .checkbox-inline {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            margin: 0 2px;
            vertical-align: middle;
        }
        
        .checkbox-inline.checked {
            background-color: #000;
        }

        /* Text areas */
        .text-area {
            min-height: 25px;
            border: 1px solid #000;
            padding: 2px;
            background: white;
        }
    </style>
</head>
<body>

<!-- PAGE 1 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/images/qupa.png') }}" alt="Qupa Logo" class="logo-img">
            </td>
            <td class="address-cell">
                ZB Chambers, 2nd Floor, Corner 1st Street & George Silundika<br>
                Harare, Zimbabwe<br>
                Tel: +263 867 700 2005 Email: loans@qupa.co.zw
            </td>
        </tr>
    </table>

    <!-- Form Title -->
    <div class="form-title">SME BUSINESS APPLICATION FORM</div>

    <!-- Top Checkboxes -->
    <div class="top-checkboxes">
        <div class="checkbox-group">
            Company <span class="checkbox {{ $businessType === 'Company' ? 'checked' : '' }}"></span>
        </div>
        <div class="checkbox-group">
            PBC <span class="checkbox {{ $businessType === 'PBC' ? 'checked' : '' }}"></span>
        </div>
        <div class="checkbox-group">
            Informal body <span class="checkbox {{ $businessType === 'Informal body' ? 'checked' : '' }}"></span>
        </div>
        <div class="checkbox-group">
            Loan Type <span class="input-line" style="width: 150px;">{{ $loanType ?? '' }}</span>
        </div>
    </div>

    <!-- BUSINESS INFORMATION Section -->
    <div class="section-header">BUSINESS INFORMATION</div>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Registered Name:</td>
            <td style="width: 30%;">{{ $registeredName ?? '' }}</td>
            <td class="field-label" style="width: 20%;">Trading Name:</td>
            <td style="width: 30%;">{{ $tradingName ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Type of Business:</td>
            <td>{{ $typeOfBusiness ?? '' }}</td>
            <td class="field-label">Business Address:</td>
            <td>{{ $businessAddress ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Period at Current Business Location:</td>
            <td>{{ $periodAtLocation ?? '' }}</td>
            <td class="field-label">Amount of Initial Capital:</td>
            <td>${{ $initialCapital ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Incorporation Date:</td>
            <td>{{ $incorporationDate ?? '' }}</td>
            <td class="field-label">Certificate of Incorporation Number:</td>
            <td>{{ $incorporationNumber ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Contact Phone Number:</td>
            <td>{{ $contactPhone ?? '' }}</td>
            <td class="field-label">Email Address:</td>
            <td>{{ $businessEmail ?? '' }}</td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 15%;">Sources of Capital:</td>
            <td style="width: 15%;">Own Savings <span class="checkbox-inline {{ isset($capitalSources['ownSavings']) && $capitalSources['ownSavings'] ? 'checked' : '' }}"></span></td>
            <td style="width: 15%;">Family Gift <span class="checkbox-inline {{ isset($capitalSources['familyGift']) && $capitalSources['familyGift'] ? 'checked' : '' }}"></span></td>
            <td style="width: 15%;">Loan <span class="checkbox-inline {{ isset($capitalSources['loan']) && $capitalSources['loan'] ? 'checked' : '' }}"></span></td>
            <td style="width: 40%;">Other(specify) <span class="input-line" style="width: 120px;">{{ isset($capitalSources['otherSpecify']) ? $capitalSources['otherSpecify'] : '' }}</span></td>
        </tr>
        <tr>
            <td class="field-label">Who are your main customers?</td>
            <td>Individuals <span class="checkbox-inline {{ isset($customerBase['individuals']) && $customerBase['individuals'] ? 'checked' : '' }}"></span></td>
            <td>Other Businesses <span class="checkbox-inline {{ isset($customerBase['businesses']) && $customerBase['businesses'] ? 'checked' : '' }}"></span></td>
            <td colspan="2">Other(specify) <span class="input-line" style="width: 200px;">{{ isset($customerBase['otherSpecify']) ? $customerBase['otherSpecify'] : '' }}</span></td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Estimated Annual Sales:</td>
            <td style="width: 20%;">${{ $estimatedAnnualSales ?? '' }}</td>
            <td class="field-label" style="width: 15%;">Net Profit:</td>
            <td style="width: 15%;">${{ $netProfit ?? '' }}</td>
            <td class="field-label" style="width: 15%;">Total Liabilities:</td>
            <td style="width: 15%;">${{ $totalLiabilities ?? '' }}</td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Main Product/Services:</td>
            <td style="width: 30%; height: 25px; vertical-align: top;">{{ $mainProducts ?? '' }}</td>
            <td class="field-label" style="width: 20%;">Main Problems Faced by Business:</td>
            <td style="width: 30%; height: 25px; vertical-align: top;">{{ $mainProblems ?? '' }}</td>
        </tr>
    </table>

    <!-- OTHER BUSINESS INTERESTS Section -->
    <div class="section-header">OTHER BUSINESS INTERESTS</div>
    
    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 25%;">Name of Business:</td>
            <td style="width: 25%;">{{ $otherBusinessName ?? '' }}</td>
            <td class="field-label" style="width: 25%;">Address Of Business:</td>
            <td style="width: 25%;">{{ $otherBusinessAddress ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Number of Employees:</td>
            <td>Fulltime and Owner <span class="checkbox-inline {{ $employeeType === 'Fulltime and Owner' ? 'checked' : '' }}"></span></td>
            <td>Part time <span class="checkbox-inline {{ $employeeType === 'Part time' ? 'checked' : '' }}"></span></td>
            <td>Non-paid(family) <span class="checkbox-inline {{ $employeeType === 'Non-paid(family)' ? 'checked' : '' }}"></span></td>
        </tr>
        <tr>
            <td class="field-label">Where are your customers from?</td>
            <td>Neighbourhood <span class="checkbox-inline {{ $customerLocation === 'Neighbourhood' ? 'checked' : '' }}"></span></td>
            <td>This Town <span class="checkbox-inline {{ $customerLocation === 'This Town' ? 'checked' : '' }}"></span></td>
            <td>Other(specify) <span class="input-line" style="width: 100px;">{{ $customerLocation !== 'Neighbourhood' && $customerLocation !== 'This Town' ? $customerLocation : '' }}</span></td>
        </tr>
    </table>

    <!-- LOAN INFORMATION Section -->
    <div class="section-header">LOAN INFORMATION</div>
    
    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Loan Amount:</td>
            <td style="width: 30%;">${{ $loanAmount ?? '' }}</td>
            <td class="field-label" style="width: 20%;">Repayment Period:</td>
            <td style="width: 30%;">{{ $loanTenure ?? '' }} months</td>
        </tr>
    </table>

    <div style="margin: 5px 0; font-weight: bold;">Detail Budget Breakdown by Order of Priority</div>
    
    <table class="form-table">
        <tr>
            <th style="width: 70%;">ITEM</th>
            <th style="width: 30%;">COST</th>
        </tr>
        @if(isset($budgetItems) && is_array($budgetItems))
            @foreach($budgetItems as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}. {{ $item['item'] ?? '' }}</td>
                    <td>${{ $item['cost'] ?? '' }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>1.</td>
                <td></td>
            </tr>
            <tr>
                <td>2.</td>
                <td></td>
            </tr>
            <tr>
                <td>3.</td>
                <td></td>
            </tr>
        @endif
    </table>

    <!-- REFERENCES Section -->
    <div class="section-header">REFERENCES</div>
    
    <table class="form-table">
        <tr>
            <th style="width: 50%;">NAME</th>
            <th style="width: 50%;">PHONE NUMBER</th>
        </tr>
        @if(isset($references) && is_array($references))
            @foreach($references as $index => $reference)
                <tr>
                    <td>{{ $index + 1 }}. {{ $reference['name'] ?? '' }}</td>
                    <td>{{ $reference['phoneNumber'] ?? '' }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>1.</td>
                <td></td>
            </tr>
            <tr>
                <td>2.</td>
                <td></td>
            </tr>
            <tr>
                <td>3.</td>
                <td></td>
            </tr>
        @endif
    </table>

    <!-- SECURITY (ASSETS PLEDGED) Section -->
    <div class="section-header">SECURITY (ASSETS PLEDGED)</div>
    
    <table class="form-table">
        <tr>
            <th style="width: 40%;">DESCRIPTION</th>
            <th style="width: 30%;">SERIAL/REG NUMBER</th>
            <th style="width: 30%;">ESTIMATED ASSET VALUE</th>
        </tr>
        @if(isset($securityAssets) && is_array($securityAssets))
            @foreach($securityAssets as $index => $asset)
                <tr>
                    <td>{{ $index + 1 }}. {{ $asset['description'] ?? '' }}</td>
                    <td>{{ $asset['serialNumber'] ?? '' }}</td>
                    <td>${{ $asset['estimatedValue'] ?? '' }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>1.</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>2.</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>3.</td>
                <td></td>
                <td></td>
            </tr>
        @endif
    </table>
</div>
</body>
</html><
!-- PAGE 2 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/images/qupa.png') }}" alt="Qupa Logo" class="logo-img">
            </td>
            <td class="address-cell">
                ZB Chambers, 2nd Floor, Corner 1st Street & George Silundika<br>
                Harare, Zimbabwe<br>
                Tel: +263 867 700 2005 Email: loans@qupa.co.zw
            </td>
        </tr>
    </table>

    <!-- DIRECTORS' PERSONAL DETAILS Section -->
    <div class="section-header">DIRECTORS' PERSONAL DETAILS</div>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 15%;">Title (Mr./Mrs./Dr/Prof):</td>
            <td style="width: 18%;">{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['title'] : '' }}</td>
            <td class="field-label" style="width: 15%;">First Name:</td>
            <td style="width: 18%;">{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['firstName'] : '' }}</td>
            <td class="field-label" style="width: 15%;">Surname:</td>
            <td style="width: 19%;">{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['surname'] : '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Maiden Name:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['maidenName'] : '' }}</td>
            <td class="field-label">Gender:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['gender'] : '' }}</td>
            <td class="field-label">Date Of Birth:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['dateOfBirth'] : '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Marital Status:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['maritalStatus'] : '' }}</td>
            <td class="field-label">Nationality:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['nationality'] : '' }}</td>
            <td class="field-label">ID Number:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['idNumber'] : '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Cell Number:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['cellNumber'] : '' }}</td>
            <td class="field-label">WhatsApp:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['whatsApp'] : '' }}</td>
            <td class="field-label">Highest Educational Qualification:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['highestEducation'] : '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Citizenship:</td>
            <td>{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['citizenship'] : '' }}</td>
            <td class="field-label" colspan="2">Email Address:</td>
            <td colspan="2">{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['emailAddress'] : '' }}</td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Residential Address:</td>
            <td style="width: 30%; height: 35px; vertical-align: top;">{{ isset($directorsPersonalDetails) ? $directorsPersonalDetails['residentialAddress'] : '' }}</td>
            <td class="field-label" style="width: 20%;">Passport Photo:</td>
            <td style="width: 30%; height: 35px; vertical-align: top;">
                @if(isset($directorsPersonalDetails) && isset($directorsPersonalDetails['passportPhoto']) && !empty($directorsPersonalDetails['passportPhoto']))
                    <img src="{{ $directorsPersonalDetails['passportPhoto'] }}" style="max-width: 100px; max-height: 35px;" alt="Passport Photo">
                @endif
            </td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Period at Current Address:</td>
            <td class="field-label" style="width: 10%;">Years:</td>
            <td style="width: 15%;">{{ isset($directorsPersonalDetails) && isset($directorsPersonalDetails['periodAtCurrentAddress']) ? $directorsPersonalDetails['periodAtCurrentAddress']['years'] : '' }}</td>
            <td class="field-label" style="width: 10%;">Months:</td>
            <td style="width: 15%;">{{ isset($directorsPersonalDetails) && isset($directorsPersonalDetails['periodAtCurrentAddress']) ? $directorsPersonalDetails['periodAtCurrentAddress']['months'] : '' }}</td>
            <td style="width: 30%;"></td>
        </tr>
        <tr>
            <td class="field-label">Period at Previous Address:</td>
            <td class="field-label">Years:</td>
            <td>{{ isset($directorsPersonalDetails) && isset($directorsPersonalDetails['periodAtPreviousAddress']) ? $directorsPersonalDetails['periodAtPreviousAddress']['years'] : '' }}</td>
            <td class="field-label">Months:</td>
            <td>{{ isset($directorsPersonalDetails) && isset($directorsPersonalDetails['periodAtPreviousAddress']) ? $directorsPersonalDetails['periodAtPreviousAddress']['months'] : '' }}</td>
            <td></td>
        </tr>
    </table>

    <!-- SPOUSE AND NEXT OF KIN DETAILS Section -->
    <div class="section-header">SPOUSE AND NEXT OF KIN DETAILS</div>

    <table class="form-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 5px;">SPOUSE DETAILS</div>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 2px; width: 30%;">Full Name:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[0]) ? $spouseDetails[0]['fullName'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Phone Number:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[0]) ? $spouseDetails[0]['phoneNumber'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Email Address:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[0]) ? $spouseDetails[0]['emailAddress'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px; vertical-align: top;">Address:</td>
                        <td style="border: 1px solid #000; height: 25px; vertical-align: top;">{{ isset($spouseDetails) && isset($spouseDetails[0]) ? $spouseDetails[0]['residentialAddress'] : '' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 5px;">NEXT OF KIN 1</div>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 2px; width: 30%;">Full Name:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[1]) ? $spouseDetails[1]['fullName'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Relationship:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[1]) ? $spouseDetails[1]['relationship'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Phone Number:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[1]) ? $spouseDetails[1]['phoneNumber'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Email Address:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[1]) ? $spouseDetails[1]['emailAddress'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px; vertical-align: top;">Address:</td>
                        <td style="border: 1px solid #000; height: 25px; vertical-align: top;">{{ isset($spouseDetails) && isset($spouseDetails[1]) ? $spouseDetails[1]['residentialAddress'] : '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="form-table">
        <tr>
            <td style="width: 100%; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 5px;">NEXT OF KIN 2</div>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 2px; width: 15%;">Full Name:</td>
                        <td style="border: 1px solid #000; height: 15px; width: 35%;">{{ isset($spouseDetails) && isset($spouseDetails[2]) ? $spouseDetails[2]['fullName'] : '' }}</td>
                        <td style="border: none; padding: 2px; width: 15%;">Relationship:</td>
                        <td style="border: 1px solid #000; height: 15px; width: 35%;">{{ isset($spouseDetails) && isset($spouseDetails[2]) ? $spouseDetails[2]['relationship'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Phone Number:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[2]) ? $spouseDetails[2]['phoneNumber'] : '' }}</td>
                        <td style="border: none; padding: 2px;">Email Address:</td>
                        <td style="border: 1px solid #000; height: 15px;">{{ isset($spouseDetails) && isset($spouseDetails[2]) ? $spouseDetails[2]['emailAddress'] : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px; vertical-align: top;">Address:</td>
                        <td style="border: 1px solid #000; height: 25px; vertical-align: top;" colspan="3">{{ isset($spouseDetails) && isset($spouseDetails[2]) ? $spouseDetails[2]['residentialAddress'] : '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- EMPLOYMENT DETAILS Section -->
    <div class="section-header">EMPLOYMENT DETAILS</div>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 25%;">Business/Employer's Name:</td>
            <td style="width: 25%;">{{ $employerName ?? '' }}</td>
            <td class="field-label" style="width: 25%;">Job Title:</td>
            <td style="width: 25%;">{{ $jobTitle ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Business/Employer's Address:</td>
            <td>{{ $employerAddress ?? '' }}</td>
            <td class="field-label">Date of Employment:</td>
            <td>{{ $dateOfEmployment ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">Name of Immediate Manager:</td>
            <td>{{ $headOfInstitution ?? '' }}</td>
            <td class="field-label">Phone Number of Immediate Manager:</td>
            <td>{{ $headOfInstitutionCell ?? '' }}</td>
        </tr>
    </table>

    <!-- PROPERTY OWNERSHIP Section -->
    <div class="section-header">PROPERTY OWNERSHIP</div>

    <table class="form-table">
        <tr>
            <td style="width: 16%;">Rented: <span class="checkbox-inline {{ $propertyOwnership === 'Rented' ? 'checked' : '' }}"></span></td>
            <td style="width: 16%;">Employer Owned: <span class="checkbox-inline {{ $propertyOwnership === 'Employer Owned' ? 'checked' : '' }}"></span></td>
            <td style="width: 16%;">Mortgaged: <span class="checkbox-inline {{ $propertyOwnership === 'Mortgaged' ? 'checked' : '' }}"></span></td>
            <td style="width: 26%;">Owned Without Mortgage: <span class="checkbox-inline {{ $propertyOwnership === 'Owned Without Mortgage' ? 'checked' : '' }}"></span></td>
            <td style="width: 26%;">Parents owned: <span class="checkbox-inline {{ $propertyOwnership === 'Parents Owned' ? 'checked' : '' }}"></span></td>
        </tr>
    </table>
</div>

<!-- PAGE 3 -->
<div class="page">
    <!-- BANKING/MOBILE ACCOUNT DETAILS Section -->
    <div class="section-header">BANKING/MOBILE ACCOUNT DETAILS</div>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 33%; text-align: center; font-weight: bold;">BANK</td>
            <td class="field-label" style="width: 33%; text-align: center; font-weight: bold;">BRANCH</td>
            <td class="field-label" style="width: 34%; text-align: center; font-weight: bold;">ACCOUNT NUMBER</td>
        </tr>
        <tr>
            <td style="height: 18px;">{{ $bankName ?? '' }}</td>
            <td style="height: 18px;">{{ $branch ?? '' }}</td>
            <td style="height: 18px;">{{ $accountNumber ?? '' }}</td>
        </tr>
        <tr>
            <td style="height: 18px;"></td>
            <td style="height: 18px;"></td>
            <td style="height: 18px;"></td>
        </tr>
    </table>

    <!-- LOANS WITH OTHER INSTITUTIONS Section -->
    <div class="section-header">LOANS WITH OTHER INSTITUTIONS (ALSO INCLUDE QUPA LOAN)</div>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 25%; text-align: center; font-weight: bold;">INSTITUTION</td>
            <td class="field-label" style="width: 25%; text-align: center; font-weight: bold;">MONTHLY INSTALLMENT</td>
            <td class="field-label" style="width: 25%; text-align: center; font-weight: bold;">CURRENT LOAN BALANCE</td>
            <td class="field-label" style="width: 25%; text-align: center; font-weight: bold;">MATURITY DATE</td>
        </tr>
        @if(isset($otherLoans) && is_array($otherLoans))
            @foreach($otherLoans as $loan)
                <tr>
                    <td style="height: 18px;">{{ $loan['institution'] ?? '' }}</td>
                    <td style="height: 18px;">${{ $loan['monthlyInstallment'] ?? $loan['repayment'] ?? '' }}</td>
                    <td style="height: 18px;">${{ $loan['currentBalance'] ?? '' }}</td>
                    <td style="height: 18px;">{{ $loan['maturityDate'] ?? '' }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
            </tr>
            <tr>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
            </tr>
            <tr>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
                <td style="height: 18px;"></td>
            </tr>
        @endif
    </table>

    <!-- DECLARATION Section -->
    <div class="section-header">DECLARATION</div>

    <div style="font-size: 8px; line-height: 1.1; margin-bottom: 8px; text-align: justify;">
        We declare that the information given above is accurate and correct. We are aware that falsifying information automatically leads to decline of our
        loan application. We authorise Qupa Microfinance to obtain and use the information obtained for the purposes of this application and for the purposes of
        credit bureau. We authorise Qupa microfinance to references from friends, relatives, neighbours and business partners including visits to our homes
        and verification of my assets. We have read and fully understood the above together with all the conditions, and We agree to be bound by Qupa
        Micro-Finance terms and conditions.
    </div>

    <!-- DIRECTORS SIGNATURE Section -->
    <div class="section-header">DIRECTORS SIGNATURE</div>

    <table class="form-table">
        @if(isset($directorsSignatures) && is_array($directorsSignatures))
            @foreach($directorsSignatures as $index => $signature)
                <tr>
                    <td class="field-label" style="width: 15%;">Director:</td>
                    <td class="field-label" style="width: 10%;">Name:</td>
                    <td style="width: 20%;">{{ $signature['name'] ?? '' }}</td>
                    <td class="field-label" style="width: 15%;">Signature:</td>
                    <td style="width: 20%;">
                        @if(isset($signature['signature']) && !empty($signature['signature']))
                            <img src="{{ $signature['signature'] }}" style="max-width: 100px; max-height: 20px;" alt="Signature">
                        @endif
                    </td>
                    <td class="field-label" style="width: 10%;">Date:</td>
                    <td style="width: 10%;">{{ $signature['date'] ?? '' }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="field-label" style="width: 15%;">Director:</td>
                <td class="field-label" style="width: 10%;">Name:</td>
                <td style="width: 20%;"></td>
                <td class="field-label" style="width: 15%;">Signature:</td>
                <td style="width: 20%;"></td>
                <td class="field-label" style="width: 10%;">Date:</td>
                <td style="width: 10%;"></td>
            </tr>
            <tr>
                <td class="field-label">Director:</td>
                <td class="field-label">Name:</td>
                <td></td>
                <td class="field-label">Signature:</td>
                <td></td>
                <td class="field-label">Date:</td>
                <td></td>
            </tr>
            <tr>
                <td class="field-label">Director:</td>
                <td class="field-label">Name:</td>
                <td></td>
                <td class="field-label">Signature:</td>
                <td></td>
                <td class="field-label">Date:</td>
                <td></td>
            </tr>
        @endif
    </table>

    <!-- FOR OFFICIAL USE ONLY Section -->
    <div class="section-header">FOR OFFICIAL USE ONLY</div>

    <table class="form-table">
        <tr>
            <td class="field-label" style="width: 20%;">Received & Checked by:</td>
            <td class="field-label" style="width: 10%;">Name:</td>
            <td style="width: 25%;"></td>
            <td class="field-label" style="width: 15%;">Signature:</td>
            <td style="width: 15%;"></td>
            <td class="field-label" style="width: 10%;">Date:</td>
            <td style="width: 5%;"></td>
        </tr>
        <tr>
            <td class="field-label">Approved by:</td>
            <td class="field-label">Name:</td>
            <td></td>
            <td class="field-label">Signature:</td>
            <td></td>
            <td class="field-label">Date:</td>
            <td></td>
        </tr>
    </table>

    <div style="margin-top: 15px;">
        <div style="font-weight: bold; margin-bottom: 5px;">KYC CHECKLIST</div>

        <table style="width: 100%; border: none; font-size: 8px;">
            <tr>
                <td style="border: none; width: 50%; vertical-align: top;">
                    <div style="margin-bottom: 2px;">Copy of ID, License, Valid Passport <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['copyOfId']) && $kycDocuments['copyOfId'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Articles of association/PBC <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['articlesOfAssociation']) && $kycDocuments['articlesOfAssociation'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Stamped 3 months' Bank Statement <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['bankStatement']) && $kycDocuments['bankStatement'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Group Constitution <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['groupConstitution']) && $kycDocuments['groupConstitution'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Proof of Residence/Confirmation Letter <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['proofOfResidence']) && $kycDocuments['proofOfResidence'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Financial Statement <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['financialStatement']) && $kycDocuments['financialStatement'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Certificate of Incorporation <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['certificateOfIncorporation']) && $kycDocuments['certificateOfIncorporation'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Ecocash Statements where applicable <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['ecocashStatements']) && $kycDocuments['ecocashStatements'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px;">Resolution to borrow <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['resolutionToBorrow']) && $kycDocuments['resolutionToBorrow'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                </td>
                <td style="border: none; width: 50%; vertical-align: top;">
                    <div style="margin-bottom: 2px;">Company documents:</div>
                    <div style="margin-bottom: 2px; margin-left: 15px;">CR11 <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['cr11']) && $kycDocuments['cr11'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px; margin-left: 15px;">CR6 <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['cr6']) && $kycDocuments['cr6'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px; margin-left: 15px;">CR5 <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['cr5']) && $kycDocuments['cr5'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>
                    <div style="margin-bottom: 2px; margin-left: 15px;">MOA <span class="checkbox-inline {{ isset($kycDocuments) && isset($kycDocuments['moa']) && $kycDocuments['moa'] ? 'checked' : '' }}" style="margin-left: 5px;"></span></div>

                    <div style="margin-top: 20px; text-align: right;">
                        <div style="border: 1px solid #000; width: 100px; height: 40px; margin-left: auto; position: relative;">
                            <div style="position: absolute; bottom: -15px; right: 0; font-size: 8px;">Qupa Date Stamp</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    @if(isset($hasDocuments) && $hasDocuments)
    <div style="margin-top: 30px; page-break-before: always;">
        <h3 style="text-align: center; margin-bottom: 20px;">Uploaded Documents</h3>
        
        @if(isset($documents['uploadedDocuments']) && count($documents['uploadedDocuments']) > 0)
            @foreach($documents['uploadedDocuments'] as $docType => $files)
                @if(count($files) > 0)
                    <div style="margin-bottom: 20px;">
                        <h4>{{ ucwords(str_replace('_', ' ', $docType)) }}</h4>
                        <ul>
                            @foreach($files as $file)
                                <li>{{ $file['name'] ?? 'Document' }} ({{ number_format(($file['size'] ?? 0) / 1024 / 1024, 2) }} MB)</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @endif
        
        @if(isset($documents['uploadedAt']))
            <p style="margin-top: 20px; font-size: 8pt; color: #666;">
                Documents uploaded on: {{ date('d/m/Y H:i', strtotime($documents['uploadedAt'])) }}
            </p>
        @endif
    </div>
    @endif
</div>

<!-- Attachments Page -->
@include('forms.partials.pdf_attachments')

</body>
</html>