<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { background-color: #004a99; color: white; padding: 10px 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { padding: 20px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .ref-code { font-weight: bold; color: #004a99; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BancoSystem</h1>
        </div>
        <div class="content">
            <h2>Application Received</h2>
            <p>Thank you for your application with BancoSystem.</p>
            <p>Your application reference code is: <span class="ref-code">{{ $application->reference_code ?? $application->session_id }}</span></p>
            <p>We have received your details and our team is currently reviewing them. You can track your application status at any time using the link below:</p>
            <p>
                <a href="{{ url('/application/status?ref=' . ($application->reference_code ?? $application->session_id)) }}" 
                   style="background-color: #004a99; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Track Application Status
                </a>
            </p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} BancoSystem. All rights reserved.
        </div>
    </div>
</body>
</html>
