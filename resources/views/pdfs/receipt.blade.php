<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $reference_code }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #047857;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #047857;
        }
        .receipt-title {
            font-size: 18px;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .total-section {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #d1fae5;
            color: #065f46;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">MICROBIZ ZIMBABWE</div>
        <div class="receipt-title">Official Payment Receipt</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Order Number:</span>
            <span>{{ $application_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Reference:</span>
            <span>{{ $reference_code }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span>{{ $payment_date }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="status-badge">PAID IN FULL</span>
        </div>
    </div>

    <div class="info-section">
        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">Customer Details</h3>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span>{{ $customer_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span>{{ $customer_phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">National ID:</span>
            <span>{{ $customer_id }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Description</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($line_items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td style="text-align: center;">{{ $item['quantity'] }}</td>
                <td style="text-align: right;">{{ $currency }} {{ number_format($item['price'], 2) }}</td>
                <td style="text-align: right;">{{ $currency }} {{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        TOTAL PAID: {{ $currency }} {{ number_format($amount, 2) }}
    </div>

    <div class="info-section" style="margin-top: 20px;">
        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">Payment Information</h3>
        <div class="info-row">
            <span class="info-label">Payment Method:</span>
            <span>{{ strtoupper($payment_method) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Transaction ID:</span>
            <span>{{ $transaction_id }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer-generated receipt. No signature is required.</p>
        <p>&copy; {{ date('Y') }} Microbiz Zimbabwe. All rights reserved.</p>
    </div>
</body>
</html>
