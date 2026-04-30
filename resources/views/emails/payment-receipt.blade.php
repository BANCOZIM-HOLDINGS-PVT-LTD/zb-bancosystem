<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { background-color: #004a99; color: white; padding: 10px 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { padding: 20px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .receipt-table th, .receipt-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .receipt-table th { background-color: #f9f9f9; }
        .total { font-weight: bold; font-size: 1.2em; color: #004a99; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BancoSystem</h1>
        </div>
        <div class="content">
            <h2>Payment Receipt</h2>
            <p>Dear Customer, thank you for your payment. Below are the transaction details for your reference.</p>
            
            <table class="receipt-table">
                <tr>
                    <th>Reference Code</th>
                    <td>{{ $application->reference_code ?? $application->session_id }}</td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td>{{ $paymentData['transaction_id'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td>{{ $paymentData['method'] ?? 'Paynow' }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ now()->format('d M Y, H:i') }}</td>
                </tr>
                <tr class="total">
                    <th>Amount Paid</th>
                    <td>{{ number_format($paymentData['amount'] ?? 0, 2) }} {{ $paymentData['currency'] ?? 'USD' }}</td>
                </tr>
            </table>

            <p>Your payment has been successfully processed and your application has been updated.</p>
            <p>
                <a href="{{ url('/application/status?ref=' . ($application->reference_code ?? $application->session_id)) }}" 
                   style="background-color: #004a99; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    View Application Status
                </a>
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} BancoSystem. All rights reserved.
        </div>
    </div>
</body>
</html>
