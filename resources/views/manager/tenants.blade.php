@extends('layouts.managerdashboardlayout')

@section('title', 'Manage Tenants')

@section('content')
<style>
    body {
        background: #f3f5f9;
        font-family: 'Inter', sans-serif;
    }

    h3.fw-bold {
        background: #000000 ;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Card Styles */
    .tenant-card {
        border: none;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .tenant-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .tenant-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 6px;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }

    .tenant-approved::before { background: #198754; }
    .tenant-pending::before { background: #ffc107; }
    .tenant-rejected::before { background: #dc3545; }

    .status-badge {
        border-radius: 50px;
        padding: 5px 14px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-approved { background-color: #d4edda; color: #155724; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-rejected { background-color: #f8d7da; color: #721c24; }

    /* Filter Bar */
    .filter-bar {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        padding: 16px 24px;
    }

    .custom-select, .form-control {
        border-radius: 12px !important;
        box-shadow: none !important;
        border: 1.5px solid #dee2e6 !important;
    }

    .custom-select:focus, .form-control:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.15rem rgba(13,110,253,0.25) !important;
    }

    .btn {
        border-radius: 10px !important;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 0;
        opacity: 0.8;
    }

    .empty-state img {
        width: 180px;
        opacity: 0.8;
    }

    /* Modal */
    .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .modal-header {
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        color: white;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }

    .modal-footer {
        border-top: none;
        background-color: #f8f9fa;
    }

    .modal .btn-close {
        filter: invert(1);
    }

    /* Table Styles */
    .table thead th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .table tbody tr {
        border-bottom: 1px solid #e9ecef;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        font-weight: 500;
    }

    .bg-primary-subtle {
        background-color: #cfe2ff !important;
    }

    .bg-success-subtle {
        background-color: #d1e7dd !important;
    }

    .bg-warning-subtle {
        background-color: #fff3cd !important;
    }

    .bg-danger-subtle {
        background-color: #f8d7da !important;
    }

    .bg-secondary-subtle {
        background-color: #e2e3e5 !important;
    }

    /* Dropdown Menu Fixes */
    .card-body {
        overflow: visible !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    .table {
        overflow: visible !important;
    }

    .table tbody {
        overflow: visible !important;
    }

    .table td {
        position: relative;
        overflow: visible !important;
    }

    .table tr {
        overflow: visible !important;
    }

    .dropdown {
        position: relative !important;
    }

    .dropdown-menu {
        z-index: 1050 !important;
        position: absolute !important;
        right: 0 !important;
        left: auto !important;
        top: 100% !important;
        margin-top: 0.125rem;
        transform: none !important;
        will-change: auto !important;
    }

    .dropdown-menu.show {
        display: block !important;
    }

    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
        }
    }
</style>

<div class="container mb-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-1 fw-bold">Tenant Management</h3>
    </div>

    <!-- Filter + Search -->
    <div class="filter-bar mb-4">
        <form method="GET" action="{{ route('manager.tenants') }}" class="d-flex flex-wrap align-items-center gap-3 mb-0" >
            <!-- Filter & Export Section -->
            <div class="d-flex flex-wrap align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm border w-100">

                <!-- Filter by Status -->
                <div class="d-flex align-items-center gap-2">
                    <label for="filter" class="fw-semibold text-secondary mb-0">
                        <i class="bi bi-funnel-fill text-primary me-1"></i> Status
                    </label>
                    <div class="position-relative">
                        <select name="filter" id="filter"
                            class="form-select form-select-sm rounded-pill ps-3 pe-5 shadow-sm border-primary-subtle"
                            style="min-width: 180px;" onchange="this.form.submit()">
                            <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All Tenants</option>
                            <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $filter === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ $filter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <i class="bi bi-chevron-down position-absolute top-50 end-0 translate-middle-y me-3 text-muted small"></i>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <label for="search" class="fw-semibold text-secondary mb-0">
                        <i class="bi bi-search text-primary me-1"></i> Tenant
                    </label>
                    <div class="input-group input-group-sm" style="max-width: 300px;">
                        <input type="text" name="search" id="search"
                            class="form-control rounded-start-pill border-end-0 shadow-sm"
                            placeholder="Search tenant name..." value="{{ $search }}">
                            &nbsp;
                        <button class="btn btn-primary rounded-end-pill px-3" style="border: 2px solid #01017c; color: #01017c; background: transparent;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Export PDF Button -->
                <div class="ms-auto">
                    <a href="{{ route('manager.tenants.export', ['filter' => $filter, 'search' => $search]) }}"
                        target="_blank"
                        class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2 rounded-pill shadow-sm px-3 py-2"
                        style="border: 2px solid #01017c; color: #01017c; background: transparent;">
                        <i class="bi bi-file-earmark-pdf-fill fs-6"></i>
                        <span class="fw-semibold">Export PDF</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <style>
        /* Improved dropdown icon positioning */
        .dropdown-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #6c757d;
        }

        /* Responsive filter bar */
        @media (max-width: 768px) {
            .filter-bar form > div {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .filter-bar .input-group {
                width: 100% !important;
            }

            .filter-bar .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>


    <!-- Tenant Table -->
   <div class="card border-0 shadow-sm" style="overflow: visible;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 20%;">Tenant</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 20%;">Email</th>
                        <th style="width: 20%;">Deposit Balance</th>
                        <th style="width: 12%;">Rent Balance</th>
                        <th style="width: 12%;">Utility Balance</th>
                        <th style="width: 20%;">Leases</th>
                        <th style="width: 6%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filteredTenants as $tenant)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold text-dark">{{ $tenant->name }}</div>
                            </td>
                            <td>
                                <span class="status-badge status-{{ $tenant->status }}">
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </td>
                            <td>
                                @if($tenant->status !== 'rejected')
                                    <span class="text-muted">{{ $tenant->email }}</span>
                                @else
                                    <span class="text-danger">Rejected</span>
                                @endif
                            </td>
                            <td>
                                @if($tenant->status !== 'rejected')
                                    <span class="fw-semibold">₱{{ number_format($tenant->deposit_amount ?? 0, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($tenant->status !== 'rejected')
                                    <span class="fw-semibold">₱{{ number_format($tenant->rent_balance ?? 0, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($tenant->status !== 'rejected')
                                    <span class="fw-semibold">₱{{ number_format($tenant->total_utility_balance ?? 0, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($tenant->leases && $tenant->leases->isNotEmpty())
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($tenant->leases as $lease)
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ $lease->room_no ?? 'Room' }} - {{ $lease->unit->type ?? 'Unit' }}
                                                </span>
                                                <span class="badge
                                                    @if(in_array(strtolower($lease->lea_status ?? ''), ['active', 'approved'])) bg-success-subtle text-success
                                                    @elseif(in_array(strtolower($lease->lea_status ?? ''), ['pending'])) bg-warning-subtle text-warning
                                                    @elseif(in_array(strtolower($lease->lea_status ?? ''), ['terminated', 'ended', 'rejected', 'voided'])) bg-danger-subtle text-danger
                                                    @else bg-secondary-subtle text-secondary
                                                    @endif">
                                                    {{ ucfirst($lease->lea_status ?? 'N/A') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($tenant->tenantApplication)
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            @php
                                                $app = $tenant->tenantApplication;
                                                $unit = $app->unit ?? null;
                                                $roomNo = $app->room_no ?? ($unit->room_no ?? 'N/A');
                                                $unitType = $app->unit_type ?? ($unit->type ?? 'N/A');
                                            @endphp
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ $roomNo }} - {{ $unitType }}
                                            </span>
                                            <span class="badge
                                                @if($tenant->status === 'approved') bg-success-subtle text-success
                                                @elseif($tenant->status === 'pending') bg-warning-subtle text-warning
                                                @elseif($tenant->status === 'rejected') bg-danger-subtle text-danger
                                                @else bg-secondary-subtle text-secondary
                                                @endif">
                                                {{ ucfirst($tenant->status ?? 'Pending') }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">No leases</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="dropdown"
                                        data-bs-display="static"
                                        aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" >
                                        @if($tenant->status !== 'rejected')
                                            <!-- View ID -->
                                            <li>
                                                @if($tenant->tenantApplication && $tenant->tenantApplication->valid_id_path && $tenant->tenantApplication->id_picture_path)
                                                    <a class="dropdown-item" href="{{ route('manager.tenants.viewIds', $tenant->id) }}" target="_blank">
                                                        <i class="bi bi-card-image me-2"></i> View ID
                                                    </a>
                                                @else
                                                    <span class="dropdown-item-text text-muted">
                                                        <i class="bi bi-card-image me-2"></i> View ID <small>(Not Available)</small>
                                                    </span>
                                                @endif
                                            </li>

                                            <!-- Proof of Billing History -->
                                            <li>
                                                <a class="dropdown-item"
                                                    href="#"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#utilityProofModal{{ $tenant->id }}">
                                                    <i class="bi bi-receipt me-2"></i> Proof of Billing History
                                                </a>
                                            </li>

                                            <!-- Accept and Reject for Pending Applications -->
                                            @if($tenant->status === 'pending')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('manager.tenant.approve', $tenant->id) }}" method="POST" class="d-inline-block w-100">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success fw-semibold w-100 text-start" onclick="return confirm('Accept {{ $tenant->name }}?')">
                                                            <i class="bi bi-check-circle-fill me-2"></i> Accept
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger fw-semibold"
                                                        href="#"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectTenantModal{{ $tenant->id }}">
                                                        <i class="bi bi-x-circle-fill me-2"></i> Reject
                                                    </a>
                                                </li>
                                            @endif

                                            <!-- Move Out Actions -->
                                            @if($tenant->leases && $tenant->leases->isNotEmpty())
                                                @php
                                                    $activeLeases = $tenant->leases->filter(fn($lease) => in_array(strtolower($lease->lea_status), ['active', 'pending']));
                                                @endphp

                                                @if($activeLeases->isNotEmpty())
                                                    <li><hr class="dropdown-divider"></li>

                                                    @foreach($activeLeases as $lease)
                                                        @if($lease->move_out_requested)
                                                                <button type="button" class="dropdown-item text-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#moveOutReasonModal{{ $lease->id }}"> <i class="bi bi-exclamation-circle me-2"></i> Move Out Requested <span class="text-info">({{ $lease->unit->room_no }})</span> </button>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                        @else
                                            <!-- Rejected tenant actions -->
                                            <li>
                                                <span class="dropdown-item-text text-muted">
                                                    <i class="bi bi-info-circle me-2"></i> Reason: {{ $tenant->rejection_reason ?? 'N/A' }}
                                                </span>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="#"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#tenantDetailsModal{{ $tenant->id }}">
                                                    <i class="bi bi-person-circle me-2"></i> View Details
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <img src="https://cdn-icons-png.flaticon.com/512/4076/4076504.png" alt="No tenants" style="width: 120px; opacity: 0.5;">
                                    <p class="text-muted mt-3 mb-0">No tenants found for this filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Move Out Reason Modal (for already requested) -->
@foreach($filteredTenants as $tenant)
    @if($tenant->leases && $tenant->leases->isNotEmpty())
        @foreach($tenant->leases as $lease)
            @if($lease->move_out_requested)
            <div class="modal fade" id="moveOutReasonModal{{ $lease->id }}" tabindex="-1" aria-labelledby="moveOutReasonModalLabel{{ $lease->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Move Out Request - Room {{ $lease->room_no }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Reason Provided by Tenant:</strong></p>
                            <p>{{ $lease->move_out_reason ?? 'No reason provided.' }}</p>

                            {{-- Reject Reason --}}
                            <form id="rejectMoveOutForm{{ $lease->id }}" method="POST" action="{{ route('manager.leases.rejectMoveOut', $lease->id) }}">
                                @csrf
                                <div class="mb-3 mt-3">
                                    <label for="rejectReason{{ $lease->id }}" class="form-label">Reason for Rejecting Move Out</label>
                                    <textarea name="reason" id="rejectReason{{ $lease->id }}" class="form-control" rows="3" placeholder="Enter reason for rejecting..." required></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            {{-- Approve Form --}}
                            <form method="POST" action="{{ route('manager.leases.approveMoveOut', $lease->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">Approve</button>
                            </form>

                            {{-- Reject Submit Button --}}
                            <button type="submit" form="rejectMoveOutForm{{ $lease->id }}" class="btn btn-danger">Reject</button>

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    @endif
@endforeach


<!-- Tenant Details Modals -->
@foreach($filteredTenants as $tenant)
    <!-- Tenant Details Modal -->
    <div class="modal fade" id="tenantDetailsModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>Tenant Details - {{ $tenant->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Personal Information</h6>
                            <p><strong>Full Name:</strong> {{ $tenant->name }}</p>
                            <p><strong>Email:</strong> {{ $tenant->email }}</p>
                            <p><strong>Contact:</strong> {{ $tenant->contact_number ?? 'N/A' }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge status-badge status-{{ $tenant->status }}">
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Financial Information</h6>
                            <p><strong>Rent Balance:</strong> <span class="fw-semibold text-danger">₱{{ number_format($tenant->rent_balance ?? 0, 2) }}</span></p>
                            <p><strong>Utility Balance:</strong> <span class="fw-semibold text-danger">₱{{ number_format($tenant->total_utility_balance ?? 0, 2) }}</span></p>
                            <p><strong>Deposit Amount:</strong> ₱{{ number_format($tenant->deposit_amount ?? 0, 2) }}</p>
                            <p><strong>User Credit:</strong> ₱{{ number_format($tenant->user_credit ?? 0, 2) }}</p>
                        </div>
                        @if($tenant->tenantApplication)
                            <div class="col-12">
                                <hr>
                                <h6 class="text-muted mb-2">Application Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Unit Type:</strong> {{ $tenant->tenantApplication->unit_type ?? 'N/A' }}</p>
                                        <p><strong>Application Date:</strong> {{ $tenant->created_at->format('M d, Y') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Full Name (Application):</strong> {{ $tenant->tenantApplication->full_name ?? 'N/A' }}</p>
                                        @if($tenant->rejection_reason)
                                            <p><strong>Rejection Reason:</strong> <span class="text-danger">{{ $tenant->rejection_reason }}</span></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Payments Modal -->
    <div class="modal fade" id="tenantPaymentsModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Payment History - {{ $tenant->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $payments = $tenant->payments()->with('lease.unit')->orderBy('created_at', 'desc')->get();
                    @endphp
                    @if($payments->isEmpty())
                        <p class="text-muted text-center py-4">No payment records found.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment For</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Lease/Unit</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($payment->pay_date ?? $payment->created_at)->format('M d, Y') }}</td>
                                            <td>{{ $payment->payment_for ?? 'N/A' }}</td>
                                            <td class="fw-semibold">₱{{ number_format($payment->pay_amount, 2) }}</td>
                                            <td>{{ ucfirst($payment->pay_method ?? 'N/A') }}</td>
                                            <td>
                                                @if($payment->lease)
                                                    {{ $payment->lease->room_no ?? 'N/A' }} - {{ $payment->lease->unit->type ?? 'Unit' }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $payment->pay_status === 'Accepted' ? 'success' : ($payment->pay_status === 'Pending' ? 'warning text-dark' : 'secondary') }}">
                                                    {{ $payment->pay_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Leases Modal -->
    <div class="modal fade" id="tenantLeasesModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-house-door me-2"></i>Lease Information - {{ $tenant->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($tenant->leases && $tenant->leases->isNotEmpty())
                        @foreach($tenant->leases as $lease)
                            <div class="card mb-3 {{ $lease->move_out_requested ? 'border-warning' : '' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="bi bi-door-open me-2"></i>
                                                {{ $lease->room_no ?? 'Room' }} - {{ $lease->unit->type ?? 'Unit' }}
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                @if($lease->bed_number)
                                                    Bed {{ $lease->bed_number }} ·
                                                @endif
                                                Status: <span class="badge bg-{{ $lease->lea_status === 'active' ? 'success' : ($lease->lea_status === 'pending' ? 'warning text-dark' : 'secondary') }}">{{ ucfirst($lease->lea_status) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-6">
                                            <small class="text-muted">Start Date:</small>
                                            <p class="mb-0">{{ $lease->lea_start_date ? \Carbon\Carbon::parse($lease->lea_start_date)->format('M d, Y') : 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">End Date:</small>
                                            <p class="mb-0">{{ $lease->lea_end_date ? \Carbon\Carbon::parse($lease->lea_end_date)->format('M d, Y') : 'N/A' }}</p>
                                        </div>
                                        @if($lease->rent_balance || $lease->utility_balance)
                                            <div class="col-md-6">
                                                <small class="text-muted">Rent Balance:</small>
                                                <p class="mb-0 fw-semibold">₱{{ number_format($lease->rent_balance ?? 0, 2) }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Utility Balance:</small>
                                                <p class="mb-0 fw-semibold">₱{{ number_format($lease->utility_balance ?? 0, 2) }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    @if($lease->move_out_requested)
                                        <div class="alert alert-warning mt-2 mb-0">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Move Out Requested
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-4">No lease records found.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Reject Modals -->
@foreach($filteredTenants as $tenant)
    @if($tenant->status === 'pending')
        <div class="modal fade" id="rejectTenantModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('manager.tenant.reject') }}">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-x-circle"></i> Reject Tenant</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Please provide a reason for rejecting <strong>{{ $tenant->name }}</strong>:</p>
                            <div class="mb-3">
                                <label for="rejection_reason{{ $tenant->id }}" class="form-label">Reason</label>
                                <select name="rejection_reason" id="rejection_reason{{ $tenant->id }}" class="form-select" required>
                                    <option value="">Select reason...</option>
                                    <option value="Incomplete application">Incomplete application</option>
                                    <option value="Failed background check">Failed background check</option>
                                    <option value="Credit score too low">Credit score too low</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-light" style="border: 2px solid #01017c; color: #01017c; background: transparent;" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger" style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">Confirm Reject</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

@foreach($filteredTenants as $tenant)
    <div class="modal fade" id="utilityProofModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Proof of Billing &mdash; {{ $tenant->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($tenant->utilityProofs->isEmpty())
                        <p class="text-muted mb-0">No proof of billing records have been uploaded yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Billing Month</th>
                                        <th>Utility Balance (₱)</th>
                                        <th>Uploaded At</th>
                                        <th>Proof</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tenant->utilityProofs as $proof)
                                        <tr>
                                            <td>{{ $proof->billing_month ?? '—' }}</td>
                                            <td>₱{{ number_format($tenant->utility_balance, 2) }}</td>
                                            <td>{{ $proof->created_at->format('M d, Y h:i A') }}</td>
                                            <td>
                                                <a href="{{ asset('storage/'.$proof->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns manually to ensure they work
    var dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');

    dropdownElementList.forEach(function(dropdownToggleEl) {
        // Check if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            try {
                var dropdown = new bootstrap.Dropdown(dropdownToggleEl, {
                    boundary: document.body,
                    popperConfig: {
                        modifiers: [
                            {
                                name: 'preventOverflow',
                                options: {
                                    boundary: document.body,
                                    rootBoundary: 'viewport'
                                }
                            },
                            {
                                name: 'flip',
                                options: {
                                    boundary: document.body,
                                    rootBoundary: 'viewport'
                                }
                            }
                        ]
                    }
                });
            } catch(e) {
                console.error('Dropdown initialization error:', e);
            }
        }
    });

    // Also add click event as fallback with better positioning
    dropdownElementList.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            var dropdown = this.closest('.dropdown');
            var dropdownMenu = dropdown ? dropdown.querySelector('.dropdown-menu') : null;

            if (dropdownMenu) {
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                    }
                });

                // Toggle current dropdown
                var isShown = dropdownMenu.classList.contains('show');

                if (!isShown) {
                    dropdownMenu.classList.add('show');

                    // Ensure dropdown is visible - adjust position if needed
                    setTimeout(function() {
                        var rect = dropdownMenu.getBoundingClientRect();
                        var viewportHeight = window.innerHeight;
                        var viewportWidth = window.innerWidth;

                        // If dropdown goes below viewport, adjust position
                        if (rect.bottom > viewportHeight) {
                            var overflow = rect.bottom - viewportHeight;
                            dropdownMenu.style.top = 'auto';
                            dropdownMenu.style.bottom = '100%';
                            dropdownMenu.style.marginTop = '0';
                            dropdownMenu.style.marginBottom = '0.125rem';
                        }

                        // If dropdown goes to the right of viewport, adjust position
                        if (rect.right > viewportWidth) {
                            dropdownMenu.style.right = '0';
                            dropdownMenu.style.left = 'auto';
                        }
                    }, 10);
                } else {
                    dropdownMenu.classList.remove('show');
                }
            }
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
            });
        }
    });
});
</script>
@endsection
