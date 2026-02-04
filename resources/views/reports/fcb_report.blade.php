<div class="page-break" style="page-break-before: always; font-family: Arial, sans-serif;">
    <!-- FCB Header -->
    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td width="50%" valign="top">
                <img src="{{ public_path('images/fcb_logo.png') }}" alt="FCB Logo" style="height: 50px;">
            </td>
            <td width="30%" valign="top" style="font-size: 8px; line-height: 1.4; color: #333;">
                24 Harvey Brown Avenue<br>
                Milton Park<br>
                P.O Box 1872<br>
                Harare, Zimbabwe
            </td>
            <td width="20%" valign="top" style="font-size: 8px; line-height: 1.4; text-align: left; color: #333;">
                <table width="100%">
                    <tr>
                        <td width="15"><img src="{{ public_path('images/phone_icon.png') }}" style="height: 8px;"></td>
                        <td>: +263 4 794367-9</td>
                    </tr>
                    <tr>
                        <td><img src="{{ public_path('images/phone_icon.png') }}" style="height: 8px;"></td>
                        <td>: 08688002306</td>
                    </tr>
                    <tr>
                        <td><img src="{{ public_path('images/email_icon.png') }}" style="height: 8px;"></td>
                        <td>: search@fcbureau.co.zw</td>
                    </tr>
                    <tr>
                        <td><img src="{{ public_path('images/web_icon.png') }}" style="height: 8px;"></td>
                        <td>: www.fcbureau.co.zw</td>
                    </tr>
                </table>
            </td>
            <td width="10%" valign="top" style="text-align: right;">
                <!-- QR Code Placeholder -->
                <img src="{{ public_path('images/qr_placeholder.png') }}" style="height: 40px;">
            </td>
        </tr>
    </table>

    <!-- Report Title Bar -->
    <div style="background-color: #999; color: white; padding: 5px; text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 0;">
        CREDIT & CLEARING REFERENCE BUREAU INDIVIDUAL REPORT
    </div>

    <!-- Subscriber Info Bar -->
    <table width="100%" style="font-size: 9px; background-color: #008080; color: white; margin-bottom: 5px; font-weight: bold;">
        <tr>
            <td style="padding: 5px;">SUBSCRIBER: ZB BANK</td>
            <td style="padding: 5px; text-align: center;">BRANCH: {{ strtoupper($data['branch'] ?? 'HEAD OFFICE') }}</td>
            <td style="padding: 5px; text-align: right;">USER: {{ strtoupper($data['user'] ?? 'SYSTEM') }}</td>
        </tr>
    </table>

    <!-- Report Meta -->
    <table width="100%" style="font-size: 9px; margin-bottom: 20px;">
        <tr>
            <td>Report Serial: {{ $data['report_serial'] ?? 'N/A' }}</td>
            <td style="text-align: right;">Report Date: {{ $data['report_date'] ?? date('d-M-Y H:i') }}</td>
        </tr>
    </table>

    <!-- Individual Score Section -->
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">INDIVIDUAL REPORT</div>
        <div style="font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">
            {{ $data['individual_details']['name'] ?? 'APPLICANT NAME' }}
        </div>
        
        <table align="center" style="font-size: 12px; font-weight: bold;">
            <tr>
                <td style="padding-right: 10px; color: #666;">SCORE:</td>
                <td style="padding-right: 15px;">
                    <span style="font-size: 16px; color: #333;">{{ $data['fcb_score'] ?? '0' }}</span>
                </td>
                <td style="padding-right: 5px;">
                    <div style="width: 20px; height: 12px; background-color: #40E0D0; display: inline-block;"></div>
                </td>
                <td style="color: #008080;">(Medium to Low Risk - 301 to 350)</td>
            </tr>
            <tr>
                <td style="padding-right: 10px; color: #666;">STATUS:</td>
                <td style="padding-right: 15px;">
                    <span style="font-size: 16px; color: #333;">{{ $data['status'] ?? 'UNKNOWN' }}</span>
                </td>
                <td>
                    <div style="width: 20px; height: 12px; background-color: #0000FF; display: inline-block;"></div>
                </td>
                <td style="color: #0000FF;">(Clean History)</td>
            </tr>
        </table>
    </div>

    <!-- Separator Line -->
    <div style="border-bottom: 1px dotted #ccc; margin-bottom: 15px;"></div>

    <!-- Personal Details -->
    <table width="100%" style="font-size: 9px; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td width="25%" style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_flag.png') }}" style="height: 8px; margin-right: 5px;"> NATIONALITY :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['nationality'] ?? 'ZIMBABWE' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_calendar.png') }}" style="height: 8px; margin-right: 5px;"> DATE OF BIRTH :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['dob'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
               <img src="{{ public_path('images/icon_id.png') }}" style="height: 8px; margin-right: 5px;"> NATIONAL ID :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['national_id'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_gender.png') }}" style="height: 8px; margin-right: 5px;"> GENDER :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['gender'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_phone.png') }}" style="height: 8px; margin-right: 5px;"> MOBILE :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['mobile'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_home.png') }}" style="height: 8px; margin-right: 5px;"> PROPERTY STATUS :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['property_status'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_building.png') }}" style="height: 8px; margin-right: 5px;"> PROPERTY DENSITY :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['property_density'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_location.png') }}" style="height: 8px; margin-right: 5px;"> ADDRESS :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['address'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ccc; padding: 4px; font-weight: bold; background-color: #f5f5f5;">
                <img src="{{ public_path('images/icon_rings.png') }}" style="height: 8px; margin-right: 5px;"> MARITAL STATUS :
            </td>
            <td style="border: 1px solid #ccc; padding: 4px;">{{ $data['individual_details']['marital_status'] ?? '' }}</td>
        </tr>
    </table>

    <!-- Addresses Section -->
    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">
        ADDRESSES <span style="font-weight: normal; color: #666; font-size: 10px;">(Last 5 years with most recent first)</span>
    </div>
    <table width="100%" style="font-size: 9px; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
        <thead>
            <tr style="background-color: #666; color: white;">
                <th style="padding: 5px; border: 1px solid #fff;">DATE</th>
                <th style="padding: 5px; border: 1px solid #fff;">STREET NAME</th>
                <th style="padding: 5px; border: 1px solid #fff;">CITY</th>
                <th style="padding: 5px; border: 1px solid #fff;">COUNTRY</th>
                <th style="padding: 5px; border: 1px solid #fff;">PHONE</th>
                <th style="padding: 5px; border: 1px solid #fff;">PROPERTY STATUS</th>
                <th style="padding: 5px; border: 1px solid #fff;">OWNERSHIP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['addresses'] as $addr)
            <tr style="border-bottom: 1px solid #ccc;">
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['date'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['street'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['city'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['country'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['phone'] ?? '' }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['property_status'] ?? '' }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $addr['ownership'] ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 5px; text-align: center; border: 1px solid #000;">No records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Applications Section -->
    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">
        TOTAL APPLICATIONS FOR THE INDIVIDUAL - {{ count($data['previous_searches'] ?? []) }} Applications
    </div>
    <div style="color: #666; font-size: 10px; margin-bottom: 5px;">Last 5 Applications for the past 5 years with most recent first</div>
    <table width="100%" style="font-size: 9px; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
        <thead>
            <tr style="background-color: #666; color: white;">
                <th style="padding: 5px; border: 1px solid #fff;">DATE</th>
                <th style="padding: 5px; border: 1px solid #fff;">SEARCH PURPOSE</th>
                <th style="padding: 5px; border: 1px solid #fff;">SUBSCRIBER</th>
                <th style="padding: 5px; border: 1px solid #fff;">BRANCH</th>
                <th style="padding: 5px; border: 1px solid #fff;">SCORE</th>
                <th style="padding: 5px; border: 1px solid #fff;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse(array_slice($data['previous_searches'], 0, 5) as $search)
            <tr style="border-bottom: 1px solid #ccc;">
                <td style="padding: 5px; border: 1px solid #000;">{{ $search['date'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $search['search_purpose'] ?? 'NEW CUSTOMER (KYC)' }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $search['subscriber'] ?? 'ZB BANK' }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $search['branch'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $search['score'] }}</td>
                <td style="padding: 5px; border: 1px solid #000;">{{ $search['status'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding: 5px; text-align: center; border: 1px solid #000;">No records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Reported Employment -->
    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">
        REPORTED EMPLOYMENT <span style="font-weight: normal; color: #666; font-size: 10px;">(Last 10 years with most recent first)</span>
    </div>
    <div style="font-size: 10px; font-style: italic; margin-bottom: 15px;">
        @if(empty($data['reported_employment']))
        (No records found)
        @else
        <!-- Render employment table if needed -->
        @endif
    </div>

    <!-- Confirmed Occupations -->
    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">
        CONFIRMED OCCUPATIONS <span style="font-weight: normal; color: #666; font-size: 10px;">(Last 10 years with most recent first)</span>
    </div>
    <div style="font-size: 10px; font-style: italic; margin-bottom: 15px;">
        @if(empty($data['confirmed_occupations']))
        (No records found)
        @else
        <!-- Render occupations table if needed -->
        @endif
    </div>

    <!-- Directorship -->
    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">
        DIRECTORSHIP / SIGNATORY FROM USER SEARCHES
    </div>
    <div style="font-size: 10px; font-style: italic; margin-bottom: 15px;">
        @if(empty($data['directorships_from_search']))
        (No records found)
        @else
        <!-- Render directorships table -->
        @endif
    </div>

    <!-- Directorship DB -->
    <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">
        DIRECTORSHIP IN FCB DATABASE
    </div>
    <div style="font-size: 10px; font-style: italic; margin-bottom: 20px;">
        @if(empty($data['directorships_db']))
        (No records found)
        @else
        <!-- Render directorships db table -->
        @endif
    </div>

    <!-- Disclaimer Footer -->
    <div style="border-top: 1px solid #ccc; padding-top: 10px; margin-top: 20px;">
        <div style="color: red; font-size: 9px; font-weight: bold; margin-bottom: 3px;">Disclaimer Warning:</div>
        <div style="color: #666; font-size: 8px; line-height: 1.2; text-align: justify;">
            Information contained in this report is privileged and may be covered by confidentiality agreements and data protection laws. Therefore, disclosure of any or all information contained in this report, including the
            existence of the report, may be in breach of such agreements and laws. The subscriber takes full responsibility for understanding its obligations regarding confidentiality and data protection and indemnifies FCB
            against any and all liability for any damages or legal costs that may result from the misuse or wrongful disclosure of this report or its content to any third party.
        </div>
    </div>
    
    <!-- Footer Page Number -->
    <div style="background-color: #008080; color: white; padding: 5px; margin-top: 10px; font-weight: bold; font-size: 11px;">
        <table width="100%">
            <tr>
                <td>{{ $data['individual_details']['name'] ?? 'APPLICANT' }} - INDIVIDUAL REPORT</td>
                <td style="text-align: right;">Page 1</td>
            </tr>
        </table>
    </div>
</div>
