<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZB Account Opening Form</title>
    <style>
        @page {
            margin: 8mm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 6pt;
            line-height: 1.0;
            margin: 0;
            padding: 0;
        }
        
        .page {
            page-break-after: always;
            position: relative;
            height: 280mm;
        }
        
        .page:last-child {
            page-break-after: avoid;
        }
        
        /* Header styles */
        .header-table {
            width: 100%;
            margin-bottom: 2px;
        }
        
        .logo-img {
            height: 30px;
        }
        
        .tagline {
            text-align: right;
            font-size: 5pt;
            letter-spacing: 0.5px;
        }
        
        /* Form title */
        .form-title {
            font-size: 10pt;
            font-weight: bold;
            border-bottom: 2px solid #000;
            padding-bottom: 1px;
            margin-bottom: 2px;
        }
        
        .subtitle {
            font-size: 5pt;
            font-style: italic;
            margin-bottom: 3px;
        }
        
        /* Section headers */
        .section-header {
            background-color: #8BC34A;
            color: white;
            font-weight: bold;
            padding: 2px 4px;
            font-size: 6pt;
            margin: 3px 0 1px 0;
        }
        
        /* Main form table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 5pt;
            margin-bottom: 1px;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
            height: 10px;
        }
        
        .label {
            background-color: #f5f5f5;
            font-weight: normal;
        }
        
        .input {
            background-color: white;
        }
        
        /* Checkbox */
        .checkbox {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            margin: 0 1px;
            vertical-align: middle;
        }
        
        .checkbox-checked {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            margin: 0 1px;
            vertical-align: middle;
            background-color: #000;
        }
        
        /* Small input boxes for dates/numbers */
        .box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin: 0 0.5px;
            text-align: center;
            vertical-align: middle;
            font-size: 5pt;
        }
        
        /* Page footer */
        .page-footer {
            position: absolute;
            bottom: 3mm;
            right: 8mm;
            font-size: 5pt;
            font-weight: bold;
        }
        
        /* Terms text */
        .terms-text {
            font-size: 4pt;
            line-height: 1.1;
            text-align: justify;
        }
        
        .terms-columns {
            column-count: 2;
            column-gap: 8px;
        }
        
        /* Compact spacing */
        .compact {
            margin: 0;
            padding: 0;
        }
        
        .tight-row {
            height: 8px;
        }
    </style>
</head>
<body>

<!-- PAGE 1 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo-img">
            </td>
            <td style="width: 60%;"></td>
            <td style="width: 20%;" class="tagline">
                BANKING | INVESTMENTS | INSURANCE
            </td>
        </tr>
    </table>
    
    <div class="form-title">Individual Customer Account Opening Application Form</div>
    <div class="subtitle">(Please complete in black or blue pen with clear CAPITAL LETTERS print)</div>
    
    <!-- Account Number and Service Centre -->
    <table class="main-table">
        <tr>
            <td class="label" style="width: 15%;">Account Number:<br/><span style="font-size: 4pt;">(For Official Use Only)</span></td>
            <td class="input" style="width: 35%; text-align: center;">
                <span class="box">4</span><span class="box"></span><span class="box"></span>—<span class="box"></span><span class="box"></span><span class="box"></span>—<span class="box"></span><span class="box"></span><span class="box"></span><span class="box"></span><span class="box"></span>
            </td>
            <td class="label" style="width: 20%;">Service Centre for Card Collection:</td>
            <td class="input" style="width: 30%;">{{ $formResponses['serviceCenter'] ?? '' }}</td>
        </tr>
    </table>    
    <!
-- Section A -->
    <div class="section-header">A - ACCOUNT SPECIFICATIONS <span style="font-size: 4pt; font-style: italic;">(Please mark (X) the appropriate boxes)</span></div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">CURRENCY OF ACCOUNT:</td>
            <td class="input" style="width: 10%;">ZWL$ 
                @if(isset($formResponses['accountCurrency']) && $formResponses['accountCurrency'] == 'ZWL$')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 10%;">USD 
                @if(isset($formResponses['accountCurrency']) && $formResponses['accountCurrency'] == 'USD')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 10%;">ZAR 
                @if(isset($formResponses['accountCurrency']) && $formResponses['accountCurrency'] == 'ZAR')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 10%;">BWP 
                @if(isset($formResponses['accountCurrency']) && $formResponses['accountCurrency'] == 'BWP')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 10%;">EURO 
                @if(isset($formResponses['accountCurrency']) && $formResponses['accountCurrency'] == 'EURO')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 15%;">OTHER <span style="font-style: italic;">(Indicate)</span> 
                @if(isset($formResponses['accountCurrency']) && !in_array($formResponses['accountCurrency'], ['ZWL$', 'USD', 'ZAR', 'BWP', 'EURO']))
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 15%;">
                @if(isset($formResponses['accountCurrency']) && !in_array($formResponses['accountCurrency'], ['ZWL$', 'USD', 'ZAR', 'BWP', 'EURO']))
                    {{ $formResponses['accountCurrency'] }}
                @endif
            </td>
        </tr>
    </table>
    
    <!-- Section B -->
    <div class="section-header">B - CUSTOMER PERSONAL DETAILS</div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 5%;">Title:</td>
            <td class="input" style="width: 5%;">Mr 
                @if(isset($formResponses['title']) && $formResponses['title'] == 'Mr')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Mrs 
                @if(isset($formResponses['title']) && $formResponses['title'] == 'Mrs')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Ms 
                @if(isset($formResponses['title']) && $formResponses['title'] == 'Ms')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Dr 
                @if(isset($formResponses['title']) && $formResponses['title'] == 'Dr')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Prof 
                @if(isset($formResponses['title']) && $formResponses['title'] == 'Prof')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label" style="width: 10%;">First Name:</td>
            <td class="input" style="width: 25%;">{{ $formResponses['firstName'] ?? '' }}</td>
            <td class="label" style="width: 10%;">Surname:</td>
            <td class="input" style="width: 25%;">{{ $formResponses['surname'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Maiden Name:</td>
            <td class="input" colspan="3">{{ $formResponses['maidenName'] ?? '' }}</td>
            <td class="label" colspan="2">Other Name(s):</td>
            <td class="input" colspan="2">{{ $formResponses['otherNames'] ?? '' }}</td>
            <td class="label">Gender:</td>
            <td class="input">Male 
                @if(isset($formResponses['gender']) && $formResponses['gender'] == 'Male')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
                Female 
                @if(isset($formResponses['gender']) && $formResponses['gender'] == 'Female')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Date of Birth:</td>
            <td class="input" colspan="2">{{ $formResponses['dateOfBirth'] ?? '' }}</td>
            <td class="label" colspan="2">Place of Birth:</td>
            <td class="input" colspan="2">{{ $formResponses['placeOfBirth'] ?? '' }}</td>
            <td class="label">Nationality:</td>
            <td class="input" colspan="2">{{ $formResponses['nationality'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label" colspan="2">Marital Status: <span style="font-style: italic; font-size: 4pt;">(mark applicable)</span></td>
            <td class="input">Single 
                @if(isset($formResponses['maritalStatus']) && $formResponses['maritalStatus'] == 'Single')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Married 
                @if(isset($formResponses['maritalStatus']) && $formResponses['maritalStatus'] == 'Married')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Other 
                @if(isset($formResponses['maritalStatus']) && !in_array($formResponses['maritalStatus'], ['Single', 'Married']))
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">Citizenship:</td>
            <td class="input" colspan="2">{{ $formResponses['citizenship'] ?? '' }}</td>
            <td class="label">Dependents:</td>
            <td class="input">{{ $formResponses['dependents'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label" colspan="2">National ID Number: <span style="font-style: italic; font-size: 4pt;">(mandatory)</span></td>
            <td class="input" colspan="3">{{ $formResponses['nationalIdNumber'] ?? '' }}</td>
            <td class="label" colspan="2">Driver's License No:</td>
            <td class="input" colspan="3">{{ $formResponses['driversLicense'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label" colspan="2">Passport Number: <span style="font-style: italic; font-size: 4pt;">(if applicable)</span></td>
            <td class="input" colspan="3">{{ $formResponses['passportNumber'] ?? '' }}</td>
            <td class="label">Expiry Date:</td>
            <td class="input" colspan="4">{{ $formResponses['passportExpiry'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label" colspan="2">Country of Residence:</td>
            <td class="input" colspan="2">{{ $formResponses['countryOfResidence'] ?? '' }}</td>
            <td class="label" colspan="2">Highest Educational Qualification:</td>
            <td class="input" colspan="2">{{ $formResponses['highestEducation'] ?? '' }}</td>
            <td class="label">Hobbies:</td>
            <td class="input">{{ $formResponses['hobbies'] ?? '' }}</td>
        </tr>
    </table>  
  
    <!-- Section C -->
    <div class="section-header">C - CUSTOMER CONTACT DETAILS</div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">Residential Address:</td>
            <td class="input" colspan="9">{{ $formResponses['residentialAddress'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Telephone:</td>
            <td class="label" style="width: 5%;">Res:</td>
            <td class="input" style="width: 20%;">{{ $formResponses['telephoneRes'] ?? '' }}</td>
            <td class="label" style="width: 15%;">Mobile: +263-</td>
            <td class="input" style="width: 20%;">{{ $formResponses['mobile'] ?? '' }}</td>
            <td class="label" style="width: 5%;">Bus:</td>
            <td class="input" style="width: 15%;">{{ $formResponses['bus'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Email Address:</td>
            <td class="input" colspan="6">{{ $formResponses['emailAddress'] ?? '' }}</td>
        </tr>
    </table>
    
    <!-- Section D -->
    <div class="section-header">D - CUSTOMER EMPLOYMENT DETAILS</div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 15%;">Employer Name:</td>
            <td class="input" style="width: 35%;">{{ $formResponses['employerName'] ?? '' }}</td>
            <td class="label" style="width: 15%;">Occupation:</td>
            <td class="input" style="width: 35%;">{{ $formResponses['occupation'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label" colspan="2">Employment Status: <span style="font-style: italic; font-size: 4pt;">(mark applicable)</span></td>
            <td class="input">Permanent 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Permanent')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Contract 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Contract')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Pensioner 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Pensioner')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Unemployed 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Unemployed')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" colspan="2">Self-Employed 
                @if(isset($formResponses['employmentStatus']) && $formResponses['employmentStatus'] == 'Self-Employed')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label" colspan="2">Business Description: <span style="font-style: italic; font-size: 4pt;">(state, if self-employed)</span></td>
            <td class="input" colspan="6">{{ $formResponses['businessDescription'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label" colspan="2">Employer Type: <span style="font-style: italic; font-size: 4pt;">(mark applicable)</span></td>
            <td class="input">Government 
                @if(isset($formResponses['employerType']) && (
                    $formResponses['employerType'] == 'Government' || 
                    (is_array($formResponses['employerType']) && isset($formResponses['employerType']['government']) && $formResponses['employerType']['government'])
                ))
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Local Company 
                @if(isset($formResponses['employerType']) && (
                    $formResponses['employerType'] == 'Local Company' || 
                    (is_array($formResponses['employerType']) && isset($formResponses['employerType']['localCompany']) && $formResponses['employerType']['localCompany'])
                ))
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Multinational 
                @if(isset($formResponses['employerType']) && (
                    $formResponses['employerType'] == 'Multinational' || 
                    (is_array($formResponses['employerType']) && isset($formResponses['employerType']['multinational']) && $formResponses['employerType']['multinational'])
                ))
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">NGO 
                @if(isset($formResponses['employerType']) && (
                    $formResponses['employerType'] == 'NGO' || 
                    (is_array($formResponses['employerType']) && isset($formResponses['employerType']['ngo']) && $formResponses['employerType']['ngo'])
                ))
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" colspan="2">Other (specify): 
                @if(isset($formResponses['employerType']) && (
                    (!in_array($formResponses['employerType'], ['Government', 'Local Company', 'Multinational', 'NGO']) && !is_array($formResponses['employerType'])) || 
                    (is_array($formResponses['employerType']) && isset($formResponses['employerType']['other']) && $formResponses['employerType']['other'])
                ))
                    @if(is_array($formResponses['employerType']) && isset($formResponses['employerType']['otherSpecify']))
                        {{ $formResponses['employerType']['otherSpecify'] }}
                    @elseif(!is_array($formResponses['employerType']))
                        {{ $formResponses['employerType'] }}
                    @endif
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Employer Physical Address:</td>
            <td class="input" colspan="3">{{ $formResponses['employerAddress'] ?? '' }}</td>
            <td class="label" colspan="2">Employer Contact Number:</td>
            <td class="input" colspan="2">{{ $formResponses['employerContact'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Gross Monthly Salary:</td>
            <td class="input">${{ $formResponses['grossMonthlySalary'] ?? '' }}</td>
            <td class="label" colspan="2">Other Source(s) of Income:</td>
            <td class="input" colspan="4">{{ $formResponses['otherIncome'] ?? '' }}</td>
        </tr>
    </table>
    
    <!-- Section E -->
    <div class="section-header">E - SPOUSE/NEXT OF KIN</div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 5%;">Title:</td>
            <td class="input" style="width: 5%;">Mr 
                @if(isset($formResponses['spouseTitle']) && $formResponses['spouseTitle'] == 'Mr')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Mrs 
                @if(isset($formResponses['spouseTitle']) && $formResponses['spouseTitle'] == 'Mrs')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Ms 
                @if(isset($formResponses['spouseTitle']) && $formResponses['spouseTitle'] == 'Ms')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Dr 
                @if(isset($formResponses['spouseTitle']) && $formResponses['spouseTitle'] == 'Dr')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 5%;">Prof 
                @if(isset($formResponses['spouseTitle']) && $formResponses['spouseTitle'] == 'Prof')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label" style="width: 10%;">Full Name:</td>
            <td class="input" style="width: 60%;">
                @if(isset($formResponses['spouseDetails']) && isset($formResponses['spouseDetails'][0]))
                    {{ $formResponses['spouseDetails'][0]['fullName'] ?? '' }}
                @else
                    {{ $formResponses['spouseFirstName'] ?? '' }} {{ $formResponses['spouseSurname'] ?? '' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Residential Address:</td>
            <td class="input" colspan="7">
                @if(isset($formResponses['spouseDetails']) && isset($formResponses['spouseDetails'][0]))
                    {{ $formResponses['spouseDetails'][0]['residentialAddress'] ?? '' }}
                @else
                    {{ $formResponses['spouseAddress'] ?? '' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">National ID No:</td>
            <td class="input" colspan="2">{{ $formResponses['spouseIdNumber'] ?? '' }}</td>
            <td class="label">Contact Number:</td>
            <td class="input" colspan="2">
                @if(isset($formResponses['spouseDetails']) && isset($formResponses['spouseDetails'][0]))
                    {{ $formResponses['spouseDetails'][0]['phoneNumber'] ?? '' }}
                @else
                    {{ $formResponses['spouseContact'] ?? '' }}
                @endif
            </td>
            <td class="label">Nature of relationship:</td>
            <td class="input">
                @if(isset($formResponses['spouseDetails']) && isset($formResponses['spouseDetails'][0]))
                    {{ $formResponses['spouseDetails'][0]['relationship'] ?? '' }}
                @else
                    {{ $formResponses['spouseRelationship'] ?? '' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label" colspan="2">Gender: <span style="font-style: italic; font-size: 4pt;">(mark applicable)</span></td>
            <td class="input">Male 
                @if(isset($formResponses['spouseGender']) && $formResponses['spouseGender'] == 'Male')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">Female 
                @if(isset($formResponses['spouseGender']) && $formResponses['spouseGender'] == 'Female')
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">Email Address:</td>
            <td class="input" colspan="3">
                @if(isset($formResponses['spouseDetails']) && isset($formResponses['spouseDetails'][0]))
                    {{ $formResponses['spouseDetails'][0]['emailAddress'] ?? '' }}
                @else
                    {{ $formResponses['spouseEmail'] ?? '' }}
                @endif
            </td>
        </tr>
    </table>
    
    <!-- Section F -->
    <div class="section-header">F - OTHER SERVICES</div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 10%;">SMS Alerts:</td>
            <td class="input" style="width: 10%;">Yes 
                @if(isset($formResponses['smsAlerts']) && $formResponses['smsAlerts'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
                No 
                @if(isset($formResponses['smsAlerts']) && !$formResponses['smsAlerts'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label" style="width: 25%;"><span style="font-style: italic; font-size: 4pt;">(If yes, state your mobile number)</span></td>
            <td class="label" style="width: 15%;">Mobile Number:</td>
            <td class="input" style="width: 40%;">{{ $formResponses['smsNumber'] ?? $formResponses['mobile'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">E-Statements:</td>
            <td class="input">Yes 
                @if(isset($formResponses['eStatements']) && $formResponses['eStatements'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
                No 
                @if(isset($formResponses['eStatements']) && !$formResponses['eStatements'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label"><span style="font-style: italic; font-size: 4pt;">(If yes, state your email address)</span></td>
            <td class="label">Email address:</td>
            <td class="input">{{ $formResponses['eStatementsEmail'] ?? $formResponses['emailAddress'] ?? '' }}</td>
        </tr>
    </table>
    
    <!-- Section G -->
    <div class="section-header">G - DIGITAL BANKING SERVICES</div>
    <table class="main-table">
        <tr>
            <td class="label" style="width: 25%;">Mobile money e.g. Ecocash Services:</td>
            <td class="input" style="width: 8%;">Yes 
                @if(isset($formResponses['mobileMoneyEcocash']) && $formResponses['mobileMoneyEcocash'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input" style="width: 8%;">No 
                @if(isset($formResponses['mobileMoneyEcocash']) && !$formResponses['mobileMoneyEcocash'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label" style="width: 24%;"><span style="font-style: italic; font-size: 4pt;">(If yes, state your mobile number)</span></td>
            <td class="label" style="width: 15%;">Mobile Number:</td>
            <td class="input" style="width: 20%;">{{ $formResponses['mobileMoneyNumber'] ?? $formResponses['mobile'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">E-Wallet:</td>
            <td class="input">Yes 
                @if(isset($formResponses['eWallet']) && $formResponses['eWallet'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">No 
                @if(isset($formResponses['eWallet']) && !$formResponses['eWallet'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label"><span style="font-style: italic; font-size: 4pt;">(If yes, state your mobile number)</span></td>
            <td class="label">Mobile Number:</td>
            <td class="input">{{ $formResponses['eWalletNumber'] ?? $formResponses['mobile'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Whatsapp Banking:</td>
            <td class="input">Yes 
                @if(isset($formResponses['whatsappBanking']) && $formResponses['whatsappBanking'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="input">No 
                @if(isset($formResponses['whatsappBanking']) && !$formResponses['whatsappBanking'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label"><span style="font-style: italic; font-size: 4pt;">(Self registration or seek assistance)</span></td>
            <td class="label">Internet Banking:</td>
            <td class="input">Yes 
                @if(isset($formResponses['internetBanking']) && $formResponses['internetBanking'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
                No 
                @if(isset($formResponses['internetBanking']) && !$formResponses['internetBanking'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
                <span style="font-style: italic; font-size: 4pt;">(Self registration or seek assistance)</span>
            </td>
        </tr>
    </table>
    
    <div class="page-footer">Page 1 of 4</div>
</div>
<!--
 PAGE 2 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo-img">
            </td>
            <td style="width: 60%;"></td>
            <td style="width: 20%;" class="tagline">
                BANKING | INVESTMENTS | INSURANCE
            </td>
        </tr>
    </table>
    
    <!-- Section H -->
    <div class="section-header">H - ZB LIFE FUNERAL CASH COVER</div>
    <div style="font-size: 5pt; margin: 1px 0;">
        Details of dependents to be covered by this application is up to eight (8) dependents. <span style="font-style: italic;">Please tick (✓) the appropriate box to show supplementary benefits to be included.</span>
    </div>
    
    <!-- Dependents Table -->
    <table class="main-table">
        <tr style="background-color: #f0f0f0;">
            <td class="label" style="text-align: center; font-weight: bold; height: 12px;">Surname</td>
            <td class="label" style="text-align: center; font-weight: bold;">Forename(s)</td>
            <td class="label" style="text-align: center; font-weight: bold;">Relationship</td>
            <td class="label" style="text-align: center; font-weight: bold;">Date of Birth</td>
            <td class="label" style="text-align: center; font-weight: bold;">Birth Entry/ National ID No.</td>
            <td class="label" style="text-align: center; font-weight: bold;">Cover Amount Per Dependant</td>
            <td class="label" style="text-align: center; font-weight: bold;">Premium Per Month $</td>
        </tr>
        @for ($i = 0; $i < 8; $i++)
        <tr>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['name']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['name'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['forenames']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['forenames'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['relationship']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['relationship'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['dateOfBirth']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['dateOfBirth'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['idNumber']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['idNumber'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['coverAmount']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['coverAmount'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['dependents'][$i]['premium']))
                    {{ $formResponses['funeralCover']['dependents'][$i]['premium'] }}
                @endif
            </td>
        </tr>
        @endfor
        <tr>
            <td colspan="7" style="text-align: center; font-weight: bold; background-color: #f0f0f0; padding: 1px; height: 10px;">Principal Member</td>
        </tr>
        <tr>
            <td class="input tight-row">{{ $formResponses['surname'] ?? '' }}</td>
            <td class="input tight-row">{{ $formResponses['firstName'] ?? '' }} {{ $formResponses['otherNames'] ?? '' }}</td>
            <td class="input tight-row">Self</td>
            <td class="input tight-row">{{ $formResponses['dateOfBirth'] ?? '' }}</td>
            <td class="input tight-row">{{ $formResponses['nationalIdNumber'] ?? '' }}</td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['coverAmount']))
                    {{ $formResponses['funeralCover']['principalMember']['coverAmount'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['premium']))
                    {{ $formResponses['funeralCover']['principalMember']['premium'] }}
                @endif
            </td>
        </tr>
    </table>
    
    <!-- Supplementary Benefits -->
    <table class="main-table">
        <tr>
            <td colspan="7" style="text-align: center; font-weight: bold; background-color: #f0f0f0; padding: 1px; height: 10px;">Supplementary Benefits (Tick (✓) appropriate box)</td>
        </tr>
        <tr>
            <td class="input" style="width: 5%; height: 8px;">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['memorialCashBenefit']) && $formResponses['funeralCover']['principalMember']['memorialCashBenefit'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">Memorial Cash Benefit:</td>
            <td class="label">Amount of Cover Per Person</td>
            <td class="input" colspan="4">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['memorialCashBenefitAmount']))
                    {{ $formResponses['funeralCover']['principalMember']['memorialCashBenefitAmount'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="input">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['tombstoneCashBenefit']) && $formResponses['funeralCover']['principalMember']['tombstoneCashBenefit'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">Tombstone Cash Benefit:</td>
            <td class="label">Amount of Cover Per Person</td>
            <td class="input" colspan="4">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['tombstoneCashBenefitAmount']))
                    {{ $formResponses['funeralCover']['principalMember']['tombstoneCashBenefitAmount'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="input">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['groceryBenefit']) && $formResponses['funeralCover']['principalMember']['groceryBenefit'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">Grocery Benefit:</td>
            <td class="label">Amount of Cover</td>
            <td class="input" colspan="4">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['groceryBenefitAmount']))
                    {{ $formResponses['funeralCover']['principalMember']['groceryBenefitAmount'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="input">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['schoolFeesBenefit']) && $formResponses['funeralCover']['principalMember']['schoolFeesBenefit'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">School Fees Benefit:</td>
            <td class="label">Amount of Cover</td>
            <td class="input" colspan="4">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['schoolFeesBenefitAmount']))
                    {{ $formResponses['funeralCover']['principalMember']['schoolFeesBenefitAmount'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="input">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['personalAccidentBenefit']) && $formResponses['funeralCover']['principalMember']['personalAccidentBenefit'])
                    <span class="checkbox-checked"></span>
                @else
                    <span class="checkbox"></span>
                @endif
            </td>
            <td class="label">Personal Accident Benefit:</td>
            <td class="label" colspan="2">Please supply details below</td>
            <td class="input" colspan="3">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['principalMember']) && isset($formResponses['funeralCover']['principalMember']['personalAccidentBenefitDetails']))
                    {{ $formResponses['funeralCover']['principalMember']['personalAccidentBenefitDetails'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold; background-color: #f0f0f0; padding: 1px; height: 8px;">Total Monthly Premium</td>
            <td class="input" colspan="4">
                @if(isset($formResponses['funeralCover']) && isset($formResponses['funeralCover']['totalMonthlyPremium']))
                    {{ $formResponses['funeralCover']['totalMonthlyPremium'] }}
                @endif
            </td>
        </tr>
    </table>
    
    <!-- Section I -->
    <div class="section-header">I - PERSONAL ACCIDENT BENEFIT</div>
    <table class="main-table">
        <tr style="background-color: #f0f0f0;">
            <td class="label" style="text-align: center; font-weight: bold; width: 50%; height: 10px;">Surname</td>
            <td class="label" style="text-align: center; font-weight: bold; width: 50%;">Forename(s)</td>
        </tr>
        @for ($i = 0; $i < 4; $i++)
        <tr>
            <td class="input tight-row">
                @if(isset($formResponses['personalAccidentBenefit'][$i]['surname']))
                    {{ $formResponses['personalAccidentBenefit'][$i]['surname'] }}
                @endif
            </td>
            <td class="input tight-row">
                @if(isset($formResponses['personalAccidentBenefit'][$i]['forenames']))
                    {{ $formResponses['personalAccidentBenefit'][$i]['forenames'] }}
                @endif
            </td>
        </tr>
        @endfor
    </table>
    
    <!-- Section J -->
    <div class="section-header">J - SUMMARY OF TERMS AND CONDITIONS OF MEMBERSHIP</div>
    <div style="font-size: 4pt; line-height: 1.1; margin: 1px 0;">
        <p style="margin: 1px 0;"><strong>1.0</strong> Funeral assurance cover under the Plan shall commence on the first day of the month coinciding with or next following the payment of the first premium.</p>
        <p style="margin: 1px 0;"><strong>1.1</strong> The Plan does not cover death by suicide or by the hand of Justice within a period of twenty-four (24) months from the date of Joining the Plan.</p>
        <p style="margin: 1px 0;"><strong>1.2</strong> Save as herein provided, Membership shall apply if any premium is not paid when due and no right Reinstated nor on account of previous payment shall exist.</p>
        <p style="margin: 1px 0;"><strong>1.3</strong> A grace period of one calendar month is allowed for the payment of each and every premium.</p>
        <p style="margin: 1px 0;"><strong>1.4</strong> Coverage under the Plan shall terminate on the death of the Principal Member or on the voluntary termination by the Principal Member or on the lapse of Membership of the Plan as a result of non-payment of premiums.</p>
        <p style="margin: 1px 0;"><strong>1.5</strong> Funeral Assurance cover does not become payable under claims when only one claim is vested after the expiry of three (3) consecutive months in respect of the Principal Member and any Immediate Family Member, from the Date of Joining the Plan or date of reinstatement or date of registration of a Dependent.</p>
        <p style="margin: 1px 0;"><strong>1.6</strong> Immediate Family Member means, in respect of the Principal Member, a claim dependent spouse, own children and persons under the legal guardianship of the Principal Member, and dependent natural or adoptive parents or grandparents.</p>
        <p style="margin: 1px 0;"><strong>1.7</strong> Extended Family Member means a Dependent who is not an Immediate Family Member.</p>
        <p style="margin: 1px 0;"><strong>1.8</strong> The qualifying period for coverage to be effectively stated in paragraph 1.0 above shall apply to any increase in the funeral benefit cover of each insured person.</p>
        <p style="margin: 1px 0;"><strong>1.9</strong> Claims shall be settled only if they are reported to ZB Bank Limited within three (3) months from the date of death of an insured person.</p>
        <p style="margin: 1px 0;"><strong>1.10</strong> The maximum cover for each person shall not exceed the limit set from time to time.</p>
    </div>
    
    <!-- Section K -->
    <div class="section-header">K - DECLARATION</div>
    <div style="font-size: 5pt; margin: 2px 0;">
        I confirm that to the best of my knowledge, the above information is true and correct and that all the persons registered above are not on medication for any disease or illness. Should anything change, I undertake to advise ZB Bank immediately.
    </div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 15%;">Full Name:</td>
            <td class="input" style="width: 30%;">{{ $formResponses['firstName'] ?? '' }} {{ $formResponses['surname'] ?? '' }}</td>
            <td class="label" style="width: 20%;">Applicant's Signature:</td>
            <td class="input" style="width: 20%;">
                @if(isset($documents['signature']) && $documents['signature'])
                    <img src="{{ $documents['signature'] }}" alt="Signature" style="max-height: 20px; max-width: 100%;">
                @endif
            </td>
            <td class="label" style="width: 5%;">Date:</td>
            <td class="input" style="width: 10%;">{{ isset($formResponses['declaration']['date']) ? $formResponses['declaration']['date'] : date('Y-m-d') }}</td>
        </tr>
    </table>
    
    <div class="page-footer">Page 2 of 4</div>
</div><
!-- PAGE 3 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo-img">
            </td>
            <td style="width: 60%;"></td>
            <td style="width: 20%;" class="tagline">
                BANKING | INVESTMENTS | INSURANCE
            </td>
        </tr>
    </table>
    
    <!-- Section L -->
    <div class="section-header">L - DECLARATION BY APPLICANT</div>
    <div style="font-size: 5pt; margin: 1px 0;">
        I authorise ZB Bank to deduct the premiums stated above each month from my account when funded.
    </div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">Accountholder's name:</td>
            <td class="input" style="width: 30%;">{{ $formResponses['firstName'] ?? '' }} {{ $formResponses['surname'] ?? '' }}</td>
            <td class="label" style="width: 20%;">Accountholder's ID:</td>
            <td class="input" style="width: 30%;">{{ $formResponses['nationalIdNumber'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Account number:</td>
            <td class="input">{{ $formResponses['accountNumber'] ?? '' }}</td>
            <td class="label">Accountholder's Signature:</td>
            <td class="input">
                @if(isset($documents['signature']) && $documents['signature'])
                    <img src="{{ $documents['signature'] }}" alt="Signature" style="max-height: 20px; max-width: 100%;">
                @endif
            </td>
        </tr>
    </table>
    
    <!-- Section M -->
    <div class="section-header">M - FOR COMPLETION AT BRANCH UPON CARD COLLECTION</div>
    <div style="font-size: 5pt; margin: 1px 0;">
        I confirm safe receipt of my PIN (Personal Identification Number) and acknowledge receipt of my card.
    </div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">Name of Cardholder:</td>
            <td class="input" style="width: 25%;"></td>
            <td class="label" style="width: 20%;">Signature of Cardholder:</td>
            <td class="input" style="width: 20%;"></td>
            <td class="label" style="width: 5%;">Date:</td>
            <td class="input" style="width: 10%;"></td>
        </tr>
    </table>
    
    <!-- Section N - Terms and Conditions -->
    <div class="section-header">N- TERMS AND CONDITIONS</div>
    
    <div class="terms-columns">
        <div style="font-size: 3.5pt; line-height: 1.0; margin: 1px 0;">
            <p style="margin: 1px 0;">These terms and conditions, together with any further instructions that may be presented by ZB BANK ('the Bank') from time to time shall constitute the Agreement between the customer and ZB Bank. The words used in this Agreement shall have the following meanings:</p>
            
            <p style="margin: 1px 0;"><strong>1. DEFINITIONS.</strong> The following terms as stated in these terms and conditions shall have the following meanings:</p>
            <p style="margin: 0.5px 0;"><strong>1.1</strong> Customer: The applicant of the account and services is hereinafter referred as 'Customer'.</p>
            <p style="margin: 0.5px 0;"><strong>1.2</strong> Bank: The 'Bank' means ZB BANK LIMITED.</p>
            <p style="margin: 0.5px 0;"><strong>1.3</strong> Service: All services offered by the Bank is hereinafter referred as 'Service'.</p>
            <p style="margin: 0.5px 0;"><strong>1.4</strong> Card: The card means the ZB Bank Debit Card.</p>
            <p style="margin: 0.5px 0;"><strong>1.5</strong> Cardholder: The account holder issued the card by the Bank.</p>
            <p style="margin: 0.5px 0;"><strong>1.6</strong> Account: The account means the designated account with the Bank.</p>
            <p style="margin: 0.5px 0;"><strong>1.7</strong> Accountholder: The person who has been issued an account by the Bank.</p>
            <p style="margin: 0.5px 0;"><strong>1.8</strong> PIN: The Personal Identification Number issued to the Cardholder.</p>
            <p style="margin: 0.5px 0;"><strong>1.9</strong> ATM: Automated Teller Machine.</p>
            <p style="margin: 0.5px 0;"><strong>1.10</strong> POS: Point of Sale terminal.</p>
            <p style="margin: 0.5px 0;"><strong>1.11</strong> Merchant: Any person who agrees to accept the Card as payment for goods and services.</p>
            <p style="margin: 0.5px 0;"><strong>1.12</strong> Transaction: Any transaction effected by the use of the Card, PIN or Card number.</p>
            <p style="margin: 0.5px 0;"><strong>1.13</strong> Statement: A periodic statement of account sent by the Bank to a Customer setting out the transactions carried out in the Account during the given period and the balance in such Account.</p>
            <p style="margin: 0.5px 0;"><strong>1.14</strong> Zimswitch: The shared financial services network operated by Zimswitch (Pvt) Ltd.</p>
            
            <p style="margin: 1px 0;"><strong>2. ACCOUNT OPENING AND OPERATION</strong></p>
            <p style="margin: 0.5px 0;"><strong>2.1</strong> The Bank may open an account for the Customer upon receiving a duly completed account opening form and all supporting documents required by the Bank.</p>
            <p style="margin: 0.5px 0;"><strong>2.2</strong> The Bank reserves the right to decline to open an account without giving any reasons.</p>
            <p style="margin: 0.5px 0;"><strong>2.3</strong> The Customer shall provide the Bank with specimen signatures of all persons authorized to operate the account.</p>
            <p style="margin: 0.5px 0;"><strong>2.4</strong> The Bank shall be entitled to rely on the specimen signatures provided by the Customer until such time as the Customer notifies the Bank in writing of any change.</p>
            <p style="margin: 0.5px 0;"><strong>2.5</strong> The Customer shall immediately notify the Bank in writing of any change in the details provided in the account opening form.</p>
            <p style="margin: 0.5px 0;"><strong>2.6</strong> The Bank may accept deposits from the Customer in cash, by cheque or by electronic funds transfer.</p>
            <p style="margin: 0.5px 0;"><strong>2.7</strong> The Bank may require the Customer to maintain a minimum balance in the account and may levy a charge if the balance falls below the required minimum.</p>
            <p style="margin: 0.5px 0;"><strong>2.8</strong> The Bank may close an account if in the Bank's opinion the Customer is not operating the account satisfactorily.</p>
            <p style="margin: 0.5px 0;"><strong>2.9</strong> The Bank may also close the account at its sole discretion by giving the Customer 14 days' notice.</p>
            <p style="margin: 0.5px 0;"><strong>2.10</strong> The Customer may close the account by giving the Bank 14 days' notice in writing.</p>
            
            <p style="margin: 1px 0;"><strong>3. DEPOSITS AND WITHDRAWALS</strong></p>
            <p style="margin: 0.5px 0;"><strong>3.1</strong> The Bank will credit the Customer's account with deposits received in accordance with the Bank's standard procedures.</p>
            <p style="margin: 0.5px 0;"><strong>3.2</strong> The Bank will only allow withdrawals if there are sufficient funds in the account.</p>
            <p style="margin: 0.5px 0;"><strong>3.3</strong> The Bank may refuse to make a payment if it would result in the account being overdrawn without prior arrangement.</p>
            <p style="margin: 0.5px 0;"><strong>3.4</strong> The Customer may withdraw money from the account by using the Card at ATMs, by issuing a cheque (if applicable), by giving instructions for a standing order, direct debit, or other payment service, or by making a transfer to another account.</p>
            <p style="margin: 0.5px 0;"><strong>3.5</strong> The Bank may limit cash withdrawals to a maximum amount per day.</p>
            
            <p style="margin: 1px 0;"><strong>4. FEES AND CHARGES</strong></p>
            <p style="margin: 0.5px 0;"><strong>4.1</strong> The Bank shall be entitled to charge fees for the operation of the account, the use of the Card, and for other services provided to the Customer.</p>
            <p style="margin: 0.5px 0;"><strong>4.2</strong> The Bank shall display a list of its standard fees and charges at its branches and on its website.</p>
            <p style="margin: 0.5px 0;"><strong>4.3</strong> The Bank may vary its fees and charges from time to time by giving the Customer 14 days' notice.</p>
            <p style="margin: 0.5px 0;"><strong>4.4</strong> The Bank shall be entitled to debit the Customer's account with all fees and charges payable by the Customer.</p>
            
            <p style="margin: 1px 0;"><strong>5. STATEMENTS</strong></p>
            <p style="margin: 0.5px 0;"><strong>5.1</strong> The Bank will provide the Customer with statements of account at regular intervals or as agreed with the Customer.</p>
            <p style="margin: 0.5px 0;"><strong>5.2</strong> The Customer shall examine the statement carefully and notify the Bank in writing of any errors or discrepancies within 14 days of receiving the statement.</p>
            <p style="margin: 0.5px 0;"><strong>5.3</strong> If the Customer does not notify the Bank of any errors or discrepancies within 14 days, the statement shall be deemed to be correct.</p>
            <p style="margin: 0.5px 0;"><strong>5.4</strong> The Bank may correct any errors in the statement even after the 14-day period has expired.</p>
            
            <p style="margin: 1px 0;"><strong>6. CARD USAGE</strong></p>
            <p style="margin: 0.5px 0;"><strong>6.1</strong> The Card is and shall remain the property of the Bank at all times.</p>
            <p style="margin: 0.5px 0;"><strong>6.2</strong> The Card is issued for the exclusive use of the Cardholder and is not transferable.</p>
            <p style="margin: 0.5px 0;"><strong>6.3</strong> The Cardholder shall sign the Card immediately upon receipt.</p>
            <p style="margin: 0.5px 0;"><strong>6.4</strong> The Cardholder shall keep the Card secure at all times and shall not allow any other person to use the Card.</p>
            <p style="margin: 0.5px 0;"><strong>6.5</strong> The Cardholder shall keep the PIN secret and shall not disclose it to any other person.</p>
            <p style="margin: 0.5px 0;"><strong>6.6</strong> The Cardholder shall not write the PIN on the Card or keep a written record of the PIN in close proximity to the Card.</p>
            <p style="margin: 0.5px 0;"><strong>6.7</strong> The Cardholder shall use the Card only for transactions permitted by the Bank.</p>
            <p style="margin: 0.5px 0;"><strong>6.8</strong> The Cardholder shall not use the Card for any illegal purpose.</p>
            <p style="margin: 0.5px 0;"><strong>6.9</strong> The Cardholder shall not use the Card after the expiry date printed on the Card.</p>
            <p style="margin: 0.5px 0;"><strong>6.10</strong> The Bank may refuse to authorize a transaction if it suspects that the Card is being used fraudulently or for an illegal purpose.</p>
            
            <p style="margin: 1px 0;"><strong>7. LOST OR STOLEN CARDS</strong></p>
            <p style="margin: 0.5px 0;"><strong>7.1</strong> The Cardholder shall immediately notify the Bank if the Card is lost or stolen, or if the PIN becomes known to any other person.</p>
            <p style="margin: 0.5px 0;"><strong>7.2</strong> The Cardholder shall be liable for all transactions made with the Card before the Bank receives notification of the loss or theft of the Card.</p>
            <p style="margin: 0.5px 0;"><strong>7.3</strong> The Bank shall not be liable for any loss or damage arising from the loss or theft of the Card or the disclosure of the PIN.</p>
            
            <p style="margin: 1px 0;"><strong>8. LIABILITY</strong></p>
            <p style="margin: 0.5px 0;"><strong>8.1</strong> The Customer shall be liable for all transactions made with the Card, whether authorized by the Cardholder or not.</p>
            <p style="margin: 0.5px 0;"><strong>8.2</strong> The Bank shall not be liable for any loss or damage arising from the use of the Card or the refusal of any Merchant to accept the Card.</p>
            <p style="margin: 0.5px 0;"><strong>8.3</strong> The Bank shall not be liable for any defect in the goods or services purchased with the Card.</p>
            <p style="margin: 0.5px 0;"><strong>8.4</strong> The Bank shall not be liable for any loss or damage arising from the failure of any ATM or POS terminal.</p>
            <p style="margin: 0.5px 0;"><strong>8.5</strong> The Bank shall not be liable for any loss or damage arising from the failure of the Zimswitch network.</p>
            
            <p style="margin: 1px 0;"><strong>9. AMENDMENTS</strong></p>
            <p style="margin: 0.5px 0;"><strong>9.1</strong> The Bank may amend these terms and conditions at any time by giving the Customer 14 days' notice.</p>
            <p style="margin: 0.5px 0;"><strong>9.2</strong> The notice may be given by displaying the amendments at the Bank's branches, by publishing the amendments on the Bank's website, or by any other means the Bank considers appropriate.</p>
            <p style="margin: 0.5px 0;"><strong>9.3</strong> The Customer shall be deemed to have accepted the amendments if the Customer continues to use the Card or the account after the 14-day notice period has expired.</p>
            
            <p style="margin: 1px 0;"><strong>10. TERMINATION</strong></p>
            <p style="margin: 0.5px 0;"><strong>10.1</strong> The Bank may terminate this Agreement at any time by giving the Customer 14 days' notice.</p>
            <p style="margin: 0.5px 0;"><strong>10.2</strong> The Customer may terminate this Agreement at any time by giving the Bank 14 days' notice in writing and returning the Card to the Bank.</p>
            <p style="margin: 0.5px 0;"><strong>10.3</strong> The Bank may terminate this Agreement immediately if the Customer breaches any of these terms and conditions.</p>
            <p style="margin: 0.5px 0;"><strong>10.4</strong> Upon termination of this Agreement, the Customer shall return the Card to the Bank and shall pay all amounts due to the Bank.</p>
            
            <p style="margin: 1px 0;"><strong>11. GOVERNING LAW</strong></p>
            <p style="margin: 0.5px 0;"><strong>11.1</strong> This Agreement shall be governed by and construed in accordance with the laws of Zimbabwe.</p>
            <p style="margin: 0.5px 0;"><strong>11.2</strong> Any dispute arising from or in connection with this Agreement shall be subject to the exclusive jurisdiction of the courts of Zimbabwe.</p>
        </div>
    </div>
    
    <div class="page-footer">Page 3 of 4</div>
</div><!-- 
PAGE 4 -->
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/zb_logo.png'))) }}" alt="ZB Logo" class="logo-img">
            </td>
            <td style="width: 60%;"></td>
            <td style="width: 20%;" class="tagline">
                BANKING | INVESTMENTS | INSURANCE
            </td>
        </tr>
    </table>
    
    <!-- Section O -->
    <div class="section-header">O - DECLARATION AND ACCEPTANCE OF TERMS AND CONDITIONS</div>
    <div style="font-size: 5pt; margin: 1px 0; line-height: 1.2;">
        <p>I/We hereby apply for the opening of an account with ZB Bank Limited. I/We understand that the information given herein and the documents supplied are the basis for opening such account and I/We therefore warrant that such information is correct.</p>
        <p>I/We further undertake to inform the Bank of any change in the information provided in this form.</p>
        <p>I/We agree to be bound by the terms and conditions governing the operations of the account as set out in section N above.</p>
        <p>I/We have read and understood the terms and conditions and hereby accept them.</p>
    </div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 15%;">Full Name:</td>
            <td class="input" style="width: 30%;">{{ $formResponses['firstName'] ?? '' }} {{ $formResponses['surname'] ?? '' }}</td>
            <td class="label" style="width: 20%;">Applicant's Signature:</td>
            <td class="input" style="width: 20%;">
                @if(isset($documents['signature']) && $documents['signature'])
                    <img src="{{ $documents['signature'] }}" alt="Signature" style="max-height: 20px; max-width: 100%;">
                @endif
            </td>
            <td class="label" style="width: 5%;">Date:</td>
            <td class="input" style="width: 10%;">{{ isset($formResponses['declaration']['date']) ? $formResponses['declaration']['date'] : date('Y-m-d') }}</td>
        </tr>
    </table>
    
    <!-- Section P -->
    <div class="section-header">P - FOR BANK USE ONLY</div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">Account Name:</td>
            <td class="input" style="width: 30%;"></td>
            <td class="label" style="width: 20%;">Account Number:</td>
            <td class="input" style="width: 30%;"></td>
        </tr>
        <tr>
            <td class="label">Account Type:</td>
            <td class="input">{{ $formResponses['accountType'] ?? '' }}</td>
            <td class="label">Currency:</td>
            <td class="input">{{ $formResponses['accountCurrency'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Branch:</td>
            <td class="input">{{ $formResponses['serviceCenter'] ?? '' }}</td>
            <td class="label">Date Opened:</td>
            <td class="input">{{ $applicationDate ?? date('d/m/Y') }}</td>
        </tr>
    </table>
    
    <div style="font-size: 5pt; margin: 5px 0; font-weight: bold;">DOCUMENTATION CHECKLIST</div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 40%;">National ID/Valid Passport:</td>
            <td class="input" style="width: 10%; text-align: center;">
                <span class="checkbox"></span>
            </td>
            <td class="label" style="width: 40%;">Proof of Residence:</td>
            <td class="input" style="width: 10%; text-align: center;">
                <span class="checkbox"></span>
            </td>
        </tr>
        <tr>
            <td class="label">Proof of Income/Employment:</td>
            <td class="input" style="text-align: center;">
                <span class="checkbox"></span>
            </td>
            <td class="label">Passport Photos:</td>
            <td class="input" style="text-align: center;">
                <span class="checkbox"></span>
            </td>
        </tr>
        <tr>
            <td class="label">Birth Certificate (for minors):</td>
            <td class="input" style="text-align: center;">
                <span class="checkbox"></span>
            </td>
            <td class="label">Other (specify):</td>
            <td class="input" style="text-align: center;">
                <span class="checkbox"></span>
            </td>
        </tr>
    </table>
    
    <div style="font-size: 5pt; margin: 5px 0; font-weight: bold;">APPROVALS</div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">Prepared by:</td>
            <td class="input" style="width: 30%;"></td>
            <td class="label" style="width: 20%;">Signature:</td>
            <td class="input" style="width: 30%;"></td>
        </tr>
        <tr>
            <td class="label">Checked by:</td>
            <td class="input"></td>
            <td class="label">Signature:</td>
            <td class="input"></td>
        </tr>
        <tr>
            <td class="label">Approved by:</td>
            <td class="input"></td>
            <td class="label">Signature:</td>
            <td class="input"></td>
        </tr>
    </table>
    
    <div style="font-size: 5pt; margin: 5px 0; font-weight: bold;">CARD DETAILS</div>
    
    <table class="main-table">
        <tr>
            <td class="label" style="width: 20%;">Card Number:</td>
            <td class="input" style="width: 30%;"></td>
            <td class="label" style="width: 20%;">Card Type:</td>
            <td class="input" style="width: 30%;"></td>
        </tr>
        <tr>
            <td class="label">Issued by:</td>
            <td class="input"></td>
            <td class="label">Date Issued:</td>
            <td class="input"></td>
        </tr>
        <tr>
            <td class="label">Collected by:</td>
            <td class="input"></td>
            <td class="label">Date Collected:</td>
            <td class="input"></td>
        </tr>
    </table>
    
    <div style="margin-top: 10px; text-align: center;">
        <div style="display: inline-block; border: 1px solid #000; padding: 5px; width: 150px; height: 80px; text-align: center; vertical-align: middle; font-size: 5pt;">
            <p style="margin-top: 30px;">BANK STAMP</p>
        </div>
    </div>
    
    <div class="page-footer">Page 4 of 4</div>
</div>

</body>
</html>