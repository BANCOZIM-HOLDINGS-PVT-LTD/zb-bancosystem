<!-- Attachments Page -->
@if(isset($documentSummary) && $documentSummary['totalDocuments'] > 0)
<div class="page">
    <div style="text-align: center; margin-bottom: 12px;">
        <img src="{{ public_path('assets/images/bancozim.png') }}" alt="BancoZim Logo" style="max-height: 80px; max-width: 200px;">
    </div>

    <div style="background-color: #8BC34A; color: white; padding: 3px 5px; font-weight: bold; font-size: 9pt; margin: 2px 0; text-align: center;">
        SUPPORTING DOCUMENTS
    </div>

    <div style="font-size: 8pt; margin: 8px 0;">
        <strong>Applicant:</strong> {{ $formResponses['firstName'] ?? '' }} {{ $formResponses['surname'] ?? '' }}<br>
        <strong>Application Number:</strong> {{ $applicationNumber ?? '' }}<br>
        <strong>Date:</strong> {{ date('d/m/Y') }}
    </div>

    @if(isset($documentSummary['hasSelfie']) && $documentSummary['hasSelfie'] && isset($selfieImageData))
    <div style="margin: 12px 0; page-break-inside: avoid;">
        <div style="background-color: #8BC34A; color: white; padding: 2px 5px; font-weight: bold; font-size: 8pt; margin: 2px 0;">
            APPLICANT PHOTO
        </div>
        <div style="border: 1px solid #000; padding: 8px; text-align: center;">
            <img src="{{ $selfieImageData['data'] }}" alt="Applicant Photo" style="max-width: 200px; max-height: 250px;">
        </div>
    </div>
    @endif

    @if(isset($documentsByType['national_id']) && count($documentsByType['national_id']) > 0)
    <div style="margin: 12px 0; page-break-inside: avoid;">
        <div style="background-color: #8BC34A; color: white; padding: 2px 5px; font-weight: bold; font-size: 8pt; margin: 2px 0;">
            NATIONAL ID
        </div>
        @foreach($documentsByType['national_id'] as $doc)
            @if(isset($doc['embeddedData']) && $doc['embeddedData']['isImage'])
            <div style="border: 1px solid #000; padding: 8px; margin-top: 4px; text-align: center;">
                <img src="{{ $doc['embeddedData']['data'] }}" alt="National ID" style="max-width: 90%; max-height: 400px;">
                <div style="font-size: 7pt; margin-top: 4px; color: #666;">
                    {{ $doc['name'] }} ({{ $doc['size'] }})
                </div>
            </div>
            @elseif(isset($doc['embeddedData']) && $doc['embeddedData']['isPdf'])
            <div style="border: 1px solid #000; padding: 8px; margin-top: 4px;">
                <div style="font-size: 8pt;">
                    <strong>Document:</strong> {{ $doc['name'] }}<br>
                    <strong>Type:</strong> PDF Document<br>
                    <strong>Size:</strong> {{ $doc['size'] }}<br>
                    <strong>Pages:</strong> {{ $doc['embeddedData']['pages'] }}
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    @if(isset($documentsByType['payslip']) && count($documentsByType['payslip']) > 0)
    <div style="margin: 12px 0; page-break-inside: avoid;">
        <div style="background-color: #8BC34A; color: white; padding: 2px 5px; font-weight: bold; font-size: 8pt; margin: 2px 0;">
            PAYSLIP
        </div>
        @foreach($documentsByType['payslip'] as $doc)
            @if(isset($doc['embeddedData']) && $doc['embeddedData']['isImage'])
            <div style="border: 1px solid #000; padding: 8px; margin-top: 4px; text-align: center;">
                <img src="{{ $doc['embeddedData']['data'] }}" alt="Payslip" style="max-width: 90%; max-height: 500px;">
                <div style="font-size: 7pt; margin-top: 4px; color: #666;">
                    {{ $doc['name'] }} ({{ $doc['size'] }})
                </div>
            </div>
            @elseif(isset($doc['embeddedData']) && $doc['embeddedData']['isPdf'])
            <div style="border: 1px solid #000; padding: 8px; margin-top: 4px;">
                <div style="font-size: 8pt;">
                    <strong>Document:</strong> {{ $doc['name'] }}<br>
                    <strong>Type:</strong> PDF Document<br>
                    <strong>Size:</strong> {{ $doc['size'] }}<br>
                    <strong>Pages:</strong> {{ $doc['embeddedData']['pages'] }}
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    @if(isset($documentsByType) && count($documentsByType) > 0)
        @foreach($documentsByType as $type => $docs)
            @if($type != 'national_id' && $type != 'payslip' && is_array($docs) && count($docs) > 0)
            <div style="margin: 12px 0; page-break-inside: avoid;">
                <div style="background-color: #8BC34A; color: white; padding: 2px 5px; font-weight: bold; font-size: 8pt; margin: 2px 0;">
                    {{ strtoupper(str_replace('_', ' ', $type)) }}
                </div>
                @foreach($docs as $doc)
                    @if(is_array($doc)) <!-- Defensive check -->
                        @if(isset($doc['embeddedData']) && is_array($doc['embeddedData']) && ($doc['embeddedData']['isImage'] ?? false))
                        <div style="border: 1px solid #000; padding: 8px; margin-top: 4px; text-align: center;">
                            <img src="{{ $doc['embeddedData']['data'] }}" alt="{{ $type }}" style="max-width: 90%; max-height: 500px;">
                            <div style="font-size: 7pt; margin-top: 4px; color: #666;">
                                {{ $doc['name'] ?? 'Document' }} ({{ $doc['size'] ?? '0 KB' }})
                            </div>
                        </div>
                        @elseif(isset($doc['embeddedData']) && is_array($doc['embeddedData']) && ($doc['embeddedData']['isPdf'] ?? false))
                        <div style="border: 1px solid #000; padding: 8px; margin-top: 4px;">
                            <div style="font-size: 8pt;">
                                <strong>Document:</strong> {{ $doc['name'] ?? 'Document' }}<br>
                                <strong>Type:</strong> PDF Document<br>
                                <strong>Size:</strong> {{ $doc['size'] ?? '0 KB' }}<br>
                                <strong>Pages:</strong> {{ $doc['embeddedData']['pages'] ?? 1 }}
                            </div>
                        </div>
                        @endif
                    @endif
                @endforeach
            </div>
            @endif
        @endforeach
    @endif

    <div style="margin-top: 20px; font-size: 7pt; text-align: center; color: #666;">
        <em>End of Supporting Documents</em>
    </div>
</div>
@endif
