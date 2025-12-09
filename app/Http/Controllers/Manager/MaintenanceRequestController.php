<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use App\Models\Unit;

class MaintenanceRequestController extends Controller
{

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        $maintenanceRequest->status = $request->status;
        $maintenanceRequest->save();

        $unit = $maintenanceRequest->unit;

        $hasActiveLease = Lease::where('id', $maintenanceRequest->lease_id)
                                ->where('unit_id', $maintenanceRequest->unit_id)
                                ->whereIn('lea_status', ['active', 'Active'])
                                ->exists();

        if($request->status === 'In Progress'){
            $unit->update(['status' => 'maintenance']);
        } elseif($request->status === 'Completed') {
            if($hasActiveLease){
                $unit->update(['status' => 'occupied']);
            } else {
                $unit->update(['status' => 'pending']);
            }
        }

        return back()->with('success', 'Maintenance request status updated successfully.');
    }


    public function show($id)
    {
        $request = MaintenanceRequest::with('tenant')->findOrFail($id);

        return view('manager.requests.show', compact('request'));
    }
}
