@extends('layouts.tenantdashboardlayout')

@section('title', 'Tenant Dashboard')

<style>
    /* General */
    body {
        background: #f4f7fb;
        font-family: 'Inter', sans-serif;
    }

    h2, h3, h5, h6 {
        font-weight: 600;
    }

        /* Welcome Section */
    .welcome-title {
        font-size: 2.4rem;
        font-weight: 700;
        background: linear-gradient(135deg, #01017c, #1b2a6d);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .welcome-subtitle {
        font-size: 1.1rem;
        color: #273a8f;
        margin-top: 0.3rem;
    }


    /* Cards (Glassmorphism Style) */
    .card-modern {
        border: none;
        border-radius: 1.2rem;
        backdrop-filter: blur(12px);
        background: rgba(255, 255, 255, 0.85);
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .card-modern:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    }

    .card-modern .card-header {
        border-radius: 1.2rem 1.2rem 0 0;
        background: linear-gradient(135deg, #01017c, #273a8f);
        font-weight: 600;
        font-size: 1.1rem;
        color: #fff;
    }

    /* Tenant Info Card */
    .card-tenant-info {
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(15px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .card-tenant-info:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    .card-header-tenant {
        border-radius: 1.5rem 1.5rem 0 0;
        background: linear-gradient(135deg, #01017c, #273a8f);
        color: #fff;
        font-weight: 600;
        font-size: 1.1rem;
        padding: 0.85rem 1.25rem;
    }


    /* Summary Cards */
    .summary-card {
        border-radius: 1.2rem;
        padding: 2rem 1.5rem;
        color: #fff;
        font-weight: 500;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .summary-card:hover {
        transform: translateY(-6px);
        filter: brightness(1.05);
    }
    .summary-card h6 {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }
    .summary-card h3 {
        font-size: 1.8rem;
        font-weight: 700;
    }

    /* Badges */
    .badge-modern {
        padding: 0.45rem 0.9rem;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }


    /* Gradient Themes */
    .summary-payments { background: linear-gradient(135deg, #01017c, #2d3b9a); }
    .summary-pending  { background: linear-gradient(135deg, #273a8f, #6074c2); }
    .summary-total    { background: linear-gradient(135deg, #4b5db8, #7c8ed8); }

    /* Navy-Themed Gradients */
    .summary-payments {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
    }
    .summary-pending {
        background: linear-gradient(135deg, #273a8f, #6074c2);
    }
    .summary-total {
        background: linear-gradient(135deg, #4b5db8, #7c8ed8);
    }

    /* Icon tweaks */
    .card-body i {
        color: #01017c;
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }



</style>

@section('content')
<div class="container-fluid pt-4">

    {{-- Welcome --}}
    <div class="text-center mb-5">
        <h2 class="welcome-title">Welcome, {{ Auth::user()->name }} 👋</h2>
        <p class="welcome-subtitle">Here’s your tenant dashboard overview</p>
    </div>

    @php
        $user = Auth::user();
        $lease = $lease ?? null;
        $application = $application ?? null;
        $payments = $payments ?? collect();
        $requests = $requests ?? collect();
        
        // Get ONLY active leases of this user with unit information
        $activeLeases = $user->leases()
            ->where('lea_status', 'active')
            ->with('unit')
            ->get();

        // Build unit information with room numbers and unit types
        $unitInfo = [];
        foreach($activeLeases as $lease) {
            $roomNo = $lease->room_no ?? 'N/A';
            $unitType = $lease->unit ? $lease->unit->type : 'N/A';
            $unitInfo[] = [
                'room_no' => $roomNo,
                'unit_type' => $unitType,
                'full_name' => $unitType . ' - Room ' . $roomNo
            ];
        }
        
        // Extract room numbers for display
        $roomNumbers = $activeLeases->pluck('room_no')->filter()->values();
        $roomList = $roomNumbers->join(', ');
    @endphp

    {{-- Tenant Info --}}
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-header card-header-custom">
            <i class="bi bi-person-badge-fill me-2"></i> Your Information
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-circle fs-5 me-2"></i>
                        <p class="mb-0"><strong>Name:</strong> {{ $user->name }}</p>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-envelope-fill fs-5 me-2"></i>
                        <p class="mb-0"><strong>Email:</strong> {{ $user->email }}</p>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <p class="mb-0"><strong>Status:</strong>
                            @if($user->status === 'approved')
                                <span class="badge badge-modern bg-success">Approved ✅</span>
                            @elseif($user->status === 'pending')
                                <span class="badge badge-modern bg-warning text-dark">Pending ⏳</span>
                            @else
                                <span class="badge badge-modern bg-danger">Rejected ❌</span>
                            @endif
                        </p>
                    </div>
                    @if($activeLeases->count() > 0)
                        <div class="d-flex align-items-start">
                            <i class="bi bi-building fs-5 me-2 mt-1"></i>
                            <div>
                                <p class="mb-1">
                                    <strong>Unit (Room No):</strong> {{ $roomList ?: 'N/A' }}
                                </p>
                                @if(count($unitInfo) > 0)
                                    <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                                        @foreach($unitInfo as $info)
                                            {{ $info['full_name'] }}@if(!$loop->last), @endif
                                        @endforeach
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    @if($activeLeases->count() > 0)

                        {{-- Lease Start Dates --}}
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-event fs-5 me-2 text-primary"></i>
                            <p class="mb-0"><strong>Lease Start:</strong></p>
                        </div>
                        @foreach($activeLeases as $lease)
                            <p class="ms-4 mb-2">
                                <strong>Room {{ $lease->room_no }}:</strong>
                                {{ optional($lease->lea_start_date)->format('M d, Y') ?? 'N/A' }}
                            </p>
                        @endforeach

                        {{-- Lease End Dates --}}
                        <div class="d-flex align-items-center mb-3 mt-3">
                            <i class="bi bi-calendar-check fs-5 me-2 text-primary"></i>
                            <p class="mb-0"><strong>Lease End:</strong></p>
                        </div>
                        @foreach($activeLeases as $lease)
                            <p class="ms-4 mb-2">
                                <strong>Room {{ $lease->room_no }}:</strong>
                                {{ optional($lease->lea_end_date)->format('M d, Y') ?? 'N/A' }}
                            </p>
                        @endforeach

                    @else
                        <p class="text-muted">No active leases found.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="summary-card summary-payments text-center">
                <h6>Total Payments</h6>
                <h3>₱{{ number_format($payments->sum('pay_amount'), 2) }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card summary-pending text-center">
                <h6>Pending Requests</h6>
                <h3>{{ $requests->where('status','Pending')->count() }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card summary-total text-center">
                <h6>Total Requests</h6>
                <h3>{{ $requests->count() }}</h3>
            </div>
        </div>
    </div>

</div>
@endsection
