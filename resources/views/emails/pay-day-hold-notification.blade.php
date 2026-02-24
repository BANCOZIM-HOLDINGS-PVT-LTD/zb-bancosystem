<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Day Account Hold Notification</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 700px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1a5276, #2980b9); color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; opacity: 0.9; font-size: 14px; }
        .body { padding: 24px 32px; }
        .alert { background: #fef3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; }
        .alert strong { color: #856404; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px; }
        th { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px 8px; text-align: left; font-weight: 600; color: #495057; }
        td { border: 1px solid #dee2e6; padding: 8px; }
        tr:nth-child(even) { background: #f8f9fa; }
        .footer { padding: 16px 32px; background: #f8f9fa; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pay Day Account Hold Notification</h1>
            <p>{{ $date }}</p>
        </div>

        <div class="body">
            <div class="alert">
                <strong>⚠️ Action Required:</strong> The following clients have pay days approaching in 4 days. Please place account holds to ensure loan repayment collection.
            </div>

            <p><strong>{{ count($clients) }}</strong> client(s) require attention:</p>

            <table>
                <thead>
                    <tr>
                        <th>App #</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>ID Number</th>
                        <th>Loan Amt</th>
                        <th>Monthly</th>
                        <th>Employer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                    <tr>
                        <td>{{ $client['app_number'] }}</td>
                        <td>{{ $client['name'] }}</td>
                        <td>{{ $client['phone'] }}</td>
                        <td>{{ $client['id_number'] }}</td>
                        <td>${{ $client['loan_amount'] }}</td>
                        <td>${{ $client['monthly_payment'] }}</td>
                        <td>{{ $client['employer'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">
            This is an automated notification from ZB BancoSystem. &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
