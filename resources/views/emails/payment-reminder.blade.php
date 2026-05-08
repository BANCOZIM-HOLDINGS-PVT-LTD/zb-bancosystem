<p>Hello {{ data_get($application->form_data, 'formResponses.firstName', 'there') }},</p>

<p>Your application payment is still pending. You can continue here:</p>

<p><a href="{{ $resumeLink }}">{{ $resumeLink }}</a></p>

<p>Reference: {{ $application->reference_code ?? $application->session_id }}</p>
