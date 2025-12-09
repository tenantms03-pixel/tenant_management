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
        $tenants = User::with(['leases' => function($query) {
                $query->whereIn('lea_status', ['active', 'Active'])
                    ->with('unit');
            }])
            ->where('role', 'tenant')
            ->where('status', 'approved')
            ->get();

        $tenantGroups = $tenants
            ->filter(function($tenant) {
                return $tenant->leases->whereIn('lea_status', ['active', 'pending', 'Active', 'Pending'])->count() > 0;
            })
            ->map(function ($tenant) {

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

                    // ✅ GET ELECTRICITY TOTAL
                    $electricityTotal = \App\Models\UtilityBillingProof::where('lease_id', $lease->id)
                        ->where('billing_type', 'Electricity')
                        ->sum('amount');

                    // ✅ GET WATER TOTAL
                    $waterTotal = \App\Models\UtilityBillingProof::where('lease_id', $lease->id)
                        ->where('billing_type', 'Water')
                        ->sum('amount');

                    // ✅ GRAND TOTAL (SUM OF ALL BILLING)
                    $totalUtility = \App\Models\UtilityBillingProof::where('lease_id', $lease->id)
                        ->sum('amount');

                     $created_at = \App\Models\UtilityBillingProof::where('lease_id', $lease->id)
                    ->latest()
                    ->value('created_at');

                    return (object) [
                        'lease_id'            => $lease->id,
                        'room_label'          => $roomLabel,

                        // ✅ INDIVIDUAL TOTALS
                        'electricity_amount' => $electricityTotal,
                        'water_amount'       => $waterTotal,

                        // ✅ COMBINED TOTAL
                        'utility_balance'    => $totalUtility,
                        'created_at' => $created_at,
                    ];
                });

                return (object) [
                    'tenant'       => $tenant,
                    'room_entries' => $roomEntries,
                    'all_leases'   => $activeLeases,
                ];
            })
            ->filter()
            ->sortBy(function($group) {
                return $group->tenant->name ?? '';
            });

        return view('manager.utilities.index', [
            'tenantGroups' => $tenantGroups,
        ]);
    }


    public function updateUtilityBalance(Request $request, $id)
    {

         validator($request->all(), [
            'utility_balance' => 'required|numeric|min:0',
            'proof_of_utility_billing' => 'required|image|mimes:jpg,jpeg,png',
            'bill_type' => 'nullable',
        ])->validate();

        try {
            \Log::info('UpdateUtilityBalance called', $request->all());  // ✅ Add logging

            $lease = Lease::findOrFail($id);
            $user = $lease->tenant;

            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Tenant not found'], 404);
            }

            $pathToProof = $request->hasFile('proof_of_utility_billing')
                ? $request->file('proof_of_utility_billing')->store('utility_proofs', 'public')
                : null;

            // Update the lease-specific utility balance (not the tenant-level balance)
            $lease->utility_balance = $lease->utility_balance + $request->utility_balance;
            $lease->save();

            // Always create a utility billing record, even if no proof is uploaded
            $billingData = [
                'tenant_id' => $user->id,
                'lease_id' => $lease->id,
                'billing_month' => $request->input('billing_month') ?? now()->format('F Y'),
                'billing_type' => $request->bill_type ?? null,
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
