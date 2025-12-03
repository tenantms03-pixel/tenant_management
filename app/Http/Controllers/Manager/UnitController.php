<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\TenantApprovedMail;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\Unit;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UnitController extends Controller
{
    protected SmsService $smsService;
    /**
     * Show all units with add form.
     */
    // public function index()
    // {
    //     $units = Unit::all();
    //     return view('manager.unitsetup', compact('units'));
    // }

    public function index()
    {
        $units = Unit::all();
        $pendingLeases = Lease::with('tenant', 'unit')
                            ->where('lea_status', 'pending')
                            ->get();

        return view('manager.unitsetup', compact('units', 'pendingLeases'));
    }


    protected function normalizePhoneNumber(?string $number): ?string
    {
        if (!$number) return null;

        $number = preg_replace('/\D/', '', $number); // remove non-numeric characters

        if (str_starts_with($number, '0')) {
            return '+63' . substr($number, 1);
        } elseif (str_starts_with($number, '63')) {
            return '+' . $number;
        } elseif (str_starts_with($number, '+63')) {
            return $number;
        }

        // fallback: assume it's a 10-digit local number
        return '+63' . $number;
    }

    /**
     * Store a new unit.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'room_no' => 'required|string|max:50|unique:units,room_no',
            'room_price' => 'required|numeric|min:0',
            'status' => 'required|in:vacant,occupied',
            'capacity' => 'required|integer|min:1',
        ]);

        Unit::create([
            'type' => $request->type,
            'room_no' => $request->room_no,
            'room_price' => $request->room_price,
            'status' => $request->status,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('manager.units.index')->with('success', 'Unit added successfully.');
    }

    /**
     * Edit a unit.
     */
    public function edit($id)
    {
        return response()->json(Unit::findOrFail($id));
    }


    /**
     * Update a unit.
     */
    /**
 * Update a unit.
 */
    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'room_no' => 'required|string|max:50|unique:units,room_no,' . $unit->id,
            'room_price' => 'required|numeric|min:0',
            'status' => 'required|in:vacant,occupied',
            'capacity' => 'required|integer|min:1',
        ]);

        $unit->update($request->only(['type', 'room_no', 'room_price', 'status', 'capacity']));

        return redirect()->route('manager.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Delete a unit.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()->route('manager.units.index')
            ->with('success', 'Unit deleted successfully.');
    }

public function approveAdditionalUnit($userId, $unitId, $leaseId)
{
    $user = User::find($userId);
    if (!$user || $user->role !== 'tenant') {
        return redirect()->back()->with('error', 'Invalid tenant.');
    }

    $tenantApp = $user->tenantApplication;
    if (!$tenantApp) {
        return redirect()->back()->with('error', 'Tenant application not found.');
    }

    $unit = Unit::find($unitId);
    if (!$unit) {
        return redirect()->back()->with('error', 'Selected unit not found.');
    }

    // Find the specific lease to approve
    $leaseToApprove = Lease::find($leaseId);
    if (!$leaseToApprove || $leaseToApprove->user_id != $userId || $leaseToApprove->unit_id != $unitId) {
        return redirect()->back()->with('error', 'Lease not found or invalid.');
    }

    // Approve tenant if not already approved
    if ($user->status !== 'approved') {
        $user->status = 'approved';
        $user->rejection_reason = null;
    }

    // Lease dates
    $startDate = Carbon::today();
    $endDate   = $startDate->copy()->addYear();
    $leaseTermText = sprintf(
        '1 Year Lease (%s – %s)',
        $startDate->format('M d, Y'),
        $endDate->format('M d, Y')
    );

    // Assign unit occupancy
    if ($unit->type === 'Bed-Spacer') {
        $unit->no_of_occupants = ($unit->no_of_occupants ?? 0) + 1;
        if ($unit->no_of_occupants >= $unit->capacity) {
            $unit->status = 'occupied';
        }
    } else {
        $unit->status = 'occupied';
    }
    $unit->save();

    // Calculate financials for the new unit
    $monthlyRent = $unit->room_price ?? 0;
    $depositAmount = $monthlyRent * 2;
    $monthlyUtilities = 0;

    // Accumulate financials
    $user->deposit_amount  = ($user->deposit_amount ?? 0) + $depositAmount;
    $user->rent_balance    = ($user->rent_balance ?? 0) + $monthlyRent;  // **add rent only once**
    $user->utility_balance = ($user->utility_balance ?? 0) + $monthlyUtilities;
    $user->rent_amount     = ($user->rent_amount ?? 0) + $monthlyRent;   // **total rent across units**
    $user->utility_amount  = ($user->utility_amount ?? 0) + $monthlyUtilities;
    $user->save();

    // Approve the specific lease
    $leaseToApprove->update([
        'lea_start_date' => $startDate,
        'lea_end_date'   => $endDate,
        'lea_status'     => 'active',
        'room_no'        => $unit->room_no,
        'lea_terms'      => $leaseTermText,
        'bed_number'     => $tenantApp->bed_number ?? null,
    ]);

    // Reject/terminate all other pending leases for the same unit
    Lease::where('unit_id', $unitId)
         ->where('id', '!=', $leaseId)
         ->where('lea_status', 'pending')
         ->update(['lea_status' => 'rejected']);

    return redirect()->back()->with('success', 'Tenant approved successfully, and financials updated cumulatively.');
}

public function rejectAdditionalUnit($userId, $unitId, $leaseId)
{
    $user = User::find($userId);
    if (!$user || $user->role !== 'tenant') {
        return redirect()->back()->with('error', 'Invalid tenant.');
    }

    $unit = Unit::find($unitId);
    if (!$unit) {
        return redirect()->back()->with('error', 'Selected unit not found.');
    }

    // Find the specific lease to reject
    $lease = Lease::find($leaseId);
    if (!$lease || $lease->user_id != $userId || $lease->unit_id != $unitId) {
        return redirect()->back()->with('error', 'Lease not found or invalid.');
    }

    // Update lease status to rejected
    $lease->update([
        'lea_status' => 'rejected'
    ]);

    // Set unit status to vacant
    $unit->status = 'vacant';
    $unit->save();

    // Create notification for the tenant
    Notification::create([
        'user_id' => $user->id,
        'title'   => 'Unit Application Rejected',
        'message' => "Your application for room {$unit->room_no} has been rejected."
    ]);

    return redirect()->back()->with('success', 'Unit application rejected successfully.');
}



}
