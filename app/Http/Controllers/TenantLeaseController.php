<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Notification;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantLeaseController extends Controller
{
    public function index()
    {
        $leases = Lease::where('user_id', auth()->id())->with('unit')->get();
        $availableUnits = Unit::with(['leases' => fn($query) => $query->where('lea_status', 'active')])
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

        // Add taken_beds attribute to each unit
        $availableUnits->each(function ($unit) use ($pendingBedReservations) {
            // Only include active leases, exclude pending
            $leaseBeds = $unit->leases->pluck('bed_number')->filter();
            $pendingBeds = $pendingBedReservations->get($unit->id, collect());
            $unit->setAttribute(
                'taken_beds',
                $leaseBeds->merge($pendingBeds)->filter()->unique()->values()
            );
        });

        return view('tenant.leasemanagement', compact('leases', 'availableUnits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit_id'       => 'required|exists:units,id',
            'start_date'    => 'required|date',
            'bed_number'    => 'nullable|integer|min:1|required_if:unit_type,Bed-Spacer',
        ]);

        DB::transaction(function () use ($request) {

            $unit = Unit::lockForUpdate()->findOrFail($request->unit_id);

            // ✅ Create lease
            Lease::create([
                'user_id'       => auth()->id(),
                'unit_id'       => $unit->id,
                'lea_start_date'=> $request->start_date,
                'lea_end_date'  => now()->addYear(),
                'lea_status'    => 'pending',
                'bed_number'    => $request->bed_number ?? null,
            ]);

        });

        return redirect()->route('tenant.leases')
            ->with('success', 'Your lease application has been submitted!');
    }

    public function requestMoveOut(Request $request, $id)
    {
        $lease = Lease::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if lease is active or pending
        if (!in_array($lease->lea_status, ['active', 'pending'])) {
            return redirect()->route('tenant.leases')
                ->with('error', 'You can only request to move out from active or pending leases.');
        }

        // Check if move out already requested
        if ($lease->move_out_requested) {
            return redirect()->route('tenant.leases')
                ->with('info', 'Move out request has already been submitted for this lease.');
        }

        // Validate reason input
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Set move out requested flag and save reason
        $lease->move_out_requested = true;
        $lease->move_out_reason = $request->reason; // Make sure your Lease table has a move_out_reason column
        $lease->save();

        // Create notification for manager(s)
        $managers = \App\Models\User::where('role', 'manager')->get();
        foreach ($managers as $manager) {
            Notification::create([
                'user_id' => $manager->id,
                'title' => 'Move Out Request',
                'message' => auth()->user()->name . ' has requested to move out from Room ' . ($lease->room_no ?? 'N/A') . '. Reason: ' . $request->reason,
            ]);
        }

        return redirect()->route('tenant.leases')
            ->with('success', 'Your move out request has been submitted. The manager will review it shortly.');
    }


}