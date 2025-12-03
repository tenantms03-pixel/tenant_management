@extends('layouts.tenantdashboardlayout')

@section('title', 'My Requests')

@section('content')
<h1 class="fw-bold mb-6">Maintenance Requests</h1>
&nbsp
<div class="card mb-6">
    <div class="card-header d-flex justify-content-between align-items-center text-white"
         style="background: linear-gradient(135deg, #01017c, #2e3c90);">
        <span class="fw-bold">Maintenance Requests</span>
        <button
            class="btn btn-sm text-white fw-semibold"
            data-bs-toggle="modal"
            data-bs-target="#createRequestModal"
            style="background-color: #2e3c90; border: none; border-radius: 8px; padding: 8px 16px;">
            + Create Request
        </button>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($requests->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-exclamation-circle fs-1 mb-2 text-primary"></i>
                <p class="mb-0">No maintenance requests found. Click <strong>"Create Request"</strong> to add one.</p>
            </div>
        @else
        <div class="mb-3 d-flex justify-content-end">
            <input type="text" id="requestSearch" class="form-control w-25" placeholder="Search requests...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead style="background-color: #01017c; color: white;">
                    <tr>
                        <th>#</th>
                        <th>Date Filed</th>
                        <th>Unit Type</th>
                        <th>Room No</th>
                        <th>Request</th>
                        <th>Urgency</th>
                        <th>Supposed Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $request)
                    @php
                        $urgencyClass = match($request->urgency) {
                            'high' => 'bg-danger text-white',
                            'mid' => 'bg-warning text-dark',
                            default => 'bg-success text-white',
                        };
                        $statusClass = match($request->status) {
                            'Pending' => 'bg-secondary text-white',
                            'Accepted' => 'bg-success text-white',
                            'Rejected' => 'bg-danger text-white',
                            'Cancelled' => 'bg-dark text-white',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->created_at)->format('D, M d, Y') }}</td>
                        <td>{{ $request->unit_type ?? '-' }}</td>
                        <td>
                            {{ $request->room_no ?? '-' }}
                            @if($request->lease && $request->lease->bed_number)
                                <div class="text-muted small">Bed {{ $request->lease->bed_number }}</div>
                            @endif
                        </td>
                        <td>{{ ucfirst($request->description) }}</td>
                        <td><span class="badge {{ $urgencyClass }}">{{ ucfirst($request->urgency) }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($request->supposed_date)->format('D, M d, Y') }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ ucfirst($request->status) }}</span></td>

                        {{-- Action --}}
                        <td>
                            @if(in_array($request->status, ['Pending', 'In Progress']))
                                <button
                                    type="button"
                                    class="btn btn-sm text-white"
                                    style="background-color: #01017c;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelRequestModal{{ $request->id }}">
                                    Cancel
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Cancel Modal --}}
                    <div class="modal fade" id="cancelRequestModal{{ $request->id }}" tabindex="-1" aria-labelledby="cancelRequestLabel{{ $request->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0">
                                <div class="modal-header text-white" style="background-color: #01017c;">
                                    <h5 class="modal-title" id="cancelRequestLabel{{ $request->id }}">Confirm Cancellation</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
                                    <p>Are you sure you want to cancel this maintenance request?</p>
                                    <p class="text-muted small">Once cancelled, it cannot be modified.</p>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                                    <form action="{{ route('tenant.requests.cancel', $request->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn text-white" style="background-color: #01017c; border: none; padding: 8px 20px;">Yes, Cancel</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1" aria-labelledby="createRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('tenant.requests.store') }}" class="modal-content" enctype="multipart/form-data">
            @csrf
            <div class="modal-header text-white" style="background-color: #01017c;">
                <h5 class="modal-title" id="createRequestModalLabel">New Maintenance Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Room</label>
                    <select name="lease_id" class="form-select" required {{ $activeLeases->isEmpty() ? 'disabled' : '' }}>
                        <option value="">Choose your room</option>
                        @foreach($activeLeases as $lease)
                            <option value="{{ $lease->id }}" {{ old('lease_id') == $lease->id ? 'selected' : '' }}>
                                {{ $lease->room_no ?? 'Room' }} - {{ $lease->unit->type ?? 'Unit' }}
                                @if($lease->bed_number)
                                    (Bed {{ $lease->bed_number }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Pick the unit that needs maintenance.</small>
                </div>
                @if($activeLeases->isEmpty())
                    <div class="alert alert-warning">
                        No active leases found. Please contact the administrator before submitting a request.
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Request Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Describe the issue..." required>{{ old('description') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Urgency</label>
                    <select name="urgency" class="form-select" required>
                        <option value="" disabled selected>Select urgency</option>
                        <option value="low" {{ old('urgency') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="mid" {{ old('urgency') == 'mid' ? 'selected' : '' }}>Mid</option>
                        <option value="high" {{ old('urgency') == 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Supposed Date</label>
                    <input type="date" name="supposed_date" class="form-control" value="{{ old('supposed_date') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Attach Image</label>
                    <input type="file" name="issue_image" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="border: 2px solid #01017c; color: #01017c; background: transparent;" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn text-white" {{ $activeLeases->isEmpty() ? 'disabled' : '' }} style="background-color: #01017c; color: white; border: none; padding: 8px 20px;">Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('requestSearch');
    const table = document.querySelector('table tbody');
    const rows = table ? Array.from(table.querySelectorAll('tr')) : [];

    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // Column sorting
    document.querySelectorAll('table thead th').forEach((th, index) => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const sortedRows = rows.sort((a, b) => {
                const aText = a.children[index].textContent.trim();
                const bText = b.children[index].textContent.trim();
                return aText.localeCompare(bText, undefined, { numeric: true });
            });
            sortedRows.forEach(row => table.appendChild(row));
        });
    });
});
</script>
@endpush
