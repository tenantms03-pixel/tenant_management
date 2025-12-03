@extends('layouts.tenantdashboardlayout')

@section('content')
<div class="container">
    <h1 class="mb-2 fw-bold">Lease Management</h1>

    <!-- Apply for New Lease Button -->
    <button class="btn btn-success mb-3" style="background-color: #01017c; color: white; border: none; padding: 8px 16px; border-radius: 6px;" 
        data-bs-toggle="modal" data-bs-target="#applyLeaseModal">
        <i class="bi bi-plus-circle me-1"></i> Apply for New Unit
    </button>

    @if($leases->isEmpty())
        <div class="alert alert-info">
            You currently have no active leases.
        </div>
    @else
        <div class="row">
            @foreach($leases as $lease)
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold">{{ $lease->unit->unit_type }}</h5>
                            <p class="mb-1">Room No: <strong>{{ $lease->unit->room_no }}</strong></p>
                            <p class="mb-1">Monthly Rent:
                                <strong>₱{{ number_format($lease->unit->room_price, 2) }}</strong>
                            </p>
                            <p class="mb-1">Lease Start:
                                <strong>{{ $lease->lea_start_date }}</strong>
                            </p>
                            <p class="mb-2">Lease End:
                                <strong>{{ $lease->lea_end_date }}</strong>
                            </p>
                            <p class="text-muted mb-2">
                                Status: <strong>{{ ucfirst($lease->lea_status) }}</strong>
                            </p>

                            @if(in_array($lease->lea_status, ['active', 'pending']))
                                @if($lease->move_out_requested)
                                    <div class="alert alert-info mb-2 py-2">
                                        <small><i class="bi bi-info-circle"></i> Move out request submitted - pending manager approval</small>
                                    </div>
                                @else
                                    <form action="{{ route('tenant.leases.requestMoveOut', $lease->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to request to move out? This action will notify the manager.')">
                                            <i class="bi bi-door-open"></i> Request to Move Out
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>


<!-- ===================================== -->
<!-- Apply for New Lease Modal -->
<!-- ===================================== -->

<div class="modal fade" id="applyLeaseModal" tabindex="-1" aria-labelledby="applyLeaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header text-white" style="background-color: #01017c; color: white; border: none; padding: 8px 16px; border-radius: 6px;">
                <h5 class="modal-title fw-bold" id="applyLeaseModalLabel">Apply for New Unit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('tenant.leases.store') }}" method="POST">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Unit</label>
                        <select name="unit_id" class="form-select" required>
                            <option value="">-- Choose Unit --</option>

                            @foreach($availableUnits as $unit)
                                <option value="{{ $unit->id }}">
                                    {{ $unit->type }} {{ $unit->room_no }} - {{ $unit->unit_type }}
                                    (₱{{ number_format($unit->room_price, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Lease Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" style="border: 2px solid #01017c; color: #01017c; background: transparent;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #01017c; color: white; border: none; padding: 8px 16px; border-radius: 6px;">Submit Application</button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
