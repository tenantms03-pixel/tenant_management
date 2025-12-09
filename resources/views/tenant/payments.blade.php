@extends('layouts.tenantdashboardlayout')

@section('title', 'My Payments')

@section('content')
<div class="container-fluid px-0">

    {{-- 🔔 Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

     {{-- 🏠 Payment Summary Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
                <div class="card border-0 rounded-4 shadow-sm h-100 bg-gradient-warning text-white">
                    <div class="card-body d-flex flex-column justify-content-center text-center">
                        <i class="bi bi-cash-coin fs-1 mb-2 opacity-75"></i>

                        @php
                            $user = auth()->user();
                            $hasDeposit = $depositBalance > 0;
                        @endphp

                        @if($hasDeposit)
                            <h6 class="fw-semibold">Pending Deposit</h6>
                            <h3 class="fw-bold mb-0">₱{{ number_format($depositBalance, 2) }}</h3>
                        @else
                            <h6 class="fw-semibold">Unpaid Rent ({{ $unpaidRentMonth }})</h6>
                            <h3 class="fw-bold mb-0">₱{{ number_format($unpaidRent, 2) }}</h3>
                        @endif

                    </div>
                </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-gradient-danger text-white"
                 style="cursor:pointer;" onclick="viewUtilityProof()">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <i class="bi bi-lightning-charge-fill fs-1 mb-2 opacity-75"></i>
                    <h6 class="fw-semibold">Unpaid Utilities ({{ $unpaidUtilitiesMonth }})</h6>
                    <h3 class="fw-bold mb-0">₱{{ number_format($unpaidUtilities, 2) }}</h3>
                </div>
            </div>
        </div>

        <!-- <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-gradient-success text-white">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <i class="bi bi-wallet2 fs-1 mb-2 opacity-75"></i>
                    <h6 class="fw-semibold">Advance Payment</h6>
                    <h3 class="fw-bold mb-0">₱{{ number_format(auth()->user()->user_credit ?? 0, 2) }}</h3>
                </div>
            </div>
        </div> -->
    </div>

    {{-- 📜 Payment History --}}
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center rounded-top-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Payment History</h5>
            <!-- <button
                class="btn btn-sm text-white px-3 py-2 rounded-3"
                style="background-color: #01017c; color: white; border: none; padding: 8px 20px;"
                data-bs-toggle="modal"
                data-bs-target="#makePaymentModal"
                {{ $leasesForSelection->isEmpty() ? 'disabled' : '' }}
            >
                <i class="bi bi-plus-lg"></i> Make Payment
            </button> -->

            @php
                $hasPendingPayment = $payments->contains(function($payment) {
                    return $payment->pay_status === 'Pending';
                });
            @endphp

            <button
                class="btn btn-sm text-white px-3 py-2 rounded-3"
                style="background-color: #01017c; color: white; border: none; padding: 8px 20px;"
                data-bs-toggle="modal"
                data-bs-target="#makePaymentModal"
                {{ $leasesForSelection->isEmpty() || $hasPendingPayment ? 'disabled' : '' }}
            >
                <i class="bi bi-plus-lg"></i> Make Payment
            </button>
        </div>

        <div class="card-body">
            @if($payments->isEmpty())
                <p class="text-muted text-center py-4">No payments recorded yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle table-borderless">
                        <thead class="table-light text-uppercase small text-muted">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Payment For</th>
                                <th>Room</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Account No</th>
                                <th>Status</th>
                                <th>Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                            <tr class="align-middle">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment->pay_date ?? $payment->created_at)->format('M d, Y') }}</td>
                                <td>{{ $payment->payment_for ?? '-' }}</td>
                                <td>
                                    @if($payment->lease)
                                        <div class="fw-semibold">{{ $payment->lease->room_no ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $payment->lease->unit->type ?? 'Unit' }}
                                            @if($payment->lease->bed_number)
                                                · Bed {{ $payment->lease->bed_number }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($payment->pay_method) }}</td>
                                <td class="fw-semibold text-success">₱{{ number_format($payment->pay_amount, 2) }}</td>
                                <td>{{ $payment->account_no ?? '-' }}</td>
                                <td>
                                    <span class="badge
                                            @if($payment->pay_status === 'Accepted')
                                                bg-success
                                            @elseif($payment->pay_status === 'Rejected')
                                                bg-danger
                                            @else
                                                bg-warning text-dark
                                            @endif
                                        ">
                                            {{ $payment->pay_status }}
                                    </span>
                                </td>
                                <td>
                                    @if($payment->proof)
                                        <a href="{{ asset('storage/'.$payment->proof) }}" target="_blank" class="btn mb-3 ms-2 rounded-pill" style="border: 2px solid #01017c; color: #01017c; background: transparent;"">View</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Make Payment Modal -->
<div class="modal fade" id="makePaymentModal" tabindex="-1" aria-labelledby="makePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('tenant.payments.store') }}" enctype="multipart/form-data" class="modal-content rounded-4 shadow-lg border-0 p-3">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="makePaymentModalLabel">Make a Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                @php $user = auth()->user(); @endphp

                <div class="form-floating mb-3">
                    <select name="lease_id" id="lease_id" class="form-select" {{ $leasesForSelection->isEmpty() ? 'disabled' : '' }} required>
                        <option value="">Select Room / Unit</option>
                        @foreach($leasesForSelection as $leaseOption)
                            <option value="{{ $leaseOption->id }}"
                                    data-utility-balance="{{ $leaseOption->utility_balance ?? 0 }}"
                                    data-rent-balance="{{ $leaseOption->rent_balance ?? 0 }}"
                                    {{ old('lease_id') == $leaseOption->id ? 'selected' : '' }}>
                                {{ $leaseOption->room_no ?? 'Room' }} - {{ $leaseOption->unit->type ?? 'Unit' }}
                                @if($leaseOption->bed_number)
                                    (Bed {{ $leaseOption->bed_number }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <label for="lease_id">Room / Unit</label>
                </div>

                @if($leasesForSelection->isEmpty())
                    <div class="alert alert-warning">
                        No active leases are linked to your account. Please contact the property manager before making a payment.
                    </div>
                @endif

                <div class="form-floating mb-3">
                    <select name="payment_for" id="payment_for" class="form-select" required disabled>
                        <option value="">Select Payment Type</option>
                        @if($user->deposit_amount > 0)
                            <option value="Deposit" data-balance="{{ $user->deposit_amount }}">
                                Deposit (₱{{ number_format($user->deposit_amount, 2) }})
                            </option>
                        @endif
                        <option value="Rent" data-balance="0" data-lease-specific="true">
                            Rent (Select unit first)
                        </option>
                        <option value="Utilities" data-balance="0" data-lease-specific="true">
                            Utilities (Select unit first)
                        </option>
                        <!-- <option value="Other" data-balance="0">Pay in Advance</option> -->
                    </select>
                    <label for="payment_for">Payment For</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="number" name="pay_amount" id="pay_amount" class="form-control" required disabled>
                    <label for="pay_amount">Amount</label>
                </div>

                <div class="form-floating mb-3">
                    <select name="pay_method" id="payment_method" class="form-select" required disabled>
                        <option value="">Select Method</option>
                        {{-- <option value="Cash">Cash</option> --}}
                        <option value="GCash">GCash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                    <label for="payment_method">Payment Method</label>
                </div>

                <div class="form-floating mb-3 d-none" id="accountNumberField">
                    <input type="text" name="account_no" class="form-control" disabled>
                    <label>Account / GCash Number</label>
                </div>

                <div class="mb-3 d-none" id="proofOfPaymentField">
                    <label class="form-label">Proof of Payment (Screenshot / Receipt)</label>
                    <input type="file" name="proof" id="proof" class="form-control rounded-3" accept="image/*" disabled>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" style="border: 2px solid #01017c; color: #01017c; background: transparent;" data-bs-dismiss="modal">Cancel</button>
                <button
                    type="submit"
                    class="btn btn-sm text-white"
                    {{ $leasesForSelection->isEmpty() ? 'disabled' : '' }}
                    style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">
                    + Submit Payment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 🔍 Utility Proof Modal --}}
<div class="modal fade" id="viewUtilityProofModal" tabindex="-1" aria-labelledby="viewUtilityProofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold">Proof of Utility Billing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">Billing History</h6>

                @if($utilityProofs->isEmpty())
                    <p class="text-muted mb-0">No proof of billing records have been uploaded yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped align-middle text-center">
                            <thead>
                                <tr>
                                    <th>Billing Month</th>
                                    <th>Billing Type</th>
                                    <th>Utility Balance (₱)</th>
                                    <th>Uploaded At</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($utilityProofs as $proof)
                                    <tr>
                                        <td>{{ $proof->billing_month ?? '—' }}</td>
                                        <td>{{ $proof->billing_type ?? '—' }}</td>
                                        <td>₱{{ number_format($proof->amount ?? $proof->lease->utility_balance ?? 0, 2) }}</td>
                                        <td>{{ $proof->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @if($proof->file_path)
                                                <a href="{{ asset('storage/'.$proof->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            @else
                                                <span class="text-muted">No proof uploaded</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 🧠 Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leaseSelect = document.getElementById('lease_id');
    const paymentFor = document.getElementById('payment_for');
    const payAmount = document.getElementById('pay_amount');
    const method = document.getElementById('payment_method');
    const accountField = document.getElementById('accountNumberField');
    const accountInput = accountField ? accountField.querySelector('input[name="account_no"]') : null;
    const proofField = document.getElementById('proofOfPaymentField');
    const proofInput = document.getElementById('proof');

    function updateFieldLock() {
        const hasLease = leaseSelect && leaseSelect.value !== '';
        [paymentFor, payAmount, method, proofInput, accountInput].forEach(el => {
            if (!el) return;
            el.disabled = !hasLease;
        });

        if (!hasLease) {
            if (accountField) {
                accountField.classList.add('d-none');
            }
            if (proofField) {
                proofField.classList.add('d-none');
            }
        }
    }

    // Store lease data for dynamic updates
    const leaseData = {};
    @foreach($leasesForSelection as $leaseOption)
        @php
            $roomLabel = ($leaseOption->room_no ?? 'Room') . ' - ' . ($leaseOption->unit->type ?? 'Unit');
            if ($leaseOption->bed_number) {
                $roomLabel .= ' (Bed ' . $leaseOption->bed_number . ')';
            }
        @endphp
        leaseData[{{ $leaseOption->id }}] = {
            utilityBalance: {{ $leaseOption->utility_balance ?? 0 }},
            rentBalance: {{ $leaseOption->rent_balance ?? 0 }},
            roomLabel: {!! json_encode($roomLabel) !!}
        };
    @endforeach

    function updatePaymentOptions() {
        const selectedLeaseId = leaseSelect.value;

        if (!paymentFor) return;

        // Get all options
        const depositOption = Array.from(paymentFor.options).find(opt => opt.value === 'Deposit');
        const rentOption = Array.from(paymentFor.options).find(opt => opt.value === 'Rent');
        const utilitiesOption = Array.from(paymentFor.options).find(opt => opt.value === 'Utilities');
        const otherOption = Array.from(paymentFor.options).find(opt => opt.value === 'Other');

        if (selectedLeaseId && leaseData[selectedLeaseId]) {
            const lease = leaseData[selectedLeaseId];

            // Update Rent option
            if (rentOption) {
                if (lease.rentBalance > 0) {
                    rentOption.textContent = `Rent - ${lease.roomLabel} (₱${parseFloat(lease.rentBalance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
                    rentOption.dataset.balance = lease.rentBalance;
                    rentOption.style.display = '';
                } else {
                    rentOption.style.display = 'none';
                }
            }

            // Update Utilities option
            if (utilitiesOption) {
                if (lease.utilityBalance > 0) {
                    utilitiesOption.textContent = `Utilities - ${lease.roomLabel} (₱${parseFloat(lease.utilityBalance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
                    utilitiesOption.dataset.balance = lease.utilityBalance;
                    utilitiesOption.style.display = '';
                } else {
                    utilitiesOption.style.display = 'none';
                }
            }
        } else {
            // Reset to default when no lease selected
            if (rentOption) {
                rentOption.textContent = 'Rent (Select unit first)';
                rentOption.dataset.balance = 0;
                rentOption.style.display = '';
            }
            if (utilitiesOption) {
                utilitiesOption.textContent = 'Utilities (Select unit first)';
                utilitiesOption.dataset.balance = 0;
                utilitiesOption.style.display = '';
            }
        }

        // Auto-select first available option if current selection is invalid
        if (paymentFor.value === 'Rent' && rentOption && rentOption.style.display === 'none') {
            paymentFor.value = '';
        }
        if (paymentFor.value === 'Utilities' && utilitiesOption && utilitiesOption.style.display === 'none') {
            paymentFor.value = '';
        }
    }

    if (leaseSelect) {
        leaseSelect.addEventListener('change', function() {
            updateFieldLock();
            updatePaymentOptions();
        });
    }

    if (method) {
        method.addEventListener('change', () => {
            const showAccount = method.value === 'GCash' || method.value === 'Bank Transfer';
            const showProof = method.value === 'GCash' || method.value === 'Bank Transfer';

            // Toggle account number field
            if (accountField) {
                accountField.classList.toggle('d-none', !showAccount);
            }
            if (accountInput) {
                accountInput.disabled = !showAccount || method.disabled;
            }

            // Toggle proof of payment field
            if (proofField) {
                proofField.classList.toggle('d-none', !showProof);
            }
            if (proofInput) {
                // Make proof required only for GCash and Bank Transfer
                proofInput.required = showProof && !method.disabled;
                proofInput.disabled = !showProof || method.disabled;
            }
        });
    }

    if (paymentFor) {
        paymentFor.addEventListener('change', () => {
            const selected = paymentFor.options[paymentFor.selectedIndex];
            const balance = parseFloat(selected.dataset.balance || 0);
            payAmount.value = balance > 0 ? balance : '';
        });
    }

    // Initialize disabled state on load
    updateFieldLock();
    updatePaymentOptions();

    window.viewUtilityProof = function() {
        const proofModal = new bootstrap.Modal(document.getElementById('viewUtilityProofModal'));
        proofModal.show();
    };
});
</script>

{{-- 🌈 Styling --}}
{{-- 🌈 Custom Styling --}}
<style>
    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffb347, #ffcc33);
    }
    .bg-gradient-danger {
        background: linear-gradient(135deg, #ff5f6d, #ffc371);
    }
    .bg-gradient-success {
        background: linear-gradient(135deg, #11998e, #38ef7d);
    }

    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .table td, .table th {
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        opacity: .9;
    }
</style>

@endsection
