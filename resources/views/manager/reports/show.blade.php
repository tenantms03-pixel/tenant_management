@extends('layouts.managerdashboardlayout')

@section('title',)
@section('page-title',)

@section('content')
<div class="content px-1">

    {{-- ---------------- PAGE HEADER ---------------- --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="fw-bold text-black-custom mb-2">
    <i class="bi bi-bar-chart-line me-2"></i> {{ $title }}
</h5>

        {{-- Export / Preview PDF button --}}
       <a href="{{ route('manager.reports.viewReportPdf', array_merge(['report' => $report], request()->all())) }}"
           target="_blank"
           class="btn btn-danger btn-sm shadow-sm d-flex align-items-center" style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export / Preview PDF
        </a>


    </div>
    <hr>

    {{-- ---------------- SUMMARY ---------------- --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    @if($report === 'payment-history')
                        <i class="bi bi-cash-coin text-success me-2"></i> Payment Summary
                    @elseif($report === 'maintenance-requests')
                        <i class="bi bi-tools text-warning me-2"></i> Maintenance Summary
                    @else
                        <i class="bi bi-list-ul text-secondary me-2"></i> Summary
                    @endif
                </h5>
                <p class="small text-muted mb-0">
                    @if($report === 'payment-history')
                        Showing: <strong>{{ $currentFilter ? ucfirst($currentFilter) : 'All Categories' }}</strong>
                    @elseif($report === 'maintenance-requests')
                        Status: <strong>{{ request('status') ?: 'All' }}</strong> |
                        Urgency: <strong>{{ request('urgency') ?: 'All' }}</strong>
                    @elseif($report === 'active-tenants')
                        Unit Type: <strong>{{ request('unit_type') ?: 'All' }}</strong> |
                        Employment Status: <strong>{{ request('employment_status') ?: 'All' }}</strong>
                    @else
                        Total Records
                    @endif
                </p>
            </div>
            <div class="text-end">
                @if($report === 'payment-history')
                    <span class="small text-muted">Total Paid</span>
                    <div class="fs-4 fw-bold text-success">₱{{ number_format($total ?? 0, 2) }}</div>
                @else
                    <span class="small text-muted">Total</span>
                    <div class="fs-4 fw-bold text-primary">{{ $total ?? $data->total() }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ---------------- FILTER SECTION ---------------- --}}
    @if(in_array($report, ['active-tenants', 'pending-tenants', 'rejected-tenants', 'maintenance-requests', 'payment-history', 'lease-summary']))
    <div class="filter-bar mb-4">
        <div class="bg-white p-3 rounded-4 shadow-sm border">
            <form method="GET" action="{{ route('manager.reports.show', ['report' => $report]) }}" class="row gy-3 gx-3 align-items-center">

                {{-- Payment History Filters --}}
                @if($report === 'payment-history')
                    <div class="col-md-8">
                        <label for="search" class="fw-semibold text-secondary mb-1">
                            <i class="bi bi-credit-card-fill text-primary me-1"></i> Search Payments
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search"
                                class="form-control rounded-start-pill border-end-0 shadow-sm"
                                placeholder="Search by Tenant, Amount, Date, Purpose, Status, Room, Reference #..."
                                value="{{ request('search') }}">&nbsp
                            <button class="btn btn-primary rounded-end-pill px-3"  style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <small class="text-muted">Search any column: Tenant name, Amount, Date (Jan 15, 2024 or 2024-01-15), Purpose (Rent/Utilities), Status, Room number, Reference number, etc.</small>
                    </div>

                {{-- Maintenance Filters --}}
                @elseif($report === 'maintenance-requests')
                    <div class="col-md-8">
                        <label for="search" class="fw-semibold text-secondary mb-1">
                            <i class="bi bi-tools text-primary me-1"></i> Search Maintenance Request
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search"
                                class="form-control rounded-start-pill border-end-0 shadow-sm"
                                placeholder="Search by issue, tenant name, status, or urgency..."
                                value="{{ request('search') }}">&nbsp
                            <button class="btn btn-primary rounded-end-pill px-3"  style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <small class="text-muted">Type a keyword and press Enter or click Search.</small>
                    </div>

                {{-- Active Tenants Filters --}}
                @elseif($report === 'active-tenants')
                    <div class="col-md-8">
                        <label for="search" class="fw-semibold text-secondary mb-1">
                            <i class="bi bi-people-fill text-primary me-1"></i> Search Active Tenants
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search"
                                class="form-control rounded-start-pill border-end-0 shadow-sm"
                                placeholder="Search by tenant name, unit type, or employment status..."
                                value="{{ request('search') }}">&nbsp
                            <button class="btn btn-primary rounded-end-pill px-3" style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <small class="text-muted">Type a keyword and press Enter or click Search.</small>
                    </div>

                {{-- Lease Summary Filters --}}
                @elseif($report === 'lease-summary')
                    <div class="col-md-8">
                        <label for="search" class="fw-semibold text-secondary mb-1">
                            <i class="bi bi-file-text-fill text-primary me-1"></i> Search Leases
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search"
                                class="form-control rounded-start-pill border-end-0 shadow-sm"
                                placeholder="Search by Tenant, Unit Type, Date, Status, Room, Term..."
                                value="{{ request('search') }}"> &nbsp
                            <button class="btn btn-primary rounded-end-pill px-3" style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <small class="text-muted">Search any column: Tenant name, Unit Type, Lease Start/End dates (Jan 15, 2024), Status (active/terminated), Room number, Lease Term, etc.</small>
                    </div>
                @endif

                {{-- Export Button (Optional, visible for relevant reports) --}}
                @if(in_array($report, ['payment-history', 'maintenance-requests', 'lease-summary', 'active-tenants']))
                    <div class="col-md-auto ms-auto d-flex align-items-center gap-2">

                        {{-- ✅ MAKE PAYMENT (ONLY FOR PAYMENT HISTORY) --}}
                        @if($report === 'payment-history')
                            <button type="button"
                                class="btn btn-success btn-sm d-flex align-items-center gap-2 rounded-pill shadow-sm px-3 py-2"
                                data-bs-toggle="modal"
                                data-bs-target="#makePaymentModal">
                                <i class="bi bi-cash-coin fs-6"></i>
                                <span class="fw-semibold">Make Payment</span>
                            </button>
                        @endif

                        {{-- ✅ EXPORT PDF --}}
                        <a href="{{ route('manager.reports.export', ['report' => $report, 'search' => request('search')]) }}"
                            target="_blank"
                            class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 rounded-pill shadow-sm px-3 py-2"
                            style="border: 2px solid #01017c; color: #01017c; background: transparent;">
                            <i class="bi bi-file-earmark-pdf-fill fs-6"></i>
                            <span class="fw-semibold">Export PDF</span>
                        </a>

                    </div>
                @endif

            </form>
        </div>
    </div>

    <style>
        .filter-bar label i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .filter-bar form {
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
    @endif


    <div class="modal fade" id="makePaymentModal" tabindex="-1" aria-labelledby="makePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('manager.payments.store') }}" class="modal-content rounded-4 shadow-lg border-0 p-3">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Make a Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-0">

                {{-- SELECT TENANT --}}
                <div class="form-floating mb-3">
                    <select name="tenant_id" id="tenant_id" class="form-select" required>
                        <option value="">Select Tenant</option>
                        @foreach($tenantsForPayment as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                    <label>Select Tenant</label>
                </div>

                {{-- SELECT ROOM / LEASE --}}
                <div class="form-floating mb-3">
                    <select name="lease_id" id="lease_id" class="form-select" required disabled>
                        <option value="">Select Room / Unit</option>
                    </select>
                    <label>Room / Unit</label>
                </div>

                {{-- PAYMENT FOR --}}
                <div class="form-floating mb-3">
                <select name="payment_for" id="payment_for" class="form-select" required disabled>
                    <option value="">Select Payment Type</option>

                    {{-- ✅ Deposit --}}
                    <option id="depositOption" value="Deposit" data-balance="0" class="d-none">
                        Deposit
                    </option>

                    {{-- ✅ Rent --}}
                    <option value="Rent" data-balance="0" data-lease-specific="true">
                        Rent (Select unit first)
                    </option>

                    {{-- ✅ Utilities --}}
                    <option value="Utilities" data-balance="0" data-lease-specific="true">
                        Utilities (Select unit first)
                    </option>
                </select>
                <label for="payment_for">Payment For</label>
            </div>


                {{-- AMOUNT --}}
                <div class="form-floating mb-3">
                    <input type="number" name="pay_amount" id="pay_amount" class="form-control" required disabled>
                    <label>Amount</label>
                </div>

                {{-- PAYMENT METHOD --}}
                <div class="form-floating mb-3">
                    <select name="pay_method" class="form-select" readonly>
                        <option value="Cash" selected>Cash</option>
                    </select>
                    <label>Payment Method</label>
                </div>

            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" class="btn btn-sm text-white" style="background-color:#01017c;">
                    + Submit Payment
                </button>
            </div>
        </form>
    </div>
</div>



    {{-- ---------------- DATA TABLE ---------------- --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-secondary">
                        {{-- HEADERS --}}
                        @if($report === 'payment-history')
                            <tr>
                                <th>Tenant</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        @elseif(in_array($report, ['active-tenants', 'pending-tenants', 'rejected-tenants']))
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Unit Type</th>
                                <th>Employment Status</th>
                                <th>Source of Income</th>
                                <th>Emergency Contact</th>
                                <th>Status</th>
                            </tr>
                        @elseif($report === 'lease-summary')
                            <tr>
                                <th>Tenant</th>
                                <th>Unit Number</th>
                                <th>Unit Type</th>
                                <th>Lease Start</th>
                                <th>Lease End</th>
                                <th>Lease Term</th>
                                <th>Status</th>
                            </tr>
                        @elseif($report === 'maintenance-requests')
                            <tr>
                                <th>Tenant</th>
                                <th>Unit</th>
                                <th>Description</th>
                                <th>Urgency</th>
                                <th>Supposed Date</th>
                                <th>Status</th>
                                <th>Issue</th>
                            </tr>
                        @endif
                    </thead>

                    <tbody>
                        @forelse($data as $item)
                            {{-- PAYMENT ROWS --}}
                            @if($report === 'payment-history')
                                <tr>
                                    <td>{{ $item->tenant->name ?? 'N/A' }}</td>
                                    <td><span class="fw-semibold">₱{{ number_format($item->pay_amount, 2) }}</span></td>
                                    <td>{{ $item->pay_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($item->payment_for) }}</td>
                                    <td>
                                        <span class="badge
                                            @if($item->pay_status === 'Accepted')
                                                bg-success
                                            @elseif($item->pay_status === 'Rejected')
                                                bg-danger
                                            @else
                                                bg-warning text-dark
                                            @endif
                                        ">
                                            {{ $item->pay_status }}
                                        </span>
                                        <!-- <form action="{{ route('manager.payments.updateStatus', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <select name="pay_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="Pending" {{ $item->pay_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="Accepted" {{ $item->pay_status === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                                            </select>
                                        </form> -->
                                    </td>
                                    <td>
                                        @if($item->proof)
                                            @php
                                                // Handle different proof path formats
                                                $proofPath = $item->proof;
                                                // If path already starts with storage/, use as is, otherwise prepend storage/
                                                if (strpos($proofPath, 'storage/') === 0) {
                                                    $imageUrl = asset($proofPath);
                                                } elseif (strpos($proofPath, 'http') === 0) {
                                                    $imageUrl = $proofPath; // Already a full URL
                                                } else {
                                                    $imageUrl = asset('storage/' . $proofPath);
                                                }
                                            @endphp
                                            <!-- <button type="button" class="btn btn-sm btn-outline-primary view-image-btn"
                                                    style="border: 2px solid #01017c; color: #01017c; background: transparent;"
                                                    data-bs-toggle="modal" data-bs-target="#viewImageModal"
                                                    data-image="{{ $imageUrl }}"
                                                    data-title="Payment Proof">
                                                <i class="bi bi-eye"></i> View Proof
                                            </button> -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary view-image-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewImageModal"
                                                data-image="{{ asset('storage/' . $item->proof) }}"
                                                data-title="Payment Proof"
                                                data-payment-id="{{ $item->id }}"
                                                data-status="{{ $item->pay_status }}">
                                            <i class="bi bi-eye"></i> View Proof
                                        </button>
                                        @else
                                            <span class="text-muted fst-italic">No Proof</span>
                                        @endif
                                    </td>
                                </tr>

                            {{-- TENANTS --}}
                            @elseif(in_array($report, ['active-tenants', 'pending-tenants', 'rejected-tenants']))
                                @php
                                    $app = $item->tenantApplication;
                                    $lease = $item->leases->first();
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
                                @endphp
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>{{ $app->contact_number ?? 'N/A' }}</td>
                                    <td>{{ $unitTypeDisplay }}</td>
                                    <td>{{ $app->employment_status ?? 'N/A' }}</td>
                                    <td>{{ $app->source_of_income ?? 'N/A' }}</td>
                                    <td>{{ $app->emergency_name ?? 'N/A' }} <br><small class="text-muted">{{ $app->emergency_number ?? '' }}</small></td>
                                    <td>
                                        <span class="badge bg-{{ $item->status === 'approved' ? 'success' : ($item->status === 'pending' ? 'warning text-dark' : 'danger') }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                </tr>

                            {{-- LEASES --}}
                            @elseif($report === 'lease-summary')
                            @php
                                $leases = $item->leases ?? collect();
                                $unit_number = $lease->unit_id ?? "N/A";
                                $unitType = $item->tenantApplication->unit_type ?? 'N/A';
                                $roomNo = $lease->room_no ?? 'N/A';
                                $bedNumber = $lease->bed_number ?? null;

                                // Format unit type with room number and bed number (if Bed-Spacer)
                                if ($unitType === 'Bed-Spacer' && $bedNumber) {
                                    $unitTypeDisplay = $unitType . ' - ' . $roomNo . ' - Bed ' . $bedNumber;
                                } elseif ($roomNo !== 'N/A') {
                                    $unitTypeDisplay = $unitType . ' - ' . $roomNo;
                                } else {
                                    $unitTypeDisplay = $unitType;
                                }
                            @endphp
                            <tr>
                                <td>{{ $item->name }}</td>

                                <td id="unit_number_{{ $item->id }}">
                                    {{ $leases->first()->unit_id ?? 'N/A' }}
                                </td>

                                <td>
                                    @if($leases->count() > 1)
                                        <select
                                            class="form-select form-select-sm"
                                            onchange="updateLeaseDetails(this, {{ $item->id }})"
                                        >
                                            @foreach($leases as $lease)
                                                @php
                                                    $unitType = $item->tenantApplication->unit_type ?? 'N/A';
                                                    $roomNo = $lease->room_no ?? 'N/A';
                                                    $bedNumber = $lease->bed_number ?? null;

                                                    if ($unitType === 'Bed-Spacer' && $bedNumber) {
                                                        $unitTypeDisplay = $unitType . ' - ' . $roomNo . ' - Bed ' . $bedNumber;
                                                    } elseif ($roomNo !== 'N/A') {
                                                        $unitTypeDisplay = $unitType . ' - ' . $roomNo;
                                                    } else {
                                                        $unitTypeDisplay = $unitType;
                                                    }
                                                @endphp

                                                <option
                                                    value="{{ $lease->id }}"
                                                    data-unit-number="{{ $lease->unit_id }}"
                                                    data-start="{{ $lease->lea_start_date }}"
                                                    data-end="{{ $lease->lea_end_date }}"
                                                    data-terms="{{ $lease->lea_terms }}"
                                                    data-status="{{ $lease->lea_status }}"
                                                >
                                                    {{ $unitTypeDisplay }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{ $unitTypeDisplay }}
                                    @endif
                                </td>

                                <td id="start_date_{{ $item->id }}">
                                    {{ $leases->first()?->lea_start_date ? \Carbon\Carbon::parse($leases->first()->lea_start_date)->format('M d, Y') : 'N/A' }}
                                </td>

                                <td id="end_date_{{ $item->id }}">
                                    {{ $leases->first()?->lea_end_date ? \Carbon\Carbon::parse($leases->first()->lea_end_date)->format('M d, Y') : 'N/A' }}
                                </td>

                                <td id="terms_{{ $item->id }}">
                                    {{ $leases->first()?->lea_terms ?? 'N/A' }}
                                </td>

                                <td id="status_{{ $item->id }}">
                                    @php $status = $leases->first()?->lea_status; @endphp
                                    <span class="badge bg-{{ $status === 'active' ? 'success' : ($status === 'terminated' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($status ?? 'N/A') }}
                                    </span>
                                </td>
                            </tr>

                            <!-- MAINTENANCE -->
                            @elseif($report === 'maintenance-requests')
                                @php
                                    $unitType = null;
                                    $roomNo = null;
                                    $bedNumber = null;

                                    if ($item->unit) {
                                        $unitType = $item->unit->type ?? null;
                                        $roomNo = $item->unit->room_no ?? null;
                                        // Try to get bed number from lease if available
                                        $lease = $item->tenant->leases->first() ?? null;
                                        $bedNumber = $lease->bed_number ?? null;
                                    } elseif ($item->unit_type || $item->room_no) {
                                        $unitType = $item->unit_type ?? null;
                                        $roomNo = $item->room_no ?? null;
                                    }

                                    // Format unit type with room number and bed number (if Bed-Spacer)
                                    if ($unitType && $roomNo) {
                                        if ($unitType === 'Bed-Spacer' && $bedNumber) {
                                            $unitDisplay = $unitType . ' - ' . $roomNo . ' - Bed ' . $bedNumber;
                                        } else {
                                            $unitDisplay = $unitType . ' - ' . $roomNo;
                                        }
                                    } elseif ($unitType) {
                                        $unitDisplay = $unitType;
                                    } else {
                                        $unitDisplay = 'N/A';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $item->tenant->name ?? 'N/A' }}</td>
                                    <td>{{ $unitDisplay }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>
                                        <span class="badge bg-{{
                                            $item->urgency === 'high' ? 'danger' :
                                            ($item->urgency === 'mid' ? 'warning text-dark' : 'secondary')
                                        }}">
                                            {{ ucfirst($item->urgency) }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($item->supposed_date)->format('M d, Y') }}</td>
                                    <td>
                                        <form action="{{ route('manager.requests.updateStatus', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="form-select form-select-sm rounded-pill"
                                                onchange="this.form.submit()"
                                                {{ $item->status === 'Cancelled' ? 'disabled' : '' }}>
                                                <option value="Pending" {{ $item->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="In Progress" {{ $item->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="Completed" {{ $item->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="Cancelled" {{ $item->status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        @if(!empty($item->issue_image) && file_exists(storage_path('app/public/' . $item->issue_image)))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary view-image-btn"
                                                    style="border: 2px solid #01017c; color: #01017c; background: transparent;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewImageModal"
                                                    data-image="{{ asset('storage/' . $item->issue_image) }}">
                                                <i class="bi bi-image"></i> View
                                            </button>
                                        @else
                                            <span class="text-muted fst-italic">No Image</span>
                                        @endif
                                    </td>
                                </tr>

                            @endif

                        @empty
                            <tr>
                                <td colspan="100%" class="text-center text-muted py-3">No records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION SECTION --}}
        @if ($data->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-rounded shadow-sm mb-0">

                        {{-- Previous Page Link --}}
                        @if ($data->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link bg-light text-secondary border-0">
                                    <i class="bi bi-chevron-left"></i>
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a href="{{ $data->previousPageUrl() }}" class="page-link border-0 text-primary">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($data->getUrlRange(1, $data->lastPage()) as $page => $url)
                            <li class="page-item {{ $page == $data->currentPage() ? 'active' : '' }}">
                                <a href="{{ $url }}"
                                    class="page-link border-0 {{ $page == $data->currentPage() ? 'bg-primary text-white shadow-sm' : 'text-dark bg-light' }}">
                                    {{ $page }}
                                </a>
                            </li>
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($data->hasMorePages())
                            <li class="page-item">
                                <a href="{{ $data->nextPageUrl() }}" class="page-link border-0 text-primary">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <span class="page-link bg-light text-secondary border-0">
                                    <i class="bi bi-chevron-right"></i>
                                </span>
                            </li>
                        @endif

                    </ul>
                </nav>
            </div>
        </div>
        @endif

        {{-- PAGINATION STYLES --}}
        @push('styles')
        <style>
            .pagination-rounded .page-item .page-link {
                border-radius: 50% !important;
                width: 38px;
                height: 38px;
                text-align: center;
                line-height: 36px;
                font-weight: 500;
                transition: all 0.2s ease-in-out;
            }

            .pagination-rounded .page-item.active .page-link {
                background-color: #0d6efd !important;
                color: #fff !important;
                box-shadow: 0 3px 6px rgba(13, 110, 253, 0.3);
            }

            .pagination-rounded .page-item .page-link:hover {
                background-color: #e9f3ff;
                color: #0d6efd;
            }

            .pagination-rounded .page-item.disabled .page-link {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>
        @endpush

    </div>
</div>

<!-- Reusable Image Modal -->
<!-- <div class="modal fade" id="viewImageModal" tabindex="-1" aria-labelledby="viewImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #01017c;">
                <h5 class="modal-title" id="viewImageModalLabel">Issue Image</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="modalIssueImage" src="" alt="Image" class="img-fluid rounded shadow-sm" style="max-height: 70vh; display: none;">
                <div id="imageError" class="alert alert-warning" style="display: none;">
                    <i class="bi bi-exclamation-triangle"></i> Image could not be loaded.
                    <div class="mt-2">
                        <small id="imageUrlDisplay" class="text-muted"></small>
                    </div>
                </div>
                <div id="imageLoading" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted small">Loading image...</p>
                </div>
            </div>
        </div>
    </div>
</div> -->
<!-- New Reusable Image Modal -->
 <div class="modal fade" id="viewImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">

            <div class="modal-header text-white" style="background-color: #01017c;">
                <h5 class="modal-title" id="viewImageModalLabel">Payment Proof</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center p-4">

                <img id="modalIssueImage" src="" class="img-fluid rounded shadow-sm"
                     style="max-height: 70vh; display: none;">

                <div id="imageLoading" class="text-center">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted small">Loading image...</p>
                </div>

                <div id="imageError" class="alert alert-warning d-none">
                    Image could not be loaded.
                </div>

                {{-- ✅ SHOW BUTTONS ONLY ON PAYMENT HISTORY --}}
                @if($report === 'payment-history')
                    <!-- <div class="d-flex justify-content-center gap-3 mt-4">
                        <form id="acceptForm" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="pay_status" value="Accepted">
                            <button class="btn btn-success px-4">Accept</button>
                        </form>

                        <form id="rejectForm" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="pay_status" value="Rejected">
                            <button class="btn btn-danger px-4">Reject</button>
                        </form>
                    </div> -->
                    <div class="d-flex justify-content-center gap-3 mt-4" id="modalActionButtons">
                        <button type="button" class="btn btn-success px-4" id="acceptBtn">Accept</button>
                        <button type="button" class="btn btn-danger px-4" id="rejectBtn">Reject</button>
                    </div>

                    <form id="updatePaymentForm" method="POST" style="display: none;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="pay_status" id="modalPayStatus">
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Old Script -->
<!-- <script>
document.addEventListener('DOMContentLoaded', function () {
    const modalImage = document.getElementById('modalIssueImage');
    const imageModal = document.getElementById('viewImageModal');
    const modalTitle = document.getElementById('viewImageModalLabel');
    const imageError = document.getElementById('imageError');
    const imageLoading = document.getElementById('imageLoading');

    if (!imageModal) {
        console.error('Modal element not found');
        return;
    }

    // Use Bootstrap's modal show event - this is the proper way
    imageModal.addEventListener('show.bs.modal', function (event) {
        // Get the button that triggered the modal
        const button = event.relatedTarget;

        if (!button) {
            console.error('No button found that triggered the modal');
            return;
        }

        const imageUrl = button.getAttribute('data-image');
        const title = button.getAttribute('data-title') || 'Payment Proof';

        console.log('Loading image:', { imageUrl, title });

        // Hide error and show loading
        if (imageError) imageError.style.display = 'none';
        if (imageLoading) imageLoading.style.display = 'block';
        if (modalImage) {
            modalImage.style.display = 'none';
            modalImage.src = '';
        }

        if (modalTitle) modalTitle.textContent = title;

        if (!imageUrl) {
            console.error('No image URL found in data-image attribute');
            if (imageLoading) imageLoading.style.display = 'none';
            if (imageError) {
                imageError.style.display = 'block';
                const urlDisplay = document.getElementById('imageUrlDisplay');
                if (urlDisplay) {
                    urlDisplay.textContent = 'No image URL provided';
                }
            }
            return;
        }

        // Load the image with proper error handling
        if (modalImage) {
            const img = new Image();

            img.onload = function() {
                console.log('Image loaded successfully');
                if (imageLoading) imageLoading.style.display = 'none';
                if (imageError) imageError.style.display = 'none';
                modalImage.src = imageUrl;
                modalImage.style.display = 'block';
            };

            img.onerror = function() {
                console.error('Failed to load image from URL:', imageUrl);
                if (imageLoading) imageLoading.style.display = 'none';
                if (imageError) {
                    imageError.style.display = 'block';
                    const urlDisplay = document.getElementById('imageUrlDisplay');
                    if (urlDisplay) {
                        urlDisplay.innerHTML = 'URL: <a href="' + imageUrl + '" target="_blank" class="text-decoration-none">' + imageUrl + '</a> (Click to test)';
                    }
                }
                modalImage.style.display = 'none';
            };

            // Start loading the image
            img.src = imageUrl;
        }
    });

    // Clear when modal closes
    imageModal.addEventListener('hidden.bs.modal', function () {
        if (modalImage) {
            modalImage.src = '';
            modalImage.style.display = 'none';
        }
        if (modalTitle) {
            modalTitle.textContent = 'Issue Image';
        }
        if (imageError) imageError.style.display = 'none';
        if (imageLoading) imageLoading.style.display = 'none';
    });
});
</script> -->




<script>
    function updateLeaseDetails(select, userId) {
        const selected = select.options[select.selectedIndex];

        const unitNumber = selected.dataset.unitNumber;
        const start = selected.dataset.start;
        const end = selected.dataset.end;
        const terms = selected.dataset.terms;
        const status = selected.dataset.status;

        document.getElementById(`unit_number_${userId}`).innerText = unitNumber ?? 'N/A';
        document.getElementById(`start_date_${userId}`).innerText = start ? new Date(start).toDateString() : 'N/A';
        document.getElementById(`end_date_${userId}`).innerText = end ? new Date(end).toDateString() : 'N/A';
        document.getElementById(`terms_${userId}`).innerText = terms ?? 'N/A';

        let badgeClass = 'secondary';
        if (status === 'active') badgeClass = 'success';
        else if (status === 'terminated') badgeClass = 'danger';

        document.getElementById(`status_${userId}`).innerHTML =
            `<span class="badge bg-${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tenantSelect = document.getElementById('tenant_id');
    const leaseSelect = document.getElementById('lease_id');
    const paymentForSelect = document.getElementById('payment_for');
    const amountInput = document.getElementById('pay_amount');

    const depositOption = document.getElementById('depositOption');
    const rentOption = paymentForSelect.querySelector('option[value="Rent"]');
    const utilitiesOption = paymentForSelect.querySelector('option[value="Utilities"]');

    // ✅ RESET EVERYTHING
    function resetAll() {
        leaseSelect.innerHTML = '<option value="">Select Room / Unit</option>';
        leaseSelect.disabled = true;

        paymentForSelect.value = "";
        paymentForSelect.disabled = true;

        amountInput.value = "";
        amountInput.disabled = true;

        // Reset option labels
        depositOption.classList.add('d-none');
        depositOption.dataset.balance = 0;
        depositOption.textContent = "Deposit";

        rentOption.textContent = "Rent (Select unit first)";
        utilitiesOption.textContent = "Utilities (Select unit first)";
    }

    resetAll();

    // ✅ WHEN TENANT IS SELECTED
    tenantSelect.addEventListener('change', function () {
        const tenantId = this.value;
        resetAll();
        if (!tenantId) return;

        leaseSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`/manager/tenant/${tenantId}/leases`)
            .then(res => res.json())
            .then(data => {
                leaseSelect.innerHTML = '<option value="">Select Room / Unit</option>';

                data.forEach(lease => {
                    leaseSelect.innerHTML += `
                        <option
                            value="${lease.id}"
                            data-rent="${lease.room_price}"
                            data-utilities="${lease.utility_balance}"
                            data-deposit="${lease.deposit}"
                        >
                            ${lease.room_no} - ${lease.unit_type}
                        </option>
                    `;

                    // ✅ SHOW DEPOSIT PRICE
                    if (lease.deposit > 0) {
                        depositOption.classList.remove('d-none');
                        depositOption.dataset.balance = lease.deposit;
                        depositOption.textContent = `Deposit (₱${parseFloat(lease.deposit).toFixed(2)})`;
                    }
                });

                leaseSelect.disabled = false;
            });
    });

    // ✅ WHEN LEASE IS SELECTED → UPDATE RENT & UTILITIES PRICES
    leaseSelect.addEventListener('change', function () {
        if (!this.value) {
            paymentForSelect.disabled = true;
            amountInput.disabled = true;
            amountInput.value = "";
            return;
        }

        const selectedLease = this.options[this.selectedIndex];

        const rent = selectedLease.dataset.rent || 0;
        const utilities = selectedLease.dataset.utilities || 0;

        rentOption.textContent = `Rent (₱${parseFloat(rent).toFixed(2)})`;
        utilitiesOption.textContent = `Utilities (₱${parseFloat(utilities).toFixed(2)})`;

        paymentForSelect.disabled = false;
    });

    // ✅ WHEN PAYMENT TYPE IS SELECTED → AUTO-FILL AMOUNT
    paymentForSelect.addEventListener('change', function () {
        const selectedType = this.value;
        const selectedLease = leaseSelect.options[leaseSelect.selectedIndex];

        let balance = 0;

        if (selectedType === "Deposit") {
            balance = depositOption.dataset.balance || 0;
        }

        if (selectedType === "Rent") {
            balance = selectedLease.dataset.rent || 0;
        }

        if (selectedType === "Utilities") {
            balance = selectedLease.dataset.utilities || 0;
        }

        amountInput.disabled = false;
        amountInput.value = parseFloat(balance).toFixed(2);
    });
});
</script>


<!-- New Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalImage = document.getElementById('modalIssueImage');
    const imageModal = document.getElementById('viewImageModal');
    const modalTitle = document.getElementById('viewImageModalLabel');
    const imageError = document.getElementById('imageError');
    const imageLoading = document.getElementById('imageLoading');

    const acceptBtn = document.getElementById('acceptBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    const updateForm = document.getElementById('updatePaymentForm');
    const modalPayStatus = document.getElementById('modalPayStatus');

    let currentPaymentId = null;

    imageModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const imageUrl = button.dataset.image;
        const title = button.dataset.title || 'Payment Proof';
        const paymentId = button.dataset.paymentId;
        const status = button.dataset.status;

        currentPaymentId = paymentId;

        // Set modal title
        if (modalTitle) modalTitle.textContent = title;

        // Reset UI
        if (modalImage) {
            modalImage.style.display = 'none';
            modalImage.src = '';
        }
        if (imageLoading) imageLoading.style.display = 'block';
        if (imageError) imageError.style.display = 'none';

        // Disable buttons if already accepted
        if (status === 'Accepted') {
            acceptBtn.disabled = true;
            rejectBtn.disabled = true;
        } else {
            acceptBtn.disabled = false;
            rejectBtn.disabled = false;
        }

        // Check if image URL is valid
        if (!imageUrl) {
            imageLoading.style.display = 'none';
            imageError.style.display = 'block';
            const urlDisplay = document.getElementById('imageUrlDisplay');
            if (urlDisplay) urlDisplay.textContent = 'No image URL provided';
            return;
        }

        // Load image directly into modalImage
        modalImage.onload = function () {
            imageLoading.style.display = 'none';
            imageError.style.display = 'none';
            modalImage.style.display = 'block';
        };

        modalImage.onerror = function () {
            imageLoading.style.display = 'none';
            imageError.style.display = 'block';
            modalImage.style.display = 'none';

            const urlDisplay = document.getElementById('imageUrlDisplay');
            if (urlDisplay) {
                urlDisplay.innerHTML = 'URL: <a href="' + imageUrl + '" target="_blank" class="text-decoration-none">' + imageUrl + '</a> (Click to test)';
            }
        };

        // Start loading
        modalImage.src = imageUrl;
    });

    // Reset modal when closed
    imageModal.addEventListener('hidden.bs.modal', function () {
        if (modalImage) {
            modalImage.src = '';
            modalImage.style.display = 'none';
        }
        if (modalTitle) modalTitle.textContent = 'Payment Proof';
        if (imageLoading) imageLoading.style.display = 'none';
        if (imageError) imageError.style.display = 'none';
    });

    // Accept / Reject buttons
    function submitPaymentUpdate(newStatus) {
        if (!currentPaymentId) return;
        modalPayStatus.value = newStatus;
        updateForm.action = `/manager/payments/${currentPaymentId}/status`;
        updateForm.submit();
    }

    acceptBtn.addEventListener('click', function () { submitPaymentUpdate('Accepted'); });
    rejectBtn.addEventListener('click', function () { submitPaymentUpdate('Rejected'); });
});
</script>
@endpush


