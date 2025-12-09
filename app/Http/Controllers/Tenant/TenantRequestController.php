<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceRequest; // 👈 make sure this matches your model name
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;

class TenantRequestController extends Controller
{
    public function index()
    {
        $requests = MaintenanceRequest::where('tenant_id', Auth::id())
                        ->with('lease.unit')
                        ->orderBy('created_at', 'desc')
                        ->get();

        $activeLeases = Auth::user()->leases()
            ->whereIn('lea_status', ['active', 'pending'])
            ->with('unit')
            ->get();

        return view('tenant.request', compact('requests', 'activeLeases'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'lease_id'      => 'required|exists:leases,id',
            'description'   => 'required|string',
            'urgency'       => 'required|string',
            'supposed_date' => 'required|date',
            'issue_image'   => 'required|image|mimes:jpg,jpeg,png',
        ]);

        $lease = Auth::user()->leases()
            ->where('id', $request->lease_id)
            ->whereIn('lea_status', ['active', 'pending', 'Active', 'Pending'])
            ->with('unit')
            ->first();

        if (!$lease) {
            return redirect()->back()->with('error', 'Selected room could not be found or is not active.');
        }

        // ✅ Store the image (same pattern as your working example)
        $issueImagePath = null;
        if ($request->hasFile('issue_image')) {
            $issueImagePath = $request->file('issue_image')->store('issues', 'public');
        }

        // ✅ Save to database
        \App\Models\MaintenanceRequest::create([
            'tenant_id'     => Auth::id(),
            'lease_id'      => $lease->id,
            'unit_id'      =>  $lease->unit_id,
            'unit_type'     => $lease->unit->type ?? 'Unit',
            'room_no'       => $lease->room_no,
            'description'   => $request->description,
            'urgency'       => $request->urgency,
            'supposed_date' => $request->supposed_date,
            'status'        => 'Pending',
            'issue_image'   => $issueImagePath,
        ]);

        return redirect()->route('tenant.requests')->with('success', 'Request submitted successfully!');
    }

    public function cancel($id)
    {
        $request = \App\Models\MaintenanceRequest::where('tenant_id', Auth::id())
                        ->where('id', $id)
                        ->firstOrFail();

        if (in_array($request->status, ['Pending', 'In Progress'])) {
            $request->update(['status' => 'Cancelled']);
            return redirect()->back()->with('success', 'Request cancelled successfully.');
        }

        return redirect()->back()->with('error', 'Unable to cancel this request.');
    }




    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'description' => 'required|string',
    //         'urgency' => 'required|string',
    //         'supposed_date' => 'required|date',
    //         'unit_type' => 'required|string',
    //         'room_no' => 'nullable|string', // optional now
    //         'issue_image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // ✅ validation for image
    //     ]);

    //     $pathToIssue = $request->hasFile('issue_image')
    //     ? $request->file('issue_image')->store('issues', 'public')
    //     : null;

    //     \App\Models\MaintenanceRequest::create([
    //         'tenant_id' => Auth::id(),
    //         'unit_type' => $request->unit_type,
    //         'room_no' => $request->room_no,
    //         'description' => $request->description,
    //         'urgency' => $request->urgency,
    //         'supposed_date' => $request->supposed_date,
    //         'status' => 'Pending',
    //         'issue_image'   => $pathToIssue, // ✅ save path
    //     ]);

    //     return redirect()->route('tenant.requests')->with('success', 'Request submitted successfully!');
    // }

}
