@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="container py-5 text-center">



    <!-- Header Section -->
    <div class="mb-5">
        <h1 class="fw-bold display-5 text-primary">
            Welcome to <span class="text-gradient">Pinikitan Rental</span>
        </h1>
        <p class="lead text-muted">
            Find your next home with ease — we currently have
            <strong class="text-success">{{ $vacantCount }}</strong> vacant rooms available.
        </p>
    </div>

    <!-- Units Cards -->
    <div class="row justify-content-center g-4">
        @php
            $groupedUnits = $units->where('status', 'vacant')->groupBy('type');
        @endphp

        @foreach($groupedUnits as $type => $typeUnits)
            <div class="col-md-6 col-lg-4">
                @php
                    $pendingApplicants = $typeUnits->sum(function ($unit) {
                        return $unit->leases->where('lea_status', 'pending')->count();
                    });

                    $totalApplicationLimit = $typeUnits->sum('application_limit');
                    $isFull = $pendingApplicants >= $totalApplicationLimit;

                @endphp
                <div class="card h-100 shadow-sm border-0 rounded-4 hover-card glass-card
                    {{ $isFull ? 'opacity-50 pointer-events-none' : '' }}"
                    @if(!$isFull)
                        data-bs-toggle="modal"
                        data-bs-target="#unitModal"
                    @endif
                    data-type="{{ $type }}"
                    data-unitId="{{ $typeUnits->first()->id }}"
                    data-capacity="{{ $typeUnits->first()->capacity }}"
                    data-noOccupants="{{ $typeUnits->first()->no_of_occupants }}"
                    data-image="{{ asset('images/units/' . strtolower(str_replace(' ', '-', $type)) . '.jpg') }}"
                    data-available="{{ $typeUnits->where('status', 'vacant')->pluck('room_no')->join(', ') }}"
                    data-price="{{ $typeUnits->first()->room_price ?? 0 }}"
                    data-status="{{ $isFull ? 'Full' : 'Vacant' }}">

                    <img src="{{ asset('images/units/' . strtolower(str_replace(' ', '-', $type)) . '.jpg') }}"
                         class="card-img-top rounded-top-4"
                         alt="{{ $type }} Image"
                         style="height: 180px; object-fit: cover;">

                    <div class="card-body text-center">
                        <h4 class="card-title fw-bold">{{ $type }}</h4>
                        @if($isFull)
                            <span class="badge bg-danger mb-3">FULL</span>
                        @else
                            <span class="badge bg-success mb-3">Vacant</span>
                        @endif
                        {{-- Display here a pending applicant count / unit->application_limit --}}

                        <ul class="list-unstyled mb-0">
                            @foreach($typeUnits as $unit)
                                <li>
                                    <p class="fw-semibold text-muted mb-2">
                                        Pending Applications: {{ $pendingApplicants }} / {{ $totalApplicationLimit }}
                                    </p>
                                </li>
                                <li class="py-1">
                                    Room No: {{ $unit->room_no }}
                                    [
                                    @if($type === 'Bed-Spacer')
                                        {{ $unit->no_of_occupants }}/{{ $unit->capacity }}
                                    @else
                                        {{ $unit->capacity > 1
                                            ? $unit->capacity . ' Persons'
                                            : $unit->capacity . ' Person'
                                        }}
                                    @endif
                                    ]
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- About Us Section -->
    <section class="py-5 text-center">
        <h2 class="fw-bold text-primary mb-4">About Us</h2>
        <p class="lead text-muted mx-auto" style="max-width: 700px;">
            Our apartment complex started in <strong>2015</strong> with the goal of providing safe,
            affordable, and modern living spaces for students and working professionals.
            With well-maintained facilities and accessible locations, we’ve built a community
            where convenience meets comfort.
        </p>
    </section>

    <!-- Contact Section -->
    <section class="py-5 bg-light rounded-4 shadow-sm">
        <h2 class="fw-bold text-primary mb-4">📍 Contact Us</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm h-100">
                    <h5 class="fw-bold">Location</h5>
                    <p class="text-muted">123 Apartment Street, Cagayan de Oro, Philippines</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm h-100">
                    <h5 class="fw-bold">Phone</h5>
                    <p class="text-muted">+63 912 345 6789</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm h-100">
                    <h5 class="fw-bold">Email</h5>
                    <p class="text-muted">info@apartmentportal.com</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Embed -->
    <section class="py-5">
        <h2 class="fw-bold text-primary mb-4">📌 Find Us Here</h2>
        <div class="ratio ratio-16x9 rounded-4 shadow-sm overflow-hidden">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d829.5969257428177!2d124.65405466128895!3d8.475028046399832!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32fff2ce448339c1%3A0x639be33dade6bf70!2sR.A.%20VITORILLO%20LAW%20OFFICE%20AND%20ASSOCIATES!5e0!3m2!1sen!2sph!4v1761789546488!5m2!1sen!2sph"
                style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>

</div>

<style>
    /* .modal-backdrop.show:nth-of-type(2) {
        z-index: 1056 !important;
    }
    #termsModal {
        z-index: 1057 !important;
    } */
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 1rem;
        box-shadow: 0 6px 25px rgba(49, 7, 236, 0.28);
    }
    .btn-gradient {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
        color: #fff;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(1,1,124,0.25);
    }
    .text-gradient {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
</style>

<!-- Unit Modal (Reusable) -->
<div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg glass-card">

            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white" style="background: linear-gradient(135deg, #01017c, #2d3b9a);">
                <h5 class="modal-title fw-bold" id="unitModalTitle">Unit Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <div class="row g-4">

                    <!-- LEFT: Unit Image + Info -->
                    <div class="col-lg-5 text-center">
                        <img id="unitModalImg" src="" alt="Unit Image"
                             class="img-fluid rounded-4 shadow-sm mb-3"
                             style="cursor: zoom-in;"
                             data-bs-toggle="modal" data-bs-target="#unitImageModal">

                        <div class="p-3 bg-light rounded-3 shadow-sm text-center">
                            <h6 class="fw-bold mb-2" id="unitModalTypeText"></h6>
                            <p class="mb-1"><strong>Size:</strong> <span id="unitModalSize"></span></p>
                            <p class="mb-1"><strong>Capacity:</strong> <span id="unitModalCapacity"></span></p>
                            <p class="mb-1"><strong>Available Rooms:</strong> <span id="unitModalAvailable"></span></p>
                            <p class="mb-1"><strong>Price:</strong> ₱<span id="unitModalPrice"></span></p>
                            <p class="mb-0"><strong>Status:</strong>
                                <span class="badge" id="unitModalStatus"></span>
                            </p>
                        </div>
                    </div>

                        <!-- RIGHT: Registration Form -->
                    <div class="col-lg-7">
                        <h5 class="mb-3 fw-bold text-primary">Tenant Registration & Application</h5>

                        {{-- Display Laravel validation errors --}}
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register.submit') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">

                            <!-- <div>

                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" placeholder="Juan" value="{{ old('first_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" placeholder="Dela Cruz" value="{{ old('last_name') }}" required>
                                </div>


                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="you@example.com" value="{{ old('email') }}" required>
                                    <div class="invalid-feedback" id="email-error"></div>
                                    <small class="text-muted" id="email-check-status"></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact" class="form-control @error('contact') is-invalid @enderror" placeholder="09XXXXXXXXX" value="{{ old('contact') }}" required>
                                </div>


                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <div class="position-relative">
                                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                        <button type="button" class="btn btn-link" id="togglePassword">
                                            <i class="bi bi-eye" id="passwordEyeIcon"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <small class="text-muted" id="passwordHint"></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="position-relative">
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" required>
                                        <button type="button" class="btn btn-link" id="togglePasswordConfirmation">
                                            <i class="bi bi-eye" id="passwordConfirmationEyeIcon"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="passwordMatch"></small>
                                </div>
                            </div> -->

                                @guest
                                    <!-- FIRST NAME & LAST NAME -->
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name"
                                            class="form-control @error('first_name') is-invalid @enderror"
                                            placeholder="Juan"
                                            value="{{ old('first_name') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name"
                                            class="form-control @error('last_name') is-invalid @enderror"
                                            placeholder="Dela Cruz"
                                            value="{{ old('last_name') }}">
                                    </div>

                                    <!-- EMAIL & CONTACT -->
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="you@example.com"
                                            value="{{ old('email') }}">
                                        <div class="invalid-feedback" id="email-error"></div>
                                        <small class="text-muted" id="email-check-status"></small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" name="contact"
                                            class="form-control @error('contact') is-invalid @enderror"
                                            placeholder="09XXXXXXXXX"
                                            value="{{ old('contact') }}">
                                    </div>

                                    <!-- PASSWORDS -->
                                    <div class="col-md-6">
                                        <label class="form-label">Password</label>
                                        <div class="position-relative">
                                            <input type="password" name="password" id="password"
                                                class="form-control @error('password') is-invalid @enderror">
                                            <button type="button" class="btn btn-link" id="togglePassword">
                                                <i class="bi bi-eye" id="passwordEyeIcon"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <small class="text-muted" id="passwordHint"></small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="position-relative">
                                            <input type="password" name="password_confirmation"
                                                id="password_confirmation"
                                                class="form-control @error('password_confirmation') is-invalid @enderror">
                                            <button type="button" class="btn btn-link" id="togglePasswordConfirmation">
                                                <i class="bi bi-eye" id="passwordConfirmationEyeIcon"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted" id="passwordMatch"></small>
                                    </div>
                                @endguest

                                <!-- BIRTHDATE -->
                                <div class="col-md-6">
                                    <label class="form-label">Birthdate</label>
                                    <input type="date" name="birthdate" class="form-control @error('birthdate') is-invalid @enderror" value="{{ old('birthdate') }}" required>
                                </div>

                                <!-- ADDRESS & UNIT TYPE -->
                                <div class="col-md-6">
                                    <label class="form-label">Current Address</label>
                                    <input type="text" name="current_address" class="form-control @error('current_address') is-invalid @enderror" placeholder="Street, City" value="{{ old('current_address') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Unit Type</label>
                                    <input type="text" name="unit_type" id="unitModalType" class="form-control @error('unit_type') is-invalid @enderror" value="{{ old('unit_type') }}" readonly>
                                </div>

                                <!-- FILTERED ROOM SELECTION -->
                                <div class="col-md-12">
                                    <label class="form-label">Select Room</label>
                                    <select name="unit_id" id="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
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
                                </div>

                                <!-- BED SELECTION (for Bed-Spacer units only) -->
                                <div class="col-md-12 d-none" id="bedSelectionGroup">
                                    <label class="form-label">Select Bed</label>
                                    <select name="bed_number" id="bed_number" class="form-select @error('bed_number') is-invalid @enderror">
                                        <option value="">Select a bed</option>
                                    </select>
                                    @error('bed_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- MOVE-IN DATE & REASON -->
                                <!-- <div class="col-md-6">
                                    <label class="form-label">Preferred Move-in Date</label>
                                    <input type="date" name="move_in_date" class="form-control @error('move_in_date') is-invalid @enderror" value="{{ old('move_in_date') }}" required>
                                </div> -->
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Move-in Date</label>

                                    <input
                                        type="date"
                                        name="move_in_date"
                                        class="form-control @error('move_in_date') is-invalid @enderror"
                                        value="{{ old('move_in_date') }}"
                                        min="{{ now()->toDateString() }}"
                                        max="{{ now()->addWeeks(2)->toDateString() }}"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reason for Renting</label>
                                    <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" placeholder="For work, school, etc." value="{{ old('reason') }}" required>
                                </div>

                                <!-- EMPLOYMENT INFO -->
                                <div class="col-md-6">
                                    <label class="form-label">Employment Status</label>
                                    <select name="employment_status" class="form-select @error('employment_status') is-invalid @enderror" id="employment_status" required>
                                        <option value="">Select</option>
                                        <option value="Employed" {{ old('employment_status') == 'Employed' ? 'selected' : '' }}>Employed</option>
                                        <option value="Unemployed" {{ old('employment_status') == 'Unemployed' ? 'selected' : '' }}>Unemployed</option>
                                        <option value="Student" {{ old('employment_status') == 'Student' ? 'selected' : '' }}>Student</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Employer or School</label>
                                    <input type="text" name="employer_school" class="form-control @error('employer_school') is-invalid @enderror" value="{{ old('employer_school') }}" required>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Source of Income</label>
                                    <input type="text" name="source_of_income" id="source_of_income" class="form-control @error('source_of_income') is-invalid @enderror" value="{{ old('source_of_income') }}" required>
                                </div>

                                <div class="col-md-6 d-none" id="monthlyIncomeGroup">
                                    <label class="form-label">Monthly Income / Wages</label>
                                    <input type="number" name="monthly_income" id="monthly_income"
                                        class="form-control"
                                        placeholder="Enter monthly income"
                                        min=0
                                    >
                                </div>

                                <!-- EMERGENCY CONTACT -->
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Contact Name</label>
                                    <input type="text" name="emergency_name" class="form-control @error('emergency_name') is-invalid @enderror" value="{{ old('emergency_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Number</label>
                                    <input type="tel"
                                           name="emergency_number"
                                           class="form-control @error('emergency_number') is-invalid @enderror"
                                           required
                                           maxlength="15"
                                           minlength="10"
                                           pattern="^[0-9]{10,15}$"
                                           inputmode="numeric"
                                           title="Emergency contact must be 10–15 digits"
                                           value="{{ old('emergency_number') }}"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Relationship</label>
                                    <input type="text" name="emergency_relationship" class="form-control @error('emergency_relationship') is-invalid @enderror" value="{{ old('emergency_relationship') }}" required>
                                </div>

                                <!-- FILE UPLOADS -->
                                <div class="col-md-6">
                                    <label class="form-label">Valid ID</label>
                                    <input type="file" name="valid_id" id="valid_id" class="form-control @error('valid_id') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                                    @error('valid_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="valid_id_preview" class="mt-2"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">1x1 ID Picture</label>
                                    <input type="file" name="id_picture" id="id_picture" class="form-control @error('id_picture') is-invalid @enderror" accept="image/*" required>
                                    @error('id_picture')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="id_picture_preview" class="mt-2"></div>
                                </div>

                                <div class="form-check d-flex justify-content-center align-items-center gap-2 mb-4 mt-4">
                                    <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }} required>
                                        <label class="form-check-label mb-0" for="terms">
                                                I have read and agree to the
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-underline">
                                                Terms and Conditions
                                            </a>.
                                        </label>
                                    @error('terms')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-gradient px-4">Submit Application</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="height: 80vh;">
        <iframe src="{{ asset('storage/assets/tenant_agreement.pdf') }}" width="100%" height="100%" style="border:none;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Zoom Image Modal -->
<div class="modal fade" id="unitImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body p-0">
                <img id="unitImageZoom" src="" alt="Unit Image Zoomed" class="img-fluid rounded-4 w-100">
            </div>
        </div>
    </div>
</div>


<!-- Additional Styles -->
@push('styles')
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
    /* Card Hover Effect */
    .hover-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }
    .hover-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 16px 30px rgba(0,0,0,0.15);
    }

    /* Glass Card */
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 1rem;
        box-shadow: 0 6px 25px rgba(49, 7, 236, 0.28);
    }

    /* Gradient Text */
    .text-gradient {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Buttons */
    .btn-gradient {
        background: linear-gradient(135deg, #01017c, #2d3b9a);
        color: #fff;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(1,1,124,0.25);
    }

    /* Form Validation Styles */
    .form-control.is-valid,
    .form-select.is-valid {
        border-color: #198754;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.8rem;
        color: #dc3545;
        animation: fadeIn 0.2s ease-in-out;
    }

    .form-check-input.is-invalid {
        border-color: #dc3545;
    }

    .form-check-input.is-valid {
        border-color: #198754;
    }

    .form-check-input.is-invalid ~ .form-check-label {
        color: #dc3545;
    }

    .validation-alert {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* File input validation */
    input[type="file"].is-valid {
        border-color: #198754;
    }

    input[type="file"].is-invalid {
        border-color: #dc3545;
    }

    /* Password strength indicator */
    .password-strength {
        height: 4px;
        margin-top: 5px;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .password-strength.weak { background: linear-gradient(to right, #dc3545 33%, #e9ecef 33%); }
    .password-strength.medium { background: linear-gradient(to right, #ffc107 66%, #e9ecef 66%); }
    .password-strength.strong { background: #198754; }

    /* Password toggle button */
    #togglePassword,
    #togglePasswordConfirmation {
        position: absolute !important;
        right: 0.5rem !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #6c757d;
        padding: 0.375rem 0.5rem;
        cursor: pointer;
        transition: color 0.2s ease;
        z-index: 10 !important;
        border: none !important;
        background: none !important;
        text-decoration: none !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    #togglePassword:hover,
    #togglePasswordConfirmation:hover {
        color: #495057;
    }

    #togglePassword:focus,
    #togglePasswordConfirmation:focus {
        outline: none;
        box-shadow: none;
    }

    /* Add padding to password inputs to make room for toggle button */
    #password,
    #password_confirmation {
        padding-right: 2.5rem;
    }

    /* Remove validation icons (exclamation) from password fields */
    #password.is-invalid,
    #password_confirmation.is-invalid {
        background-image: none !important;
        padding-right: 2.5rem;
    }

    #password.is-valid,
    #password_confirmation.is-valid {
        background-image: none !important;
        padding-right: 2.5rem;
    }
</style>
@endpush

@push('scripts')
<script>

document.addEventListener('DOMContentLoaded', function () {
    const termsModal = document.getElementById('termsModal');
    const unitModal = document.getElementById('unitModal');

    // Flag to track if Terms modal is opening (to prevent form reset)
    window.isTermsModalOpening = false;

    // When "Terms and Conditions" modal opens → hide the registration modal
    termsModal.addEventListener('show.bs.modal', function () {
        window.isTermsModalOpening = true; // Set flag before hiding
        const modal = bootstrap.Modal.getInstance(unitModal);
        if (modal) modal.hide();
    });

    // When closing Terms modal → reopen Registration modal
    termsModal.addEventListener('hidden.bs.modal', function () {
        const modal = new bootstrap.Modal(unitModal);
        modal.show();
        // Clear flag after reopening
        setTimeout(() => {
            window.isTermsModalOpening = false;
        }, 100);
    });

    // Auto-open modal if there are validation errors
    @if($errors->any() || session('error'))
        const unitModalInstance = new bootstrap.Modal(unitModal);
        unitModalInstance.show();
    @endif
});

document.addEventListener('DOMContentLoaded', function() {
    const unitModalEl = document.getElementById('unitModal');
    const zoomModalEl = document.getElementById('unitImageModal');
    const zoomImg = document.getElementById('unitImageZoom');

    // Bootstrap modal instances
    const zoomModal = new bootstrap.Modal(zoomModalEl);

    const unitInfo = {
        "Studio": { size: "25 m²", capacity: "2 persons" },
        "1 Bedroom": { size: "35 m²", capacity: "2–3 persons" },
        "2-Bedroom": { size: "45 m²", capacity: "4 persons" }
    };

    unitModalEl.addEventListener('show.bs.modal', event => {
        const card = event.relatedTarget; // clicked card (null if opened programmatically)

        // Skip if modal is opened programmatically (e.g., for validation errors)
        if (!card) return;

        const type = card.dataset.type;
        const image = card.dataset.image;
        const available = card.dataset.available;
        const price = card.dataset.price;
        const status = card.dataset.status;
        const unitId = card.dataset.unitId;
        const capacity = card.dataset.capacity;
        const noOccupants = card.dataset.noOccupants;

        document.getElementById('unitModalTitle').textContent = `${type} Unit`;
        const modalImg = document.getElementById('unitModalImg');
        modalImg.src = image;
        document.getElementById('unitModalTypeText').textContent = `${type} Unit`;

        const info = unitInfo[type] || { size: "N/A", capacity: "N/A" };
        document.getElementById('unitModalSize').textContent = info.size;
        document.getElementById('unitModalCapacity').textContent = type === 'Bed-Spacer' ? `${noOccupants || 0}/${capacity}` : capacity;

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

        // Filter available rooms by unit type dynamically
        const select = document.getElementById('unit_id');

        // Reset room list
        [...select.options].forEach(opt => {
            if (opt.value === "") return; // Skip "Select a room"
            const matchesType = opt.dataset.type === type;
            opt.hidden = !matchesType; // Hide rooms that don't match
        });

        // Automatically select blank option
        select.value = "";

        // Hide bed selection when modal opens
        const bedSelectionGroup = document.getElementById('bedSelectionGroup');
        if (bedSelectionGroup) {
            bedSelectionGroup.classList.add('d-none');
            const bedSelect = document.getElementById('bed_number');
            if (bedSelect) bedSelect.value = "";
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const employmentSelect = document.getElementById('employment_status');
    const incomeInput = document.getElementById('source_of_income');
    const monthlyIncomeGroup = document.getElementById('monthlyIncomeGroup');

    function handleEmploymentChange() {
        const value = employmentSelect.value;

        if (value === 'Student') {
            incomeInput.value = 'Allowance';
            incomeInput.setAttribute('readonly', true);
            monthlyIncomeGroup.classList.add('d-none');
        }

        else if (value === 'Employed') {
            incomeInput.value = '';
            incomeInput.removeAttribute('readonly');
            monthlyIncomeGroup.classList.remove('d-none');
        }

        else {
            incomeInput.value = '';
            incomeInput.removeAttribute('readonly');
            monthlyIncomeGroup.classList.add('d-none');
        }
    }

    employmentSelect.addEventListener('change', handleEmploymentChange);

    // ✅ Run on page load in case of validation errors
    handleEmploymentChange();
});

// ==================== BED SELECTION HANDLING ====================
document.addEventListener('DOMContentLoaded', function() {
    const unitSelect = document.getElementById('unit_id');
    const bedSelectionGroup = document.getElementById('bedSelectionGroup');
    const bedSelect = document.getElementById('bed_number');

    function populateBeds() {
        const selectedOption = unitSelect.options[unitSelect.selectedIndex];

        if (!selectedOption || selectedOption.dataset.type !== 'Bed-Spacer') {
            // Hide bed selection for non-Bed-Spacer units
            bedSelectionGroup.classList.add('d-none');
            bedSelect.required = false;
            bedSelect.innerHTML = '<option value="">Select a bed</option>';
            bedSelect.value = "";
            return;
        }

        // Show bed selection for Bed-Spacer units
        bedSelectionGroup.classList.remove('d-none');
        bedSelect.required = true;

        // Get capacity and taken beds
        const capacity = parseInt(selectedOption.dataset.capacity || '0', 10);
        const takenBeds = JSON.parse(selectedOption.dataset.takenBeds || '[]').map(Number);

        // Calculate upper and lower bed counts
        const upperCount = Math.floor(capacity / 2);
        const lowerCount = capacity - upperCount;

        // Build bed options - only include available beds
        let optionsMarkup = '<option value="">Select a bed</option>';

        // Upper beds (first half): 1 to upperCount
        for (let i = 1; i <= upperCount; i++) {
            // Skip taken beds - don't show them in the dropdown
            if (!takenBeds.includes(i)) {
                const label = `Bed ${i} - Upper`;
                optionsMarkup += `<option value="${i}">${label}</option>`;
            }
        }

        // Lower beds (second half): upperCount+1 to capacity
        for (let i = upperCount + 1; i <= capacity; i++) {
            // Skip taken beds - don't show them in the dropdown
            if (!takenBeds.includes(i)) {
                const label = `Bed ${i} - Lower`;
                optionsMarkup += `<option value="${i}">${label}</option>`;
            }
        }

        bedSelect.innerHTML = optionsMarkup;

        // Restore old value if it exists and is still available
        const oldValue = bedSelect.getAttribute('data-old-value');
        if (oldValue && !takenBeds.includes(Number(oldValue))) {
            bedSelect.value = oldValue;
        } else {
            bedSelect.value = "";
        }
        bedSelect.removeAttribute('data-old-value');
    }

    if (unitSelect && bedSelectionGroup && bedSelect) {
        unitSelect.addEventListener('change', populateBeds);

        // Check on page load if a Bed-Spacer is already selected (e.g., after validation error)
        if (unitSelect.value) {
            populateBeds();
            // Restore old bed_number if it exists
            const oldBedNumber = "{{ old('bed_number') }}";
            if (oldBedNumber) {
                bedSelect.setAttribute('data-old-value', oldBedNumber);
                populateBeds();
            }
        }
    }
});

// ==================== FORM VALIDATION ====================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#unitModal form');

    // Validation rules and messages
    const validationRules = {
        first_name: {
            validate: (value) => value.trim().length >= 2,
            message: 'First name must be at least 2 characters'
        },
        last_name: {
            validate: (value) => value.trim().length >= 2,
            message: 'Last name must be at least 2 characters'
        },
        email: {
            validate: (value) => {
                // Basic format check
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return false;
                }
                // Check if email has been validated and is available
                const emailField = form.querySelector('[name="email"]');
                const emailAvailable = emailField.getAttribute('data-email-available');
                // If email hasn't been checked yet, return true (format is valid)
                // If it has been checked, return true only if available
                return emailAvailable === null || emailAvailable === 'true';
            },
            message: 'Please enter a valid email address'
        },
        contact: {
            validate: (value) => {
                // Allow 10-15 digits (remove non-digits for check)
                const cleaned = value.replace(/[^0-9]/g, '');
                return cleaned.length >= 10 && cleaned.length <= 15;
            },
            message: 'Contact number must be 10-15 digits'
        },
        password: {
            validate: (value) => {
                // Must be 8+ chars with uppercase, lowercase, number, and special character
                return value.length >= 8 &&
                       /[a-z]/.test(value) &&
                       /[A-Z]/.test(value) &&
                       /\d/.test(value) &&
                       /[\W_]/.test(value);
            },
            message: 'Password must be at least 8 characters with uppercase, lowercase, number, and special character'
        },
        password_confirmation: {
            validate: (value, form) => {
                const password = form.querySelector('[name="password"]').value;
                return value === password && value.length > 0;
            },
            message: 'Passwords do not match'
        },
        birthdate: {
            validate: (value) => {
                if (!value) return false;
                const birthDate = new Date(value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 18 && age <= 100;
            },
            message: 'You must be at least 18 years old'
        },
        current_address: {
            validate: (value) => value.trim().length >= 5,
            message: 'Please enter a complete address (at least 5 characters)'
        },
        unit_id: {
            validate: (value) => value !== '',
            message: 'Please select a room'
        },
        bed_number: {
            validate: (value, form) => {
                const unitSelect = form.querySelector('[name="unit_id"]');
                const selectedOption = unitSelect.options[unitSelect.selectedIndex];
                // Only validate if Bed-Spacer is selected
                if (selectedOption && selectedOption.dataset.type === 'Bed-Spacer') {
                    return value !== '';
                }
                return true; // Not required for non-Bed-Spacer units
            },
            message: 'Please select a bed for Bed-Spacer units'
        },
        move_in_date: {
            validate: (value) => {
                if (!value) return false;
                const moveInDate = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return moveInDate >= today;
            },
            message: 'Move-in date must be today or in the future'
        },
        reason: {
            validate: (value) => value.trim().length >= 3,
            message: 'Please provide a reason (at least 3 characters)'
        },
        employment_status: {
            validate: (value) => value !== '',
            message: 'Please select your employment status'
        },
        employer_school: {
            validate: (value) => value.trim().length >= 2,
            message: 'Please enter your employer or school name'
        },
        source_of_income: {
            validate: (value) => value.trim().length >= 2,
            message: 'Please specify your source of income'
        },
        emergency_name: {
            validate: (value) => value.trim().length >= 2,
            message: 'Please enter emergency contact name'
        },
        emergency_number: {
            validate: (value) => /^[0-9]{10,15}$/.test(value),
            message: 'Emergency number must be 10-15 digits'
        },
        emergency_relationship: {
            validate: (value) => value.trim().length >= 2,
            message: 'Please specify the relationship'
        },
        valid_id: {
            validate: (value, form) => {
                const input = form.querySelector('[name="valid_id"]');
                if (!input.files || input.files.length === 0) return false;
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                // Also check file extension as fallback
                const ext = file.name.split('.').pop().toLowerCase();
                const allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
                return (allowedTypes.includes(file.type) || allowedExts.includes(ext)) && file.size <= maxSize;
            },
            message: 'Please upload a valid ID (JPG, PNG, or PDF, max 5MB)'
        },
        id_picture: {
            validate: (value, form) => {
                const input = form.querySelector('[name="id_picture"]');
                if (!input.files || input.files.length === 0) return false;
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                // Also check file extension as fallback
                const ext = file.name.split('.').pop().toLowerCase();
                const allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                return (allowedTypes.includes(file.type) || allowedExts.includes(ext)) && file.size <= maxSize;
            },
            message: 'Please upload a valid image (JPG, PNG, GIF, or WebP, max 2MB)'
        },
        terms: {
            validate: (value, form) => form.querySelector('[name="terms"]').checked,
            message: 'You must agree to the Terms and Conditions'
        }
    };

    // Create error message element
    function createErrorElement(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.textContent = message;
        return errorDiv;
    }

    // Show error for a field
    function showError(field, message) {
        clearError(field);
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');

        // Don't show validation error message for password fields
        // The passwordHint and updatePasswordMatch functions handle the message display
        if (field.name === 'password' || field.name === 'password_confirmation') {
            return;
        }

        const errorElement = createErrorElement(message);

        // For checkboxes, append after the parent form-check div
        if (field.type === 'checkbox') {
            field.closest('.form-check').appendChild(errorElement);
        } else {
            field.parentNode.appendChild(errorElement);
        }
    }

    // Clear error for a field
    function clearError(field) {
        field.classList.remove('is-invalid');
        const parent = field.type === 'checkbox' ? field.closest('.form-check') : field.parentNode;
        const existingError = parent.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
    }

    // Show success for a field
    function showSuccess(field) {
        clearError(field);
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
    }

    // Validate a single field
    function validateField(field) {
        const fieldName = field.name;
        const rule = validationRules[fieldName];

        if (!rule) return true;

        const value = field.type === 'file' ? field.value : field.value;
        const isValid = rule.validate(value, form);

        if (isValid) {
            showSuccess(field);
        } else {
            showError(field, rule.message);
        }

        return isValid;
    }

    // Add real-time validation to all form fields
    const formFields = form.querySelectorAll('input, select');
    formFields.forEach(field => {
        // Skip real-time validation for password fields (we use passwordHint instead)
        if (field.name === 'password' || field.name === 'password_confirmation') {
            // Only validate on blur for password fields
            field.addEventListener('blur', function() {
                validateField(this);
            });
            return;
        }

        // Validate on blur (when leaving field)
        field.addEventListener('blur', function() {
            validateField(this);
        });

        // Validate on input/change for better UX
        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });

        field.addEventListener('change', function() {
            validateField(this);
        });
    });

    // Password confirmation real-time check
    const passwordField = form.querySelector('[name="password"]');
    const confirmField = form.querySelector('[name="password_confirmation"]');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordHint = document.getElementById('passwordHint');
    const passwordMatch = document.getElementById('passwordMatch');

    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;
        return strength;
    }

    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);

        passwordStrength.className = 'password-strength';

        if (password.length === 0) {
            passwordStrength.className = 'password-strength';
            passwordHint.textContent = 'Must have uppercase, lowercase, number & special char';
            passwordHint.className = 'text-muted';
        } else if (strength <= 1) {
            passwordStrength.classList.add('weak');
            passwordHint.textContent = 'Weak - Add uppercase, numbers, or symbols';
            passwordHint.className = 'text-danger';
        } else if (strength <= 2) {
            passwordStrength.classList.add('medium');
            passwordHint.textContent = 'Medium - Add more variety';
            passwordHint.className = 'text-warning';
        } else {
            passwordStrength.classList.add('strong');
            passwordHint.textContent = 'Strong password!';
            passwordHint.className = 'text-success';
        }

        if (confirmField.value) {
            validateField(confirmField);
            updatePasswordMatch();
        }
    });

    // Set initial hint
    passwordHint.textContent = 'Must have uppercase, lowercase, number & special char';
    passwordHint.className = 'text-muted';

    // Password match indicator
    function updatePasswordMatch() {
        if (confirmField.value.length === 0) {
            passwordMatch.textContent = '';
            return;
        }

        if (passwordField.value === confirmField.value) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.className = 'text-success';
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.className = 'text-danger';
        }
    }

    confirmField.addEventListener('input', updatePasswordMatch);

    // ==================== PASSWORD VISIBILITY TOGGLE ====================
    const togglePasswordBtn = document.getElementById('togglePassword');
    const togglePasswordConfirmationBtn = document.getElementById('togglePasswordConfirmation');
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordEyeIcon = document.getElementById('passwordEyeIcon');
    const passwordConfirmationEyeIcon = document.getElementById('passwordConfirmationEyeIcon');

    // Toggle password visibility
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle eye icon
            if (type === 'password') {
                passwordEyeIcon.classList.remove('bi-eye-slash');
                passwordEyeIcon.classList.add('bi-eye');
            } else {
                passwordEyeIcon.classList.remove('bi-eye');
                passwordEyeIcon.classList.add('bi-eye-slash');
            }
        });
    }

    // Toggle confirm password visibility
    if (togglePasswordConfirmationBtn && passwordConfirmationInput) {
        togglePasswordConfirmationBtn.addEventListener('click', function() {
            const type = passwordConfirmationInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirmationInput.setAttribute('type', type);

            // Toggle eye icon
            if (type === 'password') {
                passwordConfirmationEyeIcon.classList.remove('bi-eye-slash');
                passwordConfirmationEyeIcon.classList.add('bi-eye');
            } else {
                passwordConfirmationEyeIcon.classList.remove('bi-eye');
                passwordConfirmationEyeIcon.classList.add('bi-eye-slash');
            }
        });
    }

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isFormValid = true;
        let firstInvalidField = null;
        let invalidFields = [];

        // Validate all fields
        formFields.forEach(field => {
            if (field.name && validationRules[field.name]) {
                const isValid = validateField(field);
                if (!isValid) {
                    invalidFields.push(field.name);
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
                isFormValid = isFormValid && isValid;
            }
        });

        // Debug: Log which fields are invalid
        if (invalidFields.length > 0) {
            console.log('Invalid fields:', invalidFields);
        }

        if (!isFormValid) {
            e.preventDefault();

            // Scroll to first invalid field
            if (firstInvalidField) {
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField.focus();
            }

            // Show summary alert with specific fields
            const fieldList = invalidFields.join(', ');
            showValidationAlert(`Please correct the following fields: ${fieldList}`);
        }
    });

    // Show validation alert
    function showValidationAlert(message) {
        // Remove existing alert if any
        const existingAlert = form.querySelector('.validation-alert');
        if (existingAlert) existingAlert.remove();

        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show validation-alert mt-3';
        alertDiv.innerHTML = `
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Validation Error</strong>
            <p class="mb-0 mt-1">${message}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        form.insertBefore(alertDiv, form.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 150);
            }
        }, 5000);
    }

    // Reset validation when modal is hidden
    const unitModalEl = document.getElementById('unitModal');
    unitModalEl.addEventListener('hidden.bs.modal', function() {
        // Don't reset form if Terms modal is opening (to preserve form values)
        if (window.isTermsModalOpening) {
            return;
        }

        // Only reset if there are no validation errors
        if (!form.querySelector('.is-invalid')) {
            form.reset();
            formFields.forEach(field => {
                field.classList.remove('is-valid', 'is-invalid');
                clearError(field);
            });
            const alert = form.querySelector('.validation-alert');
            if (alert) alert.remove();
            // Clear image previews
            document.getElementById('valid_id_preview').innerHTML = '';
            document.getElementById('id_picture_preview').innerHTML = '';
        }
    });
});

// ==================== REAL-TIME EMAIL VALIDATION ====================
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    const emailStatus = document.getElementById('email-check-status');
    let emailCheckTimeout;

    if (emailInput) {
        // Check email on blur and after typing stops
        emailInput.addEventListener('blur', function() {
            checkEmailAvailability(this.value);
        });

        emailInput.addEventListener('input', function() {
            const email = this.value.trim();

            // Clear previous timeout
            clearTimeout(emailCheckTimeout);

            // Clear status messages and data attribute
            emailStatus.textContent = '';
            emailError.textContent = '';
            emailInput.classList.remove('is-invalid', 'is-valid');
            emailInput.removeAttribute('data-email-available');

            // Basic email format validation
            if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                // Wait 500ms after user stops typing before checking
                emailCheckTimeout = setTimeout(() => {
                    checkEmailAvailability(email);
                }, 500);
            }
        });
    }

    function checkEmailAvailability(email) {
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return;
        }

        // Show loading state
        emailStatus.textContent = 'Checking availability...';
        emailStatus.className = 'text-muted';

        fetch('{{ route("register.check-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                emailInput.classList.remove('is-invalid');
                emailInput.classList.add('is-valid');
                emailInput.setAttribute('data-email-available', 'true');
                emailStatus.textContent = '✓ Email is available';
                emailStatus.className = 'text-success';
                emailError.textContent = '';
            } else {
                emailInput.classList.remove('is-valid');
                emailInput.classList.add('is-invalid');
                emailInput.setAttribute('data-email-available', 'false');
                emailError.textContent = data.message;
                emailStatus.textContent = '';
            }
        })
        .catch(error => {
            console.error('Error checking email:', error);
            emailStatus.textContent = '';
        });
    }
});

// ==================== IMAGE PREVIEW & PERSISTENCE ====================
document.addEventListener('DOMContentLoaded', function() {
    const validIdInput = document.getElementById('valid_id');
    const idPictureInput = document.getElementById('id_picture');
    const validIdPreview = document.getElementById('valid_id_preview');
    const idPicturePreview = document.getElementById('id_picture_preview');

    // Store file data in sessionStorage for persistence across page reloads
    const STORAGE_KEY_VALID_ID = 'form_valid_id_data';
    const STORAGE_KEY_ID_PICTURE = 'form_id_picture_data';

    // Function to show preview
    function showPreview(input, previewContainer, isImage, fileData = null) {
        const file = fileData || (input.files && input.files[0]);
        if (!file) return;

        // Store file data in sessionStorage for persistence
        if (input.id === 'valid_id' && !fileData && input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                sessionStorage.setItem(STORAGE_KEY_VALID_ID, JSON.stringify({
                    name: file.name,
                    type: file.type,
                    data: e.target.result
                }));
            };
            reader.readAsDataURL(file);
        } else if (input.id === 'id_picture' && !fileData && input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                sessionStorage.setItem(STORAGE_KEY_ID_PICTURE, JSON.stringify({
                    name: file.name,
                    type: file.type,
                    data: e.target.result
                }));
            };
            reader.readAsDataURL(file);
        }

        if (isImage || (fileData && fileData.type && fileData.type.startsWith('image/'))) {
            const imageData = fileData ? fileData.data : null;
            if (imageData) {
                previewContainer.innerHTML = `
                    <div class="position-relative d-inline-block">
                        <img src="${imageData}" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="clearFilePreview('${input.id}')" style="border-radius: 50%; width: 25px; height: 25px; padding: 0; line-height: 1;">×</button>
                    </div>
                    <div class="text-warning small mt-1"><i class="bi bi-exclamation-triangle"></i> Please reselect this file</div>
                `;
            } else {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `
                        <div class="position-relative d-inline-block">
                            <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="clearFilePreview('${input.id}')" style="border-radius: 50%; width: 25px; height: 25px; padding: 0; line-height: 1;">×</button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        } else {
            // For PDF files
            const fileName = fileData ? fileData.name : file.name;
            previewContainer.innerHTML = `
                <div class="position-relative d-inline-block">
                    <div class="alert alert-info p-2 mb-0">
                        <i class="bi bi-file-pdf"></i> ${fileName}
                        <button type="button" class="btn btn-sm btn-danger ms-2" onclick="clearFilePreview('${input.id}')">Remove</button>
                    </div>
                    <div class="text-warning small mt-1"><i class="bi bi-exclamation-triangle"></i> Please reselect this file</div>
                </div>
            `;
        }
    }

    // Function to clear preview
    window.clearFilePreview = function(inputId) {
        const input = document.getElementById(inputId);
        const previewContainer = inputId === 'valid_id' ? validIdPreview : idPicturePreview;

        input.value = '';
        previewContainer.innerHTML = '';

        // Clear sessionStorage
        if (inputId === 'valid_id') {
            sessionStorage.removeItem(STORAGE_KEY_VALID_ID);
        } else if (inputId === 'id_picture') {
            sessionStorage.removeItem(STORAGE_KEY_ID_PICTURE);
        }

        // Remove validation classes
        input.classList.remove('is-valid', 'is-invalid');
    };

    // Restore previews from sessionStorage if validation errors occurred
    @if($errors->any())
        // Restore valid_id preview if exists
        const storedValidId = sessionStorage.getItem(STORAGE_KEY_VALID_ID);
        if (storedValidId && validIdInput && validIdPreview) {
            try {
                const fileData = JSON.parse(storedValidId);
                showPreview(validIdInput, validIdPreview, fileData.type.startsWith('image/'), fileData);
            } catch (e) {
                console.error('Error restoring valid_id preview:', e);
            }
        }

        // Restore id_picture preview if exists
        const storedIdPicture = sessionStorage.getItem(STORAGE_KEY_ID_PICTURE);
        if (storedIdPicture && idPictureInput && idPicturePreview) {
            try {
                const fileData = JSON.parse(storedIdPicture);
                showPreview(idPictureInput, idPicturePreview, true, fileData);
            } catch (e) {
                console.error('Error restoring id_picture preview:', e);
            }
        }
    @endif

    // Handle file selection
    if (validIdInput) {
        validIdInput.addEventListener('change', function() {
            const isImage = this.files[0]?.type.startsWith('image/');
            showPreview(this, validIdPreview, isImage);
        });
    }

    if (idPictureInput) {
        idPictureInput.addEventListener('change', function() {
            showPreview(this, idPicturePreview, true);
        });
    }

    // Clear storage when modal is closed (only if no errors and Terms modal is not opening)
    const unitModalEl = document.getElementById('unitModal');
    if (unitModalEl) {
        unitModalEl.addEventListener('hidden.bs.modal', function() {
            // Don't clear storage if Terms modal is opening (to preserve file previews)
            if (window.isTermsModalOpening) {
                return;
            }

            @if(!$errors->any())
                // Only clear if there are no errors
                sessionStorage.removeItem(STORAGE_KEY_VALID_ID);
                sessionStorage.removeItem(STORAGE_KEY_ID_PICTURE);
            @endif
        });
    }
});
</script>
@endpush


@endSection
