<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TenantApplication;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        $unitTypes = ['Studio', '1-Bedroom', '2-Bedroom', 'Commercial', 'Bed-Spacer'];

         $availableUnits = Unit::with([
            'leases' => fn($query) => $query->whereIn('lea_status', ['active', 'pending'])
        ])
        ->withCount([
            'leases as application_count' => fn($query) =>
                $query->where('lea_status', 'pending')
        ])
        ->where(function ($query) {
            $query->where('status', 'vacant')
                ->orWhere(function ($bedQuery) {
                    $bedQuery->where('type', 'Bed-Spacer')
                        ->whereColumn('no_of_occupants', '<', 'capacity');
                });
        })
        ->get();

        $pendingBedReservations = TenantApplication::select('unit_id', 'bed_number')
            ->whereNotNull('bed_number')
            ->whereHas('user', function ($query) {
                $query->whereIn('status', ['pending', 'approved']);
            })
            ->get()
            ->groupBy('unit_id')
            ->map(fn($group) => $group->pluck('bed_number')->filter()->unique());

        $availableUnits->each(function ($unit) use ($pendingBedReservations) {
            $leaseBeds = $unit->leases->pluck('bed_number')->filter();
            $pendingBeds = $pendingBedReservations->get($unit->id, collect());
            $unit->setAttribute(
                'taken_beds',
                $leaseBeds->merge($pendingBeds)->filter()->unique()->values()
            );
        });

        return view('auth.register', compact('unitTypes', 'availableUnits'));
    }

//     public function register(Request $request)
// {
//     // Validate input
//     $request->validate([
//         // User info
//         'first_name' => 'required|string|max:255',
//         'last_name'  => 'required|string|max:255',
//         'email'    => 'required|email|unique:users,email',
//         'contact'  => 'required|digits_between:10,15',
//         'password' => [
//             'required','string','min:8','confirmed',
//             'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
//         ],

//         // Tenant application
//         'current_address' => 'required|string|max:500',
//         'birthdate'       => 'required|date|before:today',
//         'unit_type'       => 'required|string|max:100',
//         'unit_id'         => 'required|exists:units,id',
//         'bed_number'      => 'nullable|integer|min:1|required_if:unit_type,Bed-Spacer',
//         'move_in_date'    => 'required|date|after_or_equal:today',
//         'reason'          => 'required|string|max:1000',
//         'employment_status'=> 'required|string|in:Employed,Unemployed,Student',
//         'employer_school' => 'required|string|max:255',
//         'source_of_income'=> 'required|string|max:255',
//         'emergency_name'  => 'required|string|max:255',
//         'emergency_number'=> 'required|digits_between:10,15',
//         'emergency_relationship'=> 'required|string|max:100',
//         'valid_id'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
//         'id_picture'      => 'required|image|max:5120',
//     ], [
//         // Custom error messages
//         'email.unique' => 'This email is already registered. Please use a different email or login to your existing account.',
//         'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
//         'birthdate.before' => 'Please enter a valid birthdate.',
//         'unit_type.in' => 'Please select a valid unit type.',
//         'employment_status.in' => 'Please select a valid employment status.',
//         'bed_number.required_if' => 'Please select a bed number for bed-spacer units.',
//         'valid_id.max' => 'Valid ID file must not exceed 5MB.',
//         'id_picture.max' => 'ID picture must not exceed 5MB.',
//         'move_in_date.after_or_equal' => 'Move-in date must be today or a future date.',
//     ]);

//     // Fetch the selected unit with current leases for bed tracking
//     $unit = Unit::with(['leases' => fn($query) => $query->whereIn('lea_status', ['active', 'pending'])])
//         ->find($request->unit_id);

//     if (!$unit) {
//         return redirect()->back()
//             ->withInput()
//             ->with('error', 'Selected unit could not be found.');
//     }

//     $bedNumber = null;
//     if ($unit->type === 'Bed-Spacer') {
//         $bedNumber = (int) $request->bed_number;

//         if (!$bedNumber) {
//             return redirect()->back()
//                 ->withInput()
//                 ->with('error', 'Please select an available bed number for this unit.');
//         }

//         // Validate bed number is within capacity range (1 to capacity)
//         if ($bedNumber < 1 || $bedNumber > $unit->capacity) {
//             return redirect()->back()
//                 ->withInput()
//                 ->with('error', 'Invalid bed number. Bed number must be between 1 and ' . $unit->capacity . '.');
//         }

//         $takenBeds = $this->getTakenBeds($unit);

//         if (count($takenBeds) >= $unit->capacity) {
//             return redirect()->back()
//                 ->withInput()
//                 ->with('error', 'This bed-spacer unit is full. Please select another one.');
//         }

//         if (in_array($bedNumber, $takenBeds, true)) {
//             return redirect()->back()
//                 ->withInput()
//                 ->with('error', 'The selected bed is no longer available. Please pick another one.');
//         }
//     } elseif ($unit->status === 'occupied') {
//         return redirect()->back()
//             ->withInput()
//             ->with('error', 'The selected room is already occupied. Please choose another one.');
//     }

//     // Upload files
//     $validIdPath = $request->file('valid_id')->store('tenant_ids', 'public');
//     $idPicturePath = $request->file('id_picture')->store('tenant_pictures', 'public');

//     // Create user
//     $user = User::create([
//         'first_name'     => $request->first_name,
//         'last_name'      => $request->last_name,
//         'email'          => $request->email,
//         'contact_number' => $request->contact,
//         'password'       => Hash::make($request->password),
//         'role'           => 'tenant',
//         'status'         => 'pending',
//         'terms_accepted' => true,
//     ]);

//     // Create tenant application
//     TenantApplication::create([
//         'user_id'               => $user->id,
//         'full_name'             => $request->first_name . ' ' . $request->last_name,
//         'email'                 => $request->email,
//         'contact_number'        => $request->contact,
//         'current_address'       => $request->current_address,
//         'birthdate'             => $request->birthdate,
//         'unit_type'             => $request->unit_type,
//         'unit_id'               => $request->unit_id,
//         'room_no'               => $unit->room_no,
//         'bed_number'            => $bedNumber,
//         'move_in_date'          => $request->move_in_date,
//         'reason'                => $request->reason,
//         'employment_status'     => $request->employment_status,
//         'employer_school'       => $request->employer_school,
//         'source_of_income'      => $request->source_of_income,
//         'emergency_name'        => $request->emergency_name,
//         'emergency_number'      => $request->emergency_number,
//         'emergency_relationship'=> $request->emergency_relationship,
//         'valid_id_path'         => $validIdPath,
//         'id_picture_path'       => $idPicturePath,
//     ]);

//     return redirect()->route('login')
//                     ->with('success', 'Tenant registration and application submitted successfully! Awaiting approval.');
// }

public function register(Request $request)
{
    $user = Auth::user();

    // ✅ CONDITIONAL VALIDATION
    $rules = [
        // Tenant application
        'current_address' => 'required|string|max:500',
        'birthdate'       => 'required|date|before:today',
        'unit_type'       => 'required|string|max:100',
        'unit_id'         => 'required|exists:units,id',
        'bed_number'      => 'nullable|integer|min:1|required_if:unit_type,Bed-Spacer',
        'move_in_date'    => 'required|date|after_or_equal:today',
        'reason'          => 'required|string|max:1000',
        'employment_status'=> 'required|string|in:Employed,Unemployed,Student',
        'employer_school' => 'required|string|max:255',
        'source_of_income'=> 'required|string|max:255',
        'source_of_income'=> 'required|string|max:255',
        'monthly_income'=> 'nullable',
        'emergency_name'  => 'required|string|max:255',
        'emergency_number'=> 'required|digits_between:10,15',
        'emergency_relationship'=> 'required|string|max:100',
        'valid_id'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'id_picture'      => 'required|image|max:5120',
    ];

    // ✅ ONLY REQUIRE THESE IF USER IS NOT LOGGED IN
    if (!$user) {
        $rules = array_merge($rules, [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'contact'    => 'required|digits_between:10,15',
            'password'   => [
                'required','string','min:8','confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
        ]);
    }

    $request->validate($rules);

    // ✅ FETCH UNIT & BED LOGIC (UNCHANGED)
    $unit = Unit::with(['leases' => fn($query) => $query->whereIn('lea_status', ['active', 'pending'])])
        ->find($request->unit_id);

    if (!$unit) {
        return back()->withInput()->with('error', 'Selected unit could not be found.');
    }

    $bedNumber = null;

    if ($unit->type === 'Bed-Spacer') {
        $bedNumber = (int) $request->bed_number;

        if ($bedNumber < 1 || $bedNumber > $unit->capacity) {
            return back()->withInput()->with('error', 'Invalid bed number.');
        }

        $takenBeds = $this->getTakenBeds($unit);

        if (count($takenBeds) >= $unit->capacity) {
            return back()->withInput()->with('error', 'This bed-spacer unit is full.');
        }

        if (in_array($bedNumber, $takenBeds, true)) {
            return back()->withInput()->with('error', 'Selected bed is no longer available.');
        }
    } elseif ($unit->status === 'occupied') {
        return back()->withInput()->with('error', 'The selected room is already occupied.');
    }

    // ✅ FILE UPLOADS
    $validIdPath = $request->file('valid_id')->store('tenant_ids', 'public');
    $idPicturePath = $request->file('id_picture')->store('tenant_pictures', 'public');

    // ✅ CREATE USER ONLY IF NOT LOGGED IN
    if (!$user) {
        $user = User::create([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'email'          => $request->email,
            'contact_number' => $request->contact,
            'password'       => Hash::make($request->password),
            'role'           => 'tenant',
            'status'         => 'pending',
            'terms_accepted' => true,
        ]);
    }

    // ✅ ALWAYS USE LOGGED-IN USER DATA FOR TENANT APPLICATION
    TenantApplication::create([
        'user_id'               => $user->id,
        'full_name'             => $user->first_name . ' ' . $user->last_name,
        'email'                 => $user->email,
        'contact_number'        => $user->contact_number,
        'current_address'       => $request->current_address,
        'birthdate'             => $request->birthdate,
        'unit_type'             => $request->unit_type,
        'unit_id'               => $request->unit_id,
        'room_no'               => $unit->room_no,
        'bed_number'            => $bedNumber,
        'move_in_date'          => $request->move_in_date,
        'reason'                => $request->reason,
        'employment_status'     => $request->employment_status,
        'employer_school'       => $request->employer_school,
        'source_of_income'      => $request->source_of_income,
        'monthly_income'        => $request->monthly_income ?? null,
        'emergency_name'        => $request->emergency_name,
        'emergency_number'      => $request->emergency_number,
        'emergency_relationship'=> $request->emergency_relationship,
        'valid_id_path'         => $validIdPath,
        'id_picture_path'       => $idPicturePath,
    ]);

    // ✅ CONDITIONAL REDIRECT
if (Auth::user()) {
    return redirect()->route('tenant.home')
        ->with('success', 'Tenant application submitted successfully! Awaiting approval.');
}

return redirect()->route('login')
    ->with('success', 'Tenant registration and application submitted successfully! Awaiting approval.');
}



protected function getTakenBeds(Unit $unit): array
{
    $leaseBeds = $unit->leases()
        ->whereIn('lea_status', ['active', 'pending'])
        ->pluck('bed_number')
        ->filter();

    $pendingApplicationBeds = TenantApplication::where('unit_id', $unit->id)
        ->whereNotNull('bed_number')
        ->whereHas('user', function ($query) {
            $query->whereIn('status', ['pending', 'approved']);
        })
        ->pluck('bed_number')
        ->filter();

    return $leaseBeds->merge($pendingApplicationBeds)
        ->filter()
        ->unique()
        ->map(fn ($bed) => (int) $bed)
        ->values()
        ->all();
}

public function checkEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $exists = User::where('email', $request->email)->exists();

    return response()->json([
        'available' => !$exists,
        'message' => $exists ? 'This email is already registered. Please use a different email or login to your existing account.' : 'Email is available'
    ]);
}

}
