<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\User;
use App\Models\UtilityBillingProof;
use Illuminate\Http\Request;

class UtilityController extends Controller
{
    // Display list of tenants with utility balances
    public function index()
    {
        // Fetch all approved tenants with their active/pending leases
        $tenants = User::with(['leases' => function($query) {
                $query->whereIn('lea_status', ['active', 'pending', 'Active', 'Pending'])
                      ->with('unit');
            }])
            ->where('role', 'tenant')
            ->where('status', 'approved')
            ->get();

        // Build tenant groups with their leases
        $tenantGroups = $tenants
            ->filter(function($tenant) {
                // Only include tenants who have at least one active/pending lease
                return $tenant->leases->whereIn('lea_status', ['active', 'pending', 'Active', 'Pending'])->count() > 0;
            })
            ->map(function ($tenant) {
                // Get only active/pending leases
                $activeLeases = $tenant->leases->filter(function($lease) {
                    return in_array($lease->lea_status, ['active', 'pending', 'Active', 'Pending']);
                });
                
                if ($activeLeases->isEmpty()) {
                    return null;
                }
                
                $roomEntries = $activeLeases->map(function ($lease) {
                    $roomLabel = $lease->room_no ?? 'N/A';
                    if ($lease->unit) {
                        $roomLabel .= ' - ' . $lease->unit->type;
                    }
                    if ($lease->bed_number) {
                        $roomLabel .= ' (Bed ' . $lease->bed_number . ')';
                    }
                    
                    // Use lease-specific utility balance, fallback to tenant balance if lease balance is null
                    $utilityBalance = $lease->utility_balance ?? $lease->tenant->utility_balance ?? 0;
                    
                    return (object) [
                        'lease_id' => $lease->id,
                        'room_label' => $roomLabel,
                        'utility_balance' => $utilityBalance, // Each lease/unit has its own balance
                    ];
                });
                
                return (object) [
                    'tenant' => $tenant,
                    'room_entries' => $roomEntries,
                    'all_leases' => $activeLeases, // For dropdown
                ];
            })
            ->filter() // Remove any null entries
            ->sortBy(function($group) {
                return $group->tenant->name ?? '';
            }); // Sort by tenant name

        return view('manager.utilities.index', [
            'tenantGroups' => $tenantGroups,
        ]);
    }

    public function updateUtilityBalance(Request $request, $id)
    {
        try {
            \Log::info('UpdateUtilityBalance called', $request->all());  // ✅ Add logging

            $request->validate([
                'utility_balance' => 'required|numeric|min:0',
                'proof_of_utility_billing' => 'nullable|image|mimes:jpg,jpeg,png',
            ]);

            $lease = Lease::findOrFail($id);
            $user = $lease->tenant;

            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Tenant not found'], 404);
            }

            $pathToProof = $request->hasFile('proof_of_utility_billing')
                ? $request->file('proof_of_utility_billing')->store('utility_proofs', 'public')
                : null;

            // Update the lease-specific utility balance (not the tenant-level balance)
            $lease->utility_balance = $request->utility_balance;
            $lease->save();

            // Always create a utility billing record, even if no proof is uploaded
            $billingData = [
                'tenant_id' => $user->id,
                'lease_id' => $lease->id,
                'billing_month' => $request->input('billing_month') ?? now()->format('F Y'),
                'amount' => $request->utility_balance,
                'notes' => $request->input('proof_notes'),
            ];

            if ($pathToProof) {
                $user->proof_of_utility_billing = $pathToProof;
                $user->save();
                $billingData['file_path'] = $pathToProof;
            } else {
                // If no proof, set file_path to null
                $billingData['file_path'] = null;
            }

            UtilityBillingProof::create($billingData);

            return response()->json([
                'success' => true,
                'new_balance' => $lease->utility_balance,
                'message' => 'Utility balance updated successfully!' . ($pathToProof ? ' Proof uploaded.' : '')
            ]);
        } catch (\Exception $e) {
            \Log::error('UpdateUtilityBalance error: ' . $e->getMessage());  // ✅ Enhanced logging
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
