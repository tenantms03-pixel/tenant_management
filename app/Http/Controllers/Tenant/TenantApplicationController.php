<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TenantApplication;
use Illuminate\Support\Facades\Auth;

class TenantApplicationController extends Controller
{

    public function submit(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'contact_number' => 'required|string|max:20',
            'current_address' => 'required|string|max:255',
            'birthdate' => 'required|date',
            'unit_type' => 'required|string',
            'move_in_date' => 'required|date',
            'reason' => 'required|string',
            'employment_status' => 'required|string',
            'employer_school' => 'required|string|max:255',
            'emergency_name' => 'required|string|max:255',
            'emergency_number' => 'required|string|max:20',
            'emergency_relationship' => 'required|string|max:50',
            'valid_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'id_picture' => 'nullable|image',
        ]);

        $user = Auth::user();

        // Upload files if provided
        $validIdPath = $request->hasFile('valid_id') ? $request->file('valid_id')->store('tenant_ids', 'public') : null;
        $idPicturePath = $request->hasFile('id_picture') ? $request->file('id_picture')->store('tenant_ids', 'public') : null;

        TenantApplication::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'current_address' => $request->current_address,
            'birthdate' => $request->birthdate,
            'unit_type' => $request->unit_type,
            'move_in_date' => $request->move_in_date,
            'reason' => $request->reason,
            'employment_status' => $request->employment_status,
            'employer_school' => $request->employer_school,
            'emergency_name' => $request->emergency_name,
            'emergency_number' => $request->emergency_number,
            'emergency_relationship' => $request->emergency_relationship,
            'valid_id_path' => $validIdPath,
            'id_picture_path' => $idPicturePath,
            'is_complete' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant application submitted successfully!',
        ]);
    }

}
