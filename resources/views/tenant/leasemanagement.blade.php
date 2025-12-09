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
                                    <button
                                        class="btn btn-outline-danger btn-sm request-moveout-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#moveOutModal"
                                        data-lease-id="{{ $lease->id }}">
                                        <i class="bi bi-door-open"></i> Request to Move Out
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Move Out Modal -->
<div class="modal fade" id="moveOutModal" tabindex="-1" aria-labelledby="moveOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="moveOutForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveOutModalLabel">Request Move Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="move_out_reason" class="form-label fw-bold">Reason for Moving Out</label>
                        <textarea name="reason" id="move_out_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
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

            <!-- <form action="{{ route('tenant.leases.store') }}" method="POST">
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

            </form> -->

            <form action="{{ route('tenant.leases.store') }}" method="POST">
                @csrf

                <div class="modal-body row">

                    <!-- ROOM SELECTION -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Select Room</label>
                        <select name="unit_id" id="unit_id"
                            class="form-select @error('unit_id') is-invalid @enderror" required>

                            <option value="">Select a room</option>

                            @foreach($availableUnits as $unit)
                                @php
                                    $takenBeds = ($unit->taken_beds ?? collect())->values();

                                    if ($unit->type === 'Bed-Spacer') {
                                        $displayCount = max($unit->no_of_occupants ?? 0, $takenBeds->count());
                                        $occupancyInfo = " ({$displayCount}/{$unit->capacity})";
                                        $isFullyBooked = $takenBeds->count() >= $unit->capacity;
                                    } else {
                                        $occupancyInfo = "";
                                        $isFullyBooked = $unit->status === 'occupied';
                                    }
                                @endphp

                                @if($unit->status === 'vacant' || ($unit->type === 'Bed-Spacer' && !$isFullyBooked))
                                    <option value="{{ $unit->id }}"
                                        data-type="{{ $unit->type }}"
                                        data-capacity="{{ $unit->capacity }}"
                                        data-taken-beds='@json($takenBeds)'
                                        {{ old('unit_id') == $unit->id ? 'selected' : '' }}>

                                        {{ $unit->room_no }} ({{ $unit->type }}{{ $occupancyInfo }})
                                        @if($isFullyBooked) - Occupied @endif
                                    </option>
                                @endif
                            @endforeach

                        </select>
                        @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- BED SELECTION (ONLY FOR BED-SPACER) -->
                    <div class="col-md-12 mb-3 d-none" id="bedSelectionGroup">
                        <label class="form-label fw-bold">Select Bed</label>
                        <select name="bed_number" id="bed_number"
                            class="form-select @error('bed_number') is-invalid @enderror">
                            <option value="">Select a bed</option>
                        </select>

                        @error('bed_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- LEASE START DATE -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Lease Start Date</label>
                        <input type="date" name="start_date"
                            class="form-control @error('start_date') is-invalid @enderror" required
                            min="{{ now()->toDateString() }}"
                            max="{{ now()->addWeeks(2)->toDateString() }}"
                        >

                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        style="border: 2px solid #01017c; color: #01017c; background: transparent;"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary"
                        style="background-color: #01017c; color: white; border: none; padding: 8px 16px; border-radius: 6px;">
                        Submit Application
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- <script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded');

    const unitSelect = document.getElementById('unit_id');
    const bedSelectionGroup = document.getElementById('bedSelectionGroup');
    const bedSelect = document.getElementById('bed_number');

    console.log('Elements:', {
        unitSelect: unitSelect,
        bedSelectionGroup: bedSelectionGroup,
        bedSelect: bedSelect
    });

    if (!unitSelect || !bedSelectionGroup || !bedSelect) {
        console.error('Required elements not found!');
        return;
    }

    function updateBedSelection() {
        console.log('updateBedSelection called');

        const selectedOption = unitSelect.options[unitSelect.selectedIndex];
        console.log('Selected option:', selectedOption);

        if (!selectedOption || !selectedOption.value) {
            console.log('No room selected');
            bedSelectionGroup.classList.add('d-none');
            bedSelect.innerHTML = '<option value="">Select a bed</option>';
            bedSelect.removeAttribute('required');
            return;
        }

        const roomType = selectedOption.getAttribute('data-type');
        const capacity = parseInt(selectedOption.getAttribute('data-capacity'));
        const takenBedsStr = selectedOption.getAttribute('data-taken-beds');

        console.log('Raw data:', {
            roomType: roomType,
            capacity: capacity,
            takenBedsStr: takenBedsStr
        });

        let takenBeds = [];
        try {
            takenBeds = JSON.parse(takenBedsStr || '[]');
        } catch (e) {
            console.error('Error parsing taken beds:', e);
            takenBeds = [];
        }

        console.log('Room data:', {
            roomType: roomType,
            capacity: capacity,
            takenBeds: takenBeds
        });

        if (roomType === 'Bed-Spacer') {
            console.log('Bed-Spacer detected - showing bed selection');
            bedSelectionGroup.classList.remove('d-none');
            bedSelect.setAttribute('required', 'required');

            bedSelect.innerHTML = '<option value="">Select a bed</option>';

            let addedBeds = 0;
            for (let i = 1; i <= capacity; i++) {
                if (!takenBeds.includes(i)) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = `Bed ${i}`;
                    bedSelect.appendChild(option);
                    addedBeds++;
                    console.log(`Added Bed ${i}`);
                } else {
                    console.log(`Bed ${i} is taken, skipping`);
                }
            }

            console.log(`Total beds added: ${addedBeds}`);

            const oldBedNumber = "{{ old('bed_number') }}";
            if (oldBedNumber) {
                bedSelect.value = oldBedNumber;
                console.log('Restored old bed number:', oldBedNumber);
            }
        } else {
            console.log('Not a Bed-Spacer - hiding bed selection');
            bedSelectionGroup.classList.add('d-none');
            bedSelect.innerHTML = '<option value="">Select a bed</option>';
            bedSelect.removeAttribute('required');
        }
    }

    unitSelect.addEventListener('change', function() {
        console.log('Room selection changed');
        updateBedSelection();
    });

    const modal = document.getElementById('applyLeaseModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal shown');
            updateBedSelection();
        });
    }

    updateBedSelection();
});
</script> -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const moveOutModal = document.getElementById('moveOutModal');
    const moveOutForm = document.getElementById('moveOutForm');

    moveOutModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const leaseId = button.getAttribute('data-lease-id');

        // Update form action
        moveOutForm.action = `/tenant/leases/${leaseId}/request-move-out`;
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitSelect = document.getElementById('unit_id');
    const bedSelectionGroup = document.getElementById('bedSelectionGroup');
    const bedSelect = document.getElementById('bed_number');
    const unitModalEl = document.getElementById('unitModal');

    // Populate bed options for Bed-Spacer units
    function populateBeds() {
        const selectedOption = unitSelect.options[unitSelect.selectedIndex];
        if (!selectedOption || selectedOption.dataset.type !== 'Bed-Spacer') {
            bedSelectionGroup.classList.add('d-none');
            bedSelect.required = false;
            bedSelect.innerHTML = '<option value="">Select a bed</option>';
            return;
        }

        bedSelectionGroup.classList.remove('d-none');
        bedSelect.required = true;

        const capacity = parseInt(selectedOption.dataset.capacity || '0', 10);
        const takenBeds = JSON.parse(selectedOption.dataset.takenBeds || '[]').map(Number);
        const upperCount = Math.floor(capacity / 2);

        let optionsMarkup = '<option value="">Select a bed</option>';
        for (let i = 1; i <= capacity; i++) {
            if (!takenBeds.includes(i)) {
                const label = i <= upperCount ? `Bed ${i} - Upper` : `Bed ${i} - Lower`;
                optionsMarkup += `<option value="${i}">${label}</option>`;
            }
        }

        bedSelect.innerHTML = optionsMarkup;

        // Restore old value if exists
        const oldValue = bedSelect.getAttribute('data-old-value');
        if (oldValue && !takenBeds.includes(Number(oldValue))) {
            bedSelect.value = oldValue;
        }
        bedSelect.removeAttribute('data-old-value');
    }

    // Handle room selection change
    if (unitSelect && bedSelectionGroup && bedSelect) {
        unitSelect.addEventListener('change', populateBeds);

        // Run on page load if old value exists (validation error)
        if (unitSelect.value) {
            bedSelect.setAttribute('data-old-value', "{{ old('bed_number') }}");
            populateBeds();
        }
    }

    // Handle modal open and filter rooms by card type
    if (unitModalEl) {
        unitModalEl.addEventListener('show.bs.modal', function(event) {
            const card = event.relatedTarget; // clicked card
            if (!card) return; // Skip if modal opened programmatically

            const type = card.dataset.type;
            const image = card.dataset.image;
            const available = card.dataset.available;
            const price = card.dataset.price;
            const status = card.dataset.status;
            const capacity = card.dataset.capacity;
            const noOccupants = card.dataset.noOccupants;

            // Update modal content
            document.getElementById('unitModalTitle').textContent = `${type} Unit`;
            const modalImg = document.getElementById('unitModalImg');
            modalImg.src = image;
            document.getElementById('unitModalTypeText').textContent = `${type} Unit`;
            document.getElementById('unitModalCapacity').textContent =
                type === 'Bed-Spacer' ? `${noOccupants || 0}/${capacity}` : capacity;
            document.getElementById('unitModalAvailable').textContent = available;
            document.getElementById('unitModalPrice').textContent = Number(price).toFixed(2);
            const statusBadge = document.getElementById('unitModalStatus');
            statusBadge.textContent = status;
            statusBadge.className = 'badge ' + (status.toLowerCase() === 'vacant' ? 'bg-success' : 'bg-danger');

            document.getElementById('unitModalType').value = type;

            // Zoom image
            modalImg.onclick = function() {
                zoomImg.src = modalImg.src;
                zoomModal.show();
            };

            // Filter rooms in dropdown by unit type
            [...unitSelect.options].forEach(opt => {
                if (opt.value === "") return; // skip "Select a room"
                opt.hidden = opt.dataset.type !== type;
            });

            // Reset selection
            unitSelect.value = "";
            bedSelect.value = "";
            bedSelectionGroup.classList.add('d-none');
        });
    }
});
</script>

@endsection

