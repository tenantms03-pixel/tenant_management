<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;

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

        return back()->with('success', 'Maintenance request status updated successfully.');
    }

    public function show($id)
    {
        $request = MaintenanceRequest::with('tenant')->findOrFail($id);

        return view('manager.requests.show', compact('request'));
    }
}
