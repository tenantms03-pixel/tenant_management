<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment History Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            color: #333;
            padding: 20px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }
        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .header p {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            font-size: 10px;
        }
        th, td { 
            border: 1px solid #e5e7eb; 
            padding: 8px 6px; 
            text-align: left;
            vertical-align: top;
        }
        th { 
            background-color: #2563eb;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #f3f4f6;
        }
        .amount {
            text-align: right;
            font-weight: bold;
            color: #059669;
        }
        .status {
            text-align: center;
            font-weight: bold;
        }
        .status-paid {
            color: #059669;
        }
        .status-pending {
            color: #d97706;
        }
        .status-overdue {
            color: #dc2626;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment History Report</h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Reference #</th>
                <th>Tenant</th>
                <th>Unit</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Purpose</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->reference_number }}</td>
                <td>{{ $payment->tenant->name ?? 'N/A' }}</td>
                <td>
                    @if($payment->lease && $payment->lease->unit)
                        {{ $payment->lease->unit->room_no ?? 'N/A' }}
                    @else
                        N/A
                    @endif
                </td>
                <td class="amount">₱{{ number_format($payment->pay_amount, 2) }}</td>
                <td>{{ $payment->pay_date?->format('M d, Y') ?? 'N/A' }}</td>
                <td>{{ ucfirst($payment->payment_for) }}</td>
                <td class="status status-{{ strtolower($payment->pay_status) }}">{{ ucfirst($payment->pay_status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Property Management System | Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
