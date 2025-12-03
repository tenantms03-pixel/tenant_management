<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Active Tenants Report</title>
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
        .section-title { 
            background: #2563eb; 
            color: white; 
            padding: 10px; 
            margin: 20px 0 10px 0;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
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
        <h1>Active Tenants Report</h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>


    {{-- APPROVED TENANTS --}}
    <div class="section">
        <h3 class="section-title">Active Tenants</h3>
        @if($approvedTenants->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Unit Type</th>
                    <th>Employment Status</th>
                    <th>Source of Income</th>
                    <th>Emergency Name</th>
                    <th>Emergency Number</th>
                    <th>Lease Start</th>
                    <th>Lease End</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvedTenants as $tenant)
                    @php
                        $app = $tenant->tenantApplication;
                        $activeLeases = $tenant->leases->where('lea_status', 'active');
                        
                        // If no active leases, try to get any lease
                        if ($activeLeases->isEmpty()) {
                            $activeLeases = collect([$tenant->leases->first()])->filter();
                        }
                        
                        // Build unit type display with all units
                        $unitTypeDisplays = [];
                        $leaseStartDates = [];
                        $leaseEndDates = [];
                        
                        foreach ($activeLeases as $lease) {
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
                            
                            // Collect lease dates
                            if ($lease->lea_start_date) {
                                $leaseStartDates[] = $lease->lea_start_date;
                            }
                            if ($lease->lea_end_date) {
                                $leaseEndDates[] = $lease->lea_end_date;
                            }
                        }
                        
                        // Combine all units with line breaks (one unit per line)
                        $allUnitsDisplay = !empty($unitTypeDisplays) ? implode('<br>', $unitTypeDisplays) : ($app->unit_type ?? 'N/A');
                        
                        // Get earliest start date and latest end date
                        $earliestStart = !empty($leaseStartDates) ? min($leaseStartDates) : null;
                        $latestEnd = !empty($leaseEndDates) ? max($leaseEndDates) : null;
                    @endphp
                    <tr>
                        <td>{{ $tenant->name }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td>{{ $app->contact_number ?? 'N/A' }}</td>
                        <td>{!! $allUnitsDisplay !!}</td>
                        <td>{{ $app->employment_status ?? 'N/A' }}</td>
                        <td>{{ $app->source_of_income ?? 'N/A' }}</td>
                        <td>{{ $app->emergency_name ?? 'N/A' }}</td>
                        <td>{{ $app->emergency_number ?? 'N/A' }}</td>
                        <td>{{ $earliestStart ? \Carbon\Carbon::parse($earliestStart)->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $latestEnd ? \Carbon\Carbon::parse($latestEnd)->format('Y-m-d') : 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="no-data">No approved tenants found.</p>
        @endif
    </div>
    
    <div class="footer">
        <p>Property Management System | Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
