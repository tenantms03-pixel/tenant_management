<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Notification;
use Illuminate\Http\Request;

class TenantLeaseController extends Controller
{
    public function index()
    {
        $leases = Lease::where('user_id', auth()->id())->with('unit')->get();
        $availableUnits = Unit::where('status', 'vacant')->get();

        return view('tenant.leasemanagement', compact('leases', 'availableUnits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'start_date' => 'required|date',
        ]);

        $unit = Unit::find($request->unit_id);

        // Create a new lease for this tenant
        Lease::create([
            'user_id' => auth()->id(),
            'unit_id' => $unit->id,
            'lea_start_date' => $request->start_date,
            'lea_end_date' => now()->addYear(),
            'lea_status' => 'pending',
        ]);

        return redirect()->route('tenant.leases')
            ->with('success', 'Your lease application has been submitted!');
    }

    public function requestMoveOut($id)
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

        // Set move out requested flag
        $lease->move_out_requested = true;
        $lease->save();

        // Create notification for manager (if there's a manager user)
        $managers = \App\Models\User::where('role', 'manager')->get();
        foreach ($managers as $manager) {
            Notification::create([
                'user_id' => $manager->id,
                'title' => 'Move Out Request',
                'message' => auth()->user()->name . ' has requested to move out from Room ' . ($lease->room_no ?? 'N/A') . '.',
            ]);
        }

        return redirect()->route('tenant.leases')
            ->with('success', 'Your move out request has been submitted. The manager will review it shortly.');
    }

}