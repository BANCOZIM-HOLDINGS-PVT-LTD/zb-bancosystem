<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { background-color: #004a99; color: white; padding: 10px 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { padding: 20px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .status { font-weight: bold; color: #d9534f; text-transform: uppercase; }
        .status.approved { color: #5cb85c; }
        .status.completed { color: #5bc0de; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BancoSystem</h1>
        </div>
        <div class="content">
            <h2>Application Update</h2>
            <p>Your application (Ref: {{ $application->reference_code ?? $application->session_id }}) has been updated.</p>
            <p>New Status: <span class="status {{ $application->current_step }}">{{ str_replace('_', ' ', $application->current_step) }}</span></p>
            
            @if($application->current_step == 'approved')
                <p>Congratulations! Your application has been approved. We will proceed with the next steps shortly.</p>
            @elseif($application->current_step == 'rejected')
                <p>Your application requires additional review or has been declined. Please log in to view specific feedback.</p>
            @elseif($application->current_step == 'completed')
                <p>Great news! Your application process is complete and funds/products are being finalized.</p>
            @endif

            <p>For more details, please log in to the status tracking portal:</p>
            <p>
                <a href="{{ url('/application/status?ref=' . ($application->reference_code ?? $application->session_id)) }}" 
                   style="background-color: #004a99; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    View Application Details
                </a>
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} BancoSystem. All rights reserved.
        </div>
    </div>
</body>
</html>
