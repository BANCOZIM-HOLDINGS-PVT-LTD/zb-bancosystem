<div class="page-break" style="page-break-before: always;">
    <!-- FCB Header -->
    <table width="100%" style="border-bottom: 2px solid #0099cc; margin-bottom: 10px;">
        <tr>
            <td width="30%">
                <img src="{{ public_path('images/fcb_logo.png') }}" alt="FCB Logo" style="height: 60px;">
                <!-- Fallback text if logo missing -->
                <div style="font-size: 10px; color: #666;">Financial Clearing Bureau</div>
            </td>
            <td width="40%" style="font-size: 9px; line-height: 1.2;">
                24 Harvey Brown Avenue<br>
                Milton Park<br>
                P.O Box 1872<br>
                Harare, Zimbabwe
            </td>
            <td width="30%" style="font-size: 9px; text-align: right; line-height: 1.2;">
                <span style="font-weight: bold;">+263 4 794387-9</span><br>
                08688002306<br>
                search@fcbureau.co.zw<br>
                www.fcbureau.co.zw
            </td>
        </tr>
    </table>

    <!-- Report Info Header -->
    <div style="background-color: #666; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; text-align: center;">
        CREDIT & CLEARING REFERENCE BUREAU INDIVIDUAL ADD INFO REPORT
    </div>
    
    <table width="100%" style="font-size: 9px; background-color: #0099cc; color: white; margin-bottom: 10px;">
        <tr>
            <td style="padding: 3px;">SUBSCRIBER: ZB BANK</td>
            <td style="padding: 3px; text-align: center;">BRANCH: HEAD OFFICE</td>
            <td style="padding: 3px; text-align: right;">USER: SYSTEM</td>
        </tr>
    </table>

    <table width="100%" style="font-size: 10px; margin-bottom: 15px;">
        <tr>
            <td>Report Serial: {{ $data['report_serial'] ?? 'N/A' }}</td>
            <td style="text-align: right;">Report Date: {{ $data['report_date'] ?? date('d-M-Y H:i') }}</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; padding: 10px 0;">
                <div style="font-size: 14px; font-weight: bold; text-transform: uppercase;">
                    {{ $data['individual_details']['name'] ?? 'APPLICANT NAME' }} - Individual Report
                </div>
                <div style="margin-top: 5px;">
                    FCB SCORE: 
                    <span style="display: inline-block; width: 15px; height: 10px; background-color: {{ $data['score_color'] ?? 'yellow' }}; margin-right: 5px;"></span>
                    <span style="font-weight: bold; font-size: 12px;">{{ $data['fcb_score'] ?? '0' }}</span>
                    &nbsp;|&nbsp;
                    STATUS:
                    <span style="display: inline-block; width: 15px; height: 10px; background-color: {{ $data['status_color'] ?? 'blue' }}; margin-right: 5px;"></span>
                    <span style="font-weight: bold; font-size: 12px;">{{ $data['status'] ?? 'UNKNOWN' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Individual Details -->
    <div style="text-align: center; font-weight: bold; margin-bottom: 5px; font-size: 11px;">INDIVIDUAL DETAILS</div>
    <table width="100%" style="font-size: 9px; border-collapse: collapse; border: 1px solid #ccc; margin-bottom: 15px;">
        @foreach($data['individual_details'] as $key => $value)
            @if(!in_array($key, ['name'])) <!-- Skip name as it's in header -->
            <tr>
                <td width="30%" style="border: 1px solid #ccc; padding: 3px; background-color: #f9f9f9; font-weight: bold; text-transform: uppercase;">
                    {{ str_replace('_', ' ', $key) }} :
                </td>
                <td style="border: 1px solid #ccc; padding: 3px; text-transform: uppercase;">
                    {{ $value }}
                </td>
            </tr>
            @endif
        @endforeach
    </table>

    <!-- Addresses -->
    <div style="background-color: #0099cc; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; margin-bottom: 0;">
        ADDRESSES (Last 5 years with most recent first)
    </div>
    <table width="100%" style="font-size: 8px; border-collapse: collapse; margin-bottom: 15px;">
        <tr style="background-color: #eee; text-align: left;">
            <th style="padding: 3px;">DATE</th>
            <th style="padding: 3px;">STREET NAME</th>
            <th style="padding: 3px;">CITY</th>
            <th style="padding: 3px;">COUNTRY</th>
            <th style="padding: 3px;">PROPERTY RIGHTS</th>
        </tr>
        @forelse($data['addresses'] as $addr)
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 3px;">{{ $addr['date'] }}</td>
            <td style="padding: 3px;">{{ $addr['street'] }}</td>
            <td style="padding: 3px;">{{ $addr['city'] }}</td>
            <td style="padding: 3px;">{{ $addr['country'] }}</td>
            <td style="padding: 3px;">{{ $addr['rights'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="padding: 10px; text-align: center;">No records found.</td>
        </tr>
        @endforelse
    </table>

    <!-- Previous Searches -->
    <div style="background-color: #0099cc; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; margin-bottom: 0;">
        PREVIOUS SEARCHES (Last 5 years with most recent first)
    </div>
    <table width="100%" style="font-size: 8px; border-collapse: collapse; margin-bottom: 15px;">
        <tr style="background-color: #eee; text-align: left;">
            <th style="padding: 3px;">DATE</th>
            <th style="padding: 3px;">EVENT TYPE</th>
            <th style="padding: 3px;">COUNTERPARTY</th>
            <th style="padding: 3px;">BRANCH</th>
            <th style="padding: 3px;">SCORE</th>
            <th style="padding: 3px;">STATUS</th>
        </tr>
        @forelse($data['previous_searches'] as $search)
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 3px;">{{ $search['date'] }}</td>
            <td style="padding: 3px;">{{ $search['event_type'] }}</td>
            <td style="padding: 3px;">{{ $search['counterparty'] }}</td>
            <td style="padding: 3px;">{{ $search['branch'] }}</td>
            <td style="padding: 3px;">{{ $search['score'] }}</td>
            <td style="padding: 3px;">{{ $search['status'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="padding: 10px; text-align: center;">No records found.</td>
        </tr>
        @endforelse
    </table>

    <!-- Reported Incomes -->
    <div style="background-color: #0099cc; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; margin-bottom: 0;">
        REPORTED INCOMES (Last 5 years with most recent first)
    </div>
    <table width="100%" style="font-size: 8px; border-collapse: collapse; margin-bottom: 15px;">
        <tr style="background-color: #eee; text-align: left;">
            <th style="padding: 3px;">DATE</th>
            <th style="padding: 3px;">EMPLOYER</th>
            <th style="padding: 3px;">INDUSTRY</th>
            <th style="padding: 3px;">SALARY BAND</th>
            <th style="padding: 3px;">OCCUPATION</th>
        </tr>
        @forelse($data['reported_incomes'] as $income)
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 3px;">{{ $income['date'] }}</td>
            <td style="padding: 3px;">{{ $income['employer'] }}</td>
            <td style="padding: 3px;">{{ $income['industry'] }}</td>
            <td style="padding: 3px;">{{ $income['salary_band'] }}</td>
            <td style="padding: 3px;">{{ $income['occupation'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="padding: 10px; text-align: center;">No records found.</td>
        </tr>
        @endforelse
    </table>

    <!-- Directorship -->
    <div style="background-color: #0099cc; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; margin-bottom: 0;">
        DIRECTORSHIP FROM USER SEARCHES
    </div>
    <div style="font-size: 9px; padding: 5px; text-align: center; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
        @if(empty($data['directorships']))
            No records found.
        @else
            <!-- Render directorships table if needed -->
        @endif
    </div>

    <!-- Active Credit Events -->
    <div style="background-color: #0099cc; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; margin-bottom: 0;">
        REPORTED ACTIVE CREDIT EVENTS
    </div>
    <div style="font-size: 9px; padding: 5px; text-align: center; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
        @if(empty($data['active_credit_events']))
            No records found.
        @else
            <!-- Render table -->
        @endif
    </div>
    
    <!-- Convictions -->
    <div style="background-color: #0099cc; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; margin-bottom: 0;">
        CONVICTIONS
    </div>
    <div style="font-size: 9px; padding: 5px; text-align: center; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
        @if(empty($data['convictions']))
            No records found.
        @else
            <!-- Render table -->
        @endif
    </div>

    <!-- Legend -->
    <div style="border-top: 2px solid #0099cc; margin-top: 20px; padding-top: 5px;">
        <div style="font-size: 8px; font-weight: bold;">LEGEND</div>
        <img src="{{ public_path('images/fcb_legend.png') }}" alt="Legend" style="width: 100%; max-height: 50px;">
        <div style="font-size: 7px; color: red; margin-top: 2px;">Disclaimer Warning: Information contained in this report is privileged...</div>
    </div>
</div>
