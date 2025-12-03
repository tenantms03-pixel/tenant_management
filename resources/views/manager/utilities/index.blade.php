@extends('layouts.managerdashboardlayout')

@section('title', 'Utilities Management')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ========= GLOBAL ========== */
body {
    background: #f3f5f9;
    font-family: 'Inter', sans-serif;
}

/* Gradient Text */
h2.gradient-text, .card-header.gradient-text, .modal-title.gradient-text {
    background: linear-gradient(90deg, #0d6efd, #00b4d8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
}

/* ========= CARD DESIGN ========== */
.card {
    border: none;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.card-header {
    border: none;
    border-radius: 16px 16px 0 0;
    background: #fff; /* we use gradient only on text */
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ========= TABLE DESIGN ========== */
.table thead th {
    background: #fff7e0;
    color: #343a40;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
}
.table tbody tr:hover {
    background: #fffde8;
    transition: 0.25s ease-in;
}
.table td, .table th {
    vertical-align: middle;
}
.table .text-end {
    font-weight: 600;
}

/* ========= BUTTONS ========== */
.btn {
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.2s ease;
}
.btn:hover {
    transform: translateY(-1px);
}
.btn-primary {
    background: linear-gradient(135deg, #0d6efd, #00b4d8);
    border: none;
    color: #fff;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #00b4d8, #0d6efd);
}
.btn-outline-secondary {
    border-radius: 10px;
}

/* ========= MODAL DESIGN ========== */
.modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.modal-header {
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    background: #fff; /* gradient applied to title */
}
.modal-footer {
    border-top: none;
    background-color: #f8f9fa;
}
.form-floating label {
    color: #666;
}

/* ========= ALERT DESIGN ========== */
#alertContainer .alert {
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}

/* ========= RESPONSIVE ========= */
@media (max-width: 768px) {
    h2 { font-size: 1.4rem; }
    .btn-sm { font-size: 0.8rem; padding: 0.3rem 0.6rem; }
}
</style>
@endpush

@section('content')
<div class="container mt-4">
    <h2 class="mb-4 fw-bold">
        Utilities Management
    </h2>

    <div class="card mb-4">
        <div class="card-header gradient-text">
            <i class="bi bi-list-check"></i> Tenant Utilities Overview
        </div>
        <div class="card-body">
            <div id="alertContainer"></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead>
                        <tr>
                            <th>Tenant Name</th>
                            <th>Room / Unit</th>
                            <th>Utility Balance (₱)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenantGroups as $group)
                            @php
                                $roomCount = count($group->room_entries);
                            @endphp
                            @foreach($group->room_entries as $index => $entry)
                            <tr id="lease-row-{{ $entry->lease_id }}">
                                @if($index === 0)
                                    <td rowspan="{{ $roomCount }}" class="align-middle">
                                        <strong>{{ $group->tenant->name }}</strong>
                                    </td>
                                @endif
                                <td>{{ $entry->room_label }}</td>
                                <td class="text-end">₱{{ number_format($entry->utility_balance, 2) }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn" style="background-color: #01017c; color: white; border: none; padding: 8px 20px;"
                                        data-id="{{ $entry->lease_id }}"
                                        data-name="{{ $group->tenant->name }}"
                                        data-room="{{ $entry->room_label }}"
                                        data-balance="{{ number_format($entry->utility_balance, 2, '.', '') }}"
                                        data-leases="{{ $group->all_leases->map(function($lease) { return ['id' => $lease->id, 'room_no' => $lease->room_no ?? 'N/A', 'unit_type' => $lease->unit->type ?? 'Unit', 'bed_number' => $lease->bed_number]; })->toJson() }}">
                                        <i class="bi bi-pencil-square me-1"></i> Update
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="4" class="text-muted py-4">No tenant utility records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Utility Modal -->
<div class="modal fade" id="editUtilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title gradient-text">
                    <i class="bi bi-pencil-square me-1"></i> Update Utility Balance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUtilityForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="leaseId">
                <div class="modal-body">
                    <div class="form-floating mb-3">
                        <input type="text" id="tenantName" class="form-control" readonly>
                        <label for="tenantName">Tenant Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" id="selectedRoom" class="form-control" readonly>
                        <label for="selectedRoom">Room / Unit</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" step="0.01" min="0" id="utilityBalance" class="form-control text-end" placeholder="0.00" required>
                        <label for="utilityBalance">Utility Balance (₱)</label>
                    </div>
                    <div class="mb-3">
                        <label for="proofOfUtilityBilling" class="form-label fw-semibold">Proof of Utility Billing (Optional)</label>
                        <input type="file" id="proofOfUtilityBilling" name="proof_of_utility_billing" class="form-control" accept="image/*">
                        <small class="text-muted">Upload an image of the utility bill (JPG, PNG).</small>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="month" id="billingMonth" name="billing_month" class="form-control">
                        <label for="billingMonth">Billing Month (optional)</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="proof_notes" class="form-control" rows="2" placeholder="Short note about this bill..."></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" style="border: 2px solid #01017c; color: #01017c; background: transparent;" 
                        data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = new bootstrap.Modal(document.getElementById('editUtilityModal'));
    const editForm = document.getElementById('editUtilityForm');
    const tenantNameInput = document.getElementById('tenantName');
    const selectedRoomInput = document.getElementById('selectedRoom');
    const utilityBalanceInput = document.getElementById('utilityBalance');
    const proofInput = document.getElementById('proofOfUtilityBilling');
    const billingMonthInput = document.getElementById('billingMonth');
    const proofNotesInput = document.querySelector('textarea[name="proof_notes"]');
    const leaseIdInput = document.getElementById('leaseId');
    const alertContainer = document.getElementById('alertContainer');

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            // Set form values directly from button data
            leaseIdInput.value = button.dataset.id;
            tenantNameInput.value = button.dataset.name;
            selectedRoomInput.value = button.dataset.room;
            utilityBalanceInput.value = button.dataset.balance;
            
            // Reset optional fields
            proofInput.value = '';
            billingMonthInput.value = '';
            proofNotesInput.value = '';
            
            modal.show();
        });
    });

    utilityBalanceInput.addEventListener('input', function () {
        // Only allow numbers and decimal point (type="number" handles most of this, but we'll ensure it)
        let val = this.value.replace(/[^0-9.]/g, '');
        // Prevent multiple decimal points
        const parts = val.split('.');
        if (parts.length > 2) {
            val = parts[0] + '.' + parts.slice(1).join('');
        }
        this.value = val;
    });

    editForm.addEventListener('submit', function(e){
        e.preventDefault();
        const leaseId = leaseIdInput.value;
        const formattedValue = parseFloat(utilityBalanceInput.value);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        if (!leaseId) {
            alertContainer.innerHTML = `<div class="alert alert-danger">Invalid lease ID.</div>`;
            return;
        }

        if(isNaN(formattedValue) || formattedValue < 0){
            alertContainer.innerHTML = `<div class="alert alert-danger">Invalid number entered.</div>`;
            return;
        }

        const formData = new FormData();
        formData.append('utility_balance', formattedValue);
        formData.append('_method', 'PUT');
        if (billingMonthInput.value) {
            formData.append('billing_month', billingMonthInput.value);
        }
        if (proofNotesInput.value) {
            formData.append('proof_notes', proofNotesInput.value);
        }
        if (proofInput.files[0]) {
            formData.append('proof_of_utility_billing', proofInput.files[0]);
        }

        fetch(`/manager/utilities/${leaseId}`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken},
            body: formData
        })
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                modal.hide();
                alertContainer.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show mt-3">
                        <i class="bi bi-check-circle me-1"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                // Reload page after 1 second to show updated balance
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alertContainer.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show mt-3">
                        <i class="bi bi-exclamation-triangle me-1"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
            }
        })
        .catch(err=>{
            console.error(err);
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show mt-3">
                    <i class="bi bi-x-circle me-1"></i> An error occurred while updating.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
        });
    });
});
</script>
@endsection
