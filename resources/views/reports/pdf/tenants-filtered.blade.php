<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenant Report ({{ ucfirst($filter) }})</title>
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
        td.status { 
            font-weight: bold; 
            text-align: center;
            padding: 6px;
        }
        .status-approved { 
            background-color: #d1fae5; 
            color: #065f46; 
            border-radius: 4px;
        }
        .status-pending { 
            background-color: #fef3c7; 
            color: #92400e; 
            border-radius: 4px;
        }
        .status-rejected { 
            background-color: #fee2e2; 
            color: #991b1b; 
            border-radius: 4px;
        }
        .no-data { 
            text-align: center; 
            color: #9ca3af; 
            font-style: italic;
            padding: 20px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
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
        <h1>Tenant Report - {{ ucfirst($filter) }}</h1>
        <p><strong>Generated:</strong> {{ \Carbon\Carbon::parse($generatedAt)->format('F d, Y \a\t h:i A') }}</p>
    </div>

    @if($tenants->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Unit Type</th>
                    <th>Employment Status</th>
                    <th>Source of Income</th>
                    <th>Emergency Contact</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tenants as $tenant)
                    @php 
                        $app = $tenant->tenantApplication;
                        $lease = $tenant->leases->first();
                        $unitType = $app->unit_type ?? 'N/A';
                        
                        // Get room number and bed number from lease if available, otherwise from application
                        $roomNo = $lease->room_no ?? $app->room_no ?? 'N/A';
                        $bedNumber = $lease->bed_number ?? $app->bed_number ?? null;
                        
                        // Format unit type with room number and bed number (if Bed-Spacer)
                        if ($unitType === 'Bed-Spacer' && $bedNumber) {
                            $unitTypeDisplay = $unitType . ' - ' . $roomNo . ' - Bed ' . $bedNumber;
                        } elseif ($roomNo !== 'N/A') {
                            $unitTypeDisplay = $unitType . ' - ' . $roomNo;
                        } else {
                            $unitTypeDisplay = $unitType;
                        }
                        
                        $statusClass = match($tenant->status) {
                            'approved' => 'status-approved',
                            'pending' => 'status-pending',
                            'rejected' => 'status-rejected',
                            default => ''
                        };
                    @endphp
                    <tr>
                        <td>{{ $tenant->name }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td>{{ $unitTypeDisplay }}</td>
                        <td>{{ $app->employment_status ?? 'N/A' }}</td>
                        <td>{{ $app->source_of_income ?? 'N/A' }}</td>
                        <td>{{ $app->emergency_name ?? 'N/A' }} ({{ $app->emergency_number ?? 'N/A' }})</td>
                        <td class="status {{ $statusClass }}">{{ ucfirst($tenant->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">No tenants found for this filter.</p>
    @endif
    
    <div class="footer">
        <p>Property Management System | Generated on {{ \Carbon\Carbon::parse($generatedAt)->format('F d, Y') }}</p>
    </div>
</body>
</html>
