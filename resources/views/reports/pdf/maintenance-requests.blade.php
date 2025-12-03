<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Requests Report</title>
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
            text-align: center;
            vertical-align: top;
        }
        th { 
            background-color: #2563eb;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #f3f4f6;
        }
        .badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            color: #fff; 
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .high { background-color: #ef4444; }
        .mid { background-color: #f59e0b; }
        .low { background-color: #10b981; }
        .pending { background-color: #6b7280; }
        .accepted { background-color: #10b981; }
        .rejected { background-color: #ef4444; }
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
        <h1>Maintenance Requests Report</h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date Filed</th>
                <th>Unit Type</th>
                <th>Request</th>
                <th>Urgency</th>
                <th>Supposed Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $request)
            @php
                $unitType = $request->unit_type ?? null;
                $roomNo = $request->room_no ?? null;
                $bedNumber = null;
                
                // Try to get bed number from tenant's lease if available
                if ($request->tenant) {
                    $lease = $request->tenant->leases->first();
                    $bedNumber = $lease->bed_number ?? null;
                }
                
                // Format unit type with room number and bed number (if Bed-Spacer)
                if ($unitType && $roomNo) {
                    if ($unitType === 'Bed-Spacer' && $bedNumber) {
                        $unitTypeDisplay = $unitType . ' - ' . $roomNo . ' - Bed ' . $bedNumber;
                    } else {
                        $unitTypeDisplay = $unitType . ' - ' . $roomNo;
                    }
                } elseif ($unitType) {
                    $unitTypeDisplay = $unitType;
                } else {
                    $unitTypeDisplay = '-';
                }
                
                $urgencyClass = match($request->urgency) {
                    'high' => 'high',
                    'mid' => 'mid',
                    default => 'low',
                };
                $statusClass = match($request->status) {
                    'Pending' => 'pending',
                    'Accepted' => 'accepted',
                    'Rejected' => 'rejected',
                    default => 'pending',
                };
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y-m-d') }}</td>
                <td>{{ $unitTypeDisplay }}</td>
                <td>{{ ucfirst($request->description) }}</td>
                <td><span class="badge {{ $urgencyClass }}">{{ ucfirst($request->urgency) }}</span></td>
                <td>{{ \Carbon\Carbon::parse($request->supposed_date)->format('Y-m-d') }}</td>
                <td><span class="badge {{ $statusClass }}">{{ ucfirst($request->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Property Management System | Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
