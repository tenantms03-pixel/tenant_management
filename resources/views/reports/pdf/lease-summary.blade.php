<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lease Summary Report</title>
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
            margin-bottom: 8px;
            font-weight: bold;
        }
        .header p {
            font-size: 10px;
            color: #666;
            margin: 3px 0;
        }
        .header-info {
            background-color: #eff6ff;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #2563eb;
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
        .active { background-color: #10b981; }
        .terminated { background-color: #ef4444; }
        .pending { background-color: #6b7280; }
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
        <h1>Active Lease Summary Report</h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <div class="header-info">
            <p><strong>Total Active Leases:</strong> {{ $total ?? $data->count() }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tenant Name</th>
                <th>Email</th>
                <th>Unit Type</th>
                <th>Room No.</th>
                <th>Lease Start</th>
                <th>Lease End</th>
                <th>Monthly Rent (₱)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rowNumber = 0;
                $hasLeases = false;
            @endphp
            @foreach($data as $tenant)
                @php
                    // Get all active/pending leases for this tenant
                    $leases = $tenant->leases->whereIn('lea_status', ['active', 'pending']);
                    if ($leases->isEmpty()) {
                        $leases = collect([$tenant->leases->first()])->filter();
                    }
                    
                    $app = $tenant->tenantApplication;
                    
                    // Build unit type display with all units (one per line)
                    $unitTypeDisplays = [];
                    $roomNoDisplays = [];
                    $leaseStartDates = [];
                    $leaseEndDates = [];
                    
                    foreach ($leases as $lease) {
                        if (!$lease) continue;
                        
                        // Get unit type from lease->unit if available, otherwise from tenantApplication
                        $unitType = ($lease->unit && $lease->unit->type) ? $lease->unit->type : ($app->unit_type ?? 'N/A');
                        $roomNo = $lease->room_no ?? (($lease->unit && $lease->unit->room_no) ? $lease->unit->room_no : 'N/A');
                        $bedNumber = $lease->bed_number ?? null;
                        
                        // Format unit type with room number and bed number (if Bed-Spacer)
                        if ($unitType === 'Bed-Spacer' && $bedNumber) {
                            $unitTypeDisplay = $unitType . ' - ' . $roomNo . ' - Bed ' . $bedNumber;
                        } elseif ($roomNo !== 'N/A') {
                            $unitTypeDisplay = $unitType . ' - ' . $roomNo;
                        } else {
                            $unitTypeDisplay = $unitType;
                        }
                        
                        $unitTypeDisplays[] = $unitTypeDisplay;
                        $roomNoDisplays[] = $roomNo;
                        
                        // Collect lease dates and statuses
                        if ($lease->lea_start_date) {
                            $leaseStartDates[] = \Carbon\Carbon::parse($lease->lea_start_date)->format('Y-m-d');
                        }
                        if ($lease->lea_end_date) {
                            $leaseEndDates[] = \Carbon\Carbon::parse($lease->lea_end_date)->format('Y-m-d');
                        }
                    }
                    
                    // Combine all units with line breaks (one unit per line)
                    $allUnitsDisplay = !empty($unitTypeDisplays) ? implode('<br>', $unitTypeDisplays) : ($app->unit_type ?? 'N/A');
                    $allRoomNosDisplay = !empty($roomNoDisplays) ? implode('<br>', $roomNoDisplays) : 'N/A';
                    $allStartDatesDisplay = !empty($leaseStartDates) ? implode('<br>', $leaseStartDates) : 'N/A';
                    $allEndDatesDisplay = !empty($leaseEndDates) ? implode('<br>', $leaseEndDates) : 'N/A';
                    
                    // Only create row if there are leases
                    if (!empty($unitTypeDisplays)) {
                        $hasLeases = true;
                        $rowNumber++;
                    }
                @endphp
                @if(!empty($unitTypeDisplays))
                    <tr>
                        <td>{{ $rowNumber }}</td>
                        <td>{{ $tenant->name }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td>{!! $allUnitsDisplay !!}</td>
                        <td>{!! $allRoomNosDisplay !!}</td>
                        <td>{!! $allStartDatesDisplay !!}</td>
                        <td>{!! $allEndDatesDisplay !!}</td>
                        <td>P{{ number_format($tenant->rent_amount ?? 0, 2) }}</td>
                    </tr>
                @endif
            @endforeach
            @if(!$hasLeases)
                <tr>
                    <td colspan="8" class="no-data">No active leases found.</td>
                </tr>
            @endif
        </tbody>
    </table>
    
    <div class="footer">
        <p>Property Management System | Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
