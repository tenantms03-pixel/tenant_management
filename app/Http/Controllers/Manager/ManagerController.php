<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\TenantApprovedMail;
use App\Mail\TenantPaymentReminderMail;
use App\Mail\TenantRejectedMail;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ManagerController extends Controller
{
    protected SmsService $smsService;

    public function __construct()
    {
        $this->smsService = new SmsService();
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
     * Show all tenants grouped by status
     */
    // public function tenants()
    // {
    //     $pendingTenants = User::where('role', 'tenant')
    //         ->where('status', 'pending')
    //         ->get();

    //     $approvedTenantList = User::where('role', 'tenant')
    //         ->where('status', 'approved')
    //         ->get();

    //     $rejectedTenantList = User::where('role', 'tenant')
    //         ->where('status', operator: 'rejected')
    //         ->get();

    //     return view('manager.tenants', compact(
    //         'pendingTenants',
    //         'approvedTenantList',
    //         'rejectedTenantList'
    //     ));
    // }

   public function notifyTenant($id)
    {
        $tenant = User::findOrFail($id);

        if ($tenant->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved tenants can be notified.');
        }

        try {
            Mail::to($tenant->email)->send(new TenantPaymentReminderMail($tenant));
        } catch (\Exception $e) {
            Log::error("❌ Failed to send payment reminder email to {$tenant->email}: {$e->getMessage()}");
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Payment reminder sent to ' . $tenant->name);
    }


    public function exportTenantsPdf(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = User::with(['tenantApplication', 'leases'])
            ->where('role', 'tenant');

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $tenants = $query->get();

        $pdf = Pdf::loadView('reports.pdf.tenants-filtered', [
            'tenants' => $tenants,
            'filter' => $filter,
            'generatedAt' => now()->format('Y-m-d H:i:s')
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("tenant-report-{$filter}.pdf");
    }

    public function tenants(Request $request)
    {
        // Capture filter and search (default: all for filter, empty for search)
        $filter = $request->query('filter', 'all');
        $search = $request->query('search', '');

        // Base query for tenants - load leases with utility_balance
        // Load ALL leases regardless of status (including terminated, rejected, etc.)
        // Also load tenantApplication with unit relationship to show application info when no leases exist
        $query = User::with([
                'tenantApplication.unit',
                'leases' => function($q) {
                    $q->with('unit'); // Remove status filter to show all leases
                },
                'utilityProofs'
            ])
            ->where('role', 'tenant');

        // Apply status filter if specified
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        // ✅ Apply search filter (by full_name in tenant_applications)
        if (!empty($search)) {
            $query->whereHas('tenantApplication', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        // Group by status for easy section display (these can stay as-is, but we'll use $filteredTenants for display)
        $pendingTenants = User::where('role', 'tenant')->where('status', 'pending')->get();
        $approvedTenantList = User::where('role', 'tenant')->where('status', 'approved')->get();
        $rejectedTenantList = User::where('role', 'tenant')->where('status', 'rejected')->get();

        // Filtered results for card display (now includes search)
        // $filteredTenants = $query->orderByRaw("CONCAT(first_name, ' ', last_name) ASC")->get();
        $filteredTenants = $query->orderBy("created_at", 'desc')->get();

        return view('manager.tenants', compact(
            'pendingTenants',
            'approvedTenantList',
            'rejectedTenantList',
            'filteredTenants',
            'filter',
            'search'  // Pass search to the view for form pre-fill
        ));
    }
    // public function tenants(Request $request)
    // {
    //     // Capture filter (default: all)
    //     $filter = $request->query('filter', 'all');

    //     // Base query for tenants
    //     $query = User::where('role', 'tenant');

    //     // Apply filter if specified
    //     if ($filter !== 'all') {
    //         $query->where('status', $filter);
    //     }

    //     // Group by status for easy section display
    //     $pendingTenants = User::where('role', 'tenant')->where('status', 'pending')->get();
    //     $approvedTenantList = User::where('role', 'tenant')->where('status', 'approved')->get();
    //     $rejectedTenantList = User::where('role', 'tenant')->where('status', 'rejected')->get();

    //     // Filtered results for card display
    //     $filteredTenants = $query->get();

    //     return view('manager.tenants', compact(
    //         'pendingTenants',
    //         'approvedTenantList',
    //         'rejectedTenantList',
    //         'filteredTenants',
    //         'filter'
    //     ));
    // }

    // public function filterTenants(Request $request)
    // {
    //     $filter = $request->input('filter', 'all');
    //     $query = User::where('role', 'tenant');

    //     if ($filter !== 'all') {
    //         $query->where('status', $filter);
    //     }

    //     $tenants = $query->get();

    //     return response()->json([
    //         'html' => view('partials.tenants_table', compact('tenants'))->render()
    //     ]);
    // }


    /**
     * Approve a tenant application
     */
    // public function approve($id)
    // {
    //     $user = User::find($id);

    //     if (!$user || $user->role !== 'tenant') {
    //         return redirect()->back()->with('error', 'Invalid tenant.');
    //     }

    //     $user->status = 'approved';
    //     $user->rejection_reason = null; // clear old reason if any
    //     $user->save();

    //     return redirect()->back()->with('success', 'Tenant approved successfully.');
    // }

    // public function approve($id)
    // {
    //     $user = User::find($id);

    //     if (!$user || $user->role !== 'tenant') {
    //         return redirect()->back()->with('error', 'Invalid tenant.');
    //     }

    //     $tenantApp = $user->tenantApplication;

    //     if (!$tenantApp) {
    //         return redirect()->back()->with('error', 'Tenant application not found.');
    //     }

    //     // Approve tenant
    //     $user->status = 'approved';
    //     $user->rejection_reason = null;
    //     $user->save();

    //     // Lease period
    //     $startDate = Carbon::today();
    //     $endDate   = $startDate->copy()->addYear();

    //     // Assign unit
    //     $unit = Unit::find($tenantApp->unit_id);

    //     if (!$unit) {
    //         return redirect()->back()->with('error', 'Selected unit not found.');
    //     }

    //     // Mark unit as occupied
    //     $unit->status = 'occupied';
    //     $unit->save();

    //     // Create Lease
    //     $lease = Lease::create([
    //         'user_id'        => $user->id,
    //         'unit_id'        => $unit->id,
    //         'lea_start_date' => $startDate,
    //         'lea_end_date'   => $endDate,
    //         'lea_status'     => 'active',
    //         'room_no'        => $unit->room_no, // fetch from unit
    //     ]);

    //     // Determine unit type and rent
    //     $unitType = $tenantApp->unit_type ?? 'Studio';
    //     $monthlyRent = 0;
    //     $depositAmount = 0;
    //     $monthlyUtilities = 0; // fixed utility fee, adjust if needed

    //     switch ($unitType) {
    //         case 'Studio':
    //             $monthlyRent = 7500;
    //             break;
    //         case 'One Bedroom':
    //             $monthlyRent = 10000;
    //             break;
    //         case 'Two Bedroom':
    //             $monthlyRent = 12000;
    //             break;
    //         default:
    //             $monthlyRent = 7500;
    //     }

    //     // Deposit = 1 month rent + 1 month advance rent
    //     $depositAmount = $monthlyRent * 2;

    //     // Update tenant financial info
    //     $user->rent_amount     = $monthlyRent;
    //     $user->utility_amount  = $monthlyUtilities;
    //     $user->deposit_amount  = $depositAmount;
    //     $user->rent_balance    = $depositAmount; // total initial amount due
    //     $user->utility_balance = $monthlyUtilities; // utilities due
    //     $user->save();

    //     return redirect()->back()->with('success', 'Tenant approved successfully. Lease and financial info initialized, and unit marked as occupied.');
    // }

    // public function approve($id)
    // {
    //     $user = User::find($id);

    //     if (!$user || $user->role !== 'tenant') {
    //         return redirect()->back()->with('error', 'Invalid tenant.');
    //     }

    //     $tenantApp = $user->tenantApplication;

    //     if (!$tenantApp) {
    //         return redirect()->back()->with('error', 'Tenant application not found.');
    //     }

    //     // Approve tenant
    //     $user->status = 'approved';
    //     $user->rejection_reason = null;
    //     $user->save();

    //     // Lease period
    //     $startDate = Carbon::today();
    //     $endDate   = $startDate->copy()->addYear();

    //     // Lease duration text
    //     $leaseTermText = sprintf(
    //         '1 Year Lease (%s – %s)',
    //         $startDate->format('M d, Y'),
    //         $endDate->format('M d, Y')
    //     );

    //     // Assign unit
    //     $unit = Unit::find($tenantApp->unit_id);

    //     if (!$unit) {
    //         return redirect()->back()->with('error', 'Selected unit not found.');
    //     }

    //     // Mark unit as occupied
    //     $unit->status = 'occupied';
    //     $unit->save();

    //     // Create Lease
    //     $lease = Lease::create([
    //         'user_id'        => $user->id,
    //         'unit_id'        => $unit->id,
    //         'lea_start_date' => $startDate,
    //         'lea_end_date'   => $endDate,
    //         'lea_status'     => 'active',
    //         'room_no'        => $unit->room_no,
    //         'lea_terms'      => $leaseTermText, // ✅ Added lease term text
    //     ]);

    //     // Determine unit type and rent
    //     $unitType = $tenantApp->unit_type ?? 'Studio';
    //     $monthlyRent = 0;
    //     $depositAmount = 0;
    //     $monthlyUtilities = 0;

    //     switch ($unitType) {
    //         case 'Studio':
    //             $monthlyRent = 7500;
    //             break;
    //         case 'One Bedroom':
    //             $monthlyRent = 10000;
    //             break;
    //         case 'Two Bedroom':
    //             $monthlyRent = 12000;
    //             break;
    //         default:
    //             $monthlyRent = 7500;
    //     }

    //     // Deposit = 1 month rent + 1 month advance rent
    //     $depositAmount = $monthlyRent * 2;

    //     // Update tenant financial info
    //     $user->rent_amount     = $monthlyRent;
    //     $user->utility_amount  = $monthlyUtilities;
    //     $user->deposit_amount  = $depositAmount;
    //     $user->rent_balance    = $depositAmount;
    //     $user->utility_balance = $monthlyUtilities;
    //     $user->save();

    //     return redirect()->back()->with('success', 'Tenant approved successfully. Lease and financial info initialized, and unit marked as occupied.');
    // }

    // With e-mail this time T_T
    // public function approve($id)
    // {
    //     $user = User::find($id);

    //     if (!$user || $user->role !== 'tenant') {
    //         return redirect()->back()->with('error', 'Invalid tenant.');
    //     }

    //     $tenantApp = $user->tenantApplication;

    //     if (!$tenantApp) {
    //         return redirect()->back()->with('error', 'Tenant application not found.');
    //     }

    //     // Approve tenant
    //     $user->status = 'approved';
    //     $user->rejection_reason = null;
    //     $user->save();

    //     // Lease period
    //     $startDate = Carbon::today();
    //     $endDate   = $startDate->copy()->addYear();

    //     $leaseTermText = sprintf(
    //         '1 Year Lease (%s – %s)',
    //         $startDate->format('M d, Y'),
    //         $endDate->format('M d, Y')
    //     );

    //     // Assign unit
    //     $unit = Unit::find($tenantApp->unit_id);
    //     if (!$unit) {
    //         return redirect()->back()->with('error', 'Selected unit not found.');
    //     }

    //     $unit->status = 'occupied';
    //     $unit->save();

    //     // Create Lease
    //     $lease = Lease::create([
    //         'user_id'        => $user->id,
    //         'unit_id'        => $unit->id,
    //         'lea_start_date' => $startDate,
    //         'lea_end_date'   => $endDate,
    //         'lea_status'     => 'active',
    //         'room_no'        => $unit->room_no,
    //         'lea_terms'      => $leaseTermText,
    //     ]);

    //     // Determine rent and deposit
    //     $unitType = $tenantApp->unit_type ?? 'Studio';
    //     $monthlyRent = match($unitType) {
    //         'Studio' => 7500,
    //         'One Bedroom' => 10000,
    //         'Two Bedroom' => 12000,
    //         default => 7500,
    //     };
    //     $depositAmount = $monthlyRent * 2;
    //     $monthlyUtilities = 0;

    //     // Update tenant financial info
    //     $user->update([
    //         'rent_amount'     => $monthlyRent,
    //         'utility_amount'  => $monthlyUtilities,
    //         'deposit_amount'  => $depositAmount,
    //         'rent_balance'    => $depositAmount,
    //         'utility_balance' => $monthlyUtilities,
    //     ]);

    //     // ✅ Send Approval Email
    //     try {
    //         Mail::to($user->email)->send(new TenantApprovedMail($user, $lease));
    //         // $this->info("📩 Approval email sent to {$user->email}");
    //     } catch (\Exception $e) {
    //         \Log::error("❌ Failed to send tenant approval email to {$user->email}: {$e->getMessage()}");
    //     }

    //     return redirect()->back()->with('success', 'Tenant approved successfully. Lease and financial info initialized, and unit marked as occupied.');
    // }

    // public function approve($id)
    // {
    //     $user = User::find($id);

    //     if (!$user || $user->role !== 'tenant') {
    //         return redirect()->back()->with('error', 'Invalid tenant.');
    //     }

    //     $tenantApp = $user->tenantApplication;

    //     if (!$tenantApp) {
    //         return redirect()->back()->with('error', 'Tenant application not found.');
    //     }

    //     // Approve tenant
    //     $user->status = 'approved';
    //     $user->rejection_reason = null;
    //     $user->save();

    //     // Lease period
    //     $startDate = Carbon::today();
    //     $endDate   = $startDate->copy()->addYear();

    //     $leaseTermText = sprintf(
    //         '1 Year Lease (%s – %s)',
    //         $startDate->format('M d, Y'),
    //         $endDate->format('M d, Y')
    //     );

    //     // Assign unit
    //     $unit = Unit::find($tenantApp->unit_id);
    //     if (!$unit) {
    //         return redirect()->back()->with('error', 'Selected unit not found.');
    //     }

    //     $unit->status = 'occupied';
    //     $unit->save();

    //     // Create Lease
    //     $lease = Lease::create([
    //         'user_id'        => $user->id,
    //         'unit_id'        => $unit->id,
    //         'lea_start_date' => $startDate,
    //         'lea_end_date'   => $endDate,
    //         'lea_status'     => 'active',
    //         'room_no'        => $unit->room_no,
    //         'lea_terms'      => $leaseTermText,
    //     ]);

    //     // Determine rent and deposit
    //     $unitType = $tenantApp->unit_type ?? 'Studio';
    //     $monthlyRent = match($unitType) {
    //         'Studio' => 7500,
    //         'One Bedroom' => 10000,
    //         'Two Bedroom' => 12000,
    //         default => 7500,
    //     };
    //     $depositAmount = $monthlyRent * 2;
    //     $monthlyUtilities = 0;

    //     // Update tenant financial info
    //     $user->update([
    //         'rent_amount'     => $monthlyRent,
    //         'utility_amount'  => $monthlyUtilities,
    //         'deposit_amount'  => $depositAmount,
    //         'rent_balance'    => $depositAmount,
    //         'utility_balance' => $monthlyUtilities,
    //     ]);

    //     Notification::create([
    //         'user_id' => $user->id,
    //         'title' => 'Application Approved',
    //         'message' => 'Congratulations! Your tenant application has been approved.',
    //     ]);

    //     // ✅ Send Approval Email
    //     try {
    //         Mail::to($user->email)->send(new TenantApprovedMail($user, $lease));
    //     } catch (\Exception $e) {
    //         Log::error("❌ Failed to send tenant approval email to {$user->email}: {$e->getMessage()}");
    //     }

    //     // ✅ Send Approval SMS
    //     $contactNumber = $this->normalizePhoneNumber($tenantApp->contact_number);

    //     if ($contactNumber) {
    //         $approvalMessage = "Hello {$user->name}, your application has been APPROVED! "
    //             . "Your lease for room {$unit->room_no} starts {$startDate->format('M d, Y')}.";
    //         $this->smsService->send($contactNumber, $approvalMessage);
    //     }

    //     return redirect()->back()->with('success', 'Tenant approved successfully. Lease and financial info initialized, and unit marked as occupied.');
    // }

    public function approve($id)
    {
        $user = User::find($id);

        if (!$user || $user->role !== 'tenant') {
            return redirect()->back()->with('error', 'Invalid tenant.');
        }

        $tenantApp = $user->tenantApplication;

        if (!$tenantApp) {
            return redirect()->back()->with('error', 'Tenant application not found.');
        }

        // Get unit early (needed for rejection logic)
        $unit = Unit::find($tenantApp->unit_id);
        if (!$unit) {
            return redirect()->back()->with('error', 'Selected unit not found.');
        }

        // Approve tenant
        $user->status = 'approved';
        $user->rejection_reason = null;
        $user->save();

        // Auto-reject other pending applications for the same unit
        $otherPendingApplications = TenantApplication::where('unit_id', $tenantApp->unit_id)
            ->where('user_id', '!=', $user->id)
            ->whereHas('user', function($query) {
                $query->where('status', 'pending');
            })
            ->with('user')
            ->get();

        foreach ($otherPendingApplications as $otherApp) {
            $otherUser = $otherApp->user;

            // Reject the other tenant
            $otherUser->status = 'rejected';
            $otherUser->rejection_reason = 'Unit is already taken. Another application for this unit has been approved.';
            $otherUser->save();

            // Send rejection SMS
            $contactNumber = $this->normalizePhoneNumber($otherApp->contact_number ?? null);
            if ($contactNumber) {
                $rejectionMessage = "TenantMS\nHello {$otherUser->name}, your application for unit {$unit->room_no} has been REJECTED. "
                    . "The unit is already taken by another approved tenant.";
                $this->smsService->send($contactNumber, $rejectionMessage);
            }

            // Create notification
            Notification::create([
                'user_id' => $otherUser->id,
                'title'   => 'Application Rejected',
                'message' => "Your application for unit {$unit->room_no} has been rejected. The unit is already taken.",
            ]);

            // Send rejection email
            try {
                Mail::to($otherUser->email)->send(new TenantRejectedMail($otherUser));
            } catch (\Exception $e) {
                Log::error("❌ Failed to send tenant rejection email to {$otherUser->email}: {$e->getMessage()}");
            }
        }

        // Lease period
        $startDate = Carbon::today();
        $endDate   = $startDate->copy()->addYear();

        $leaseTermText = sprintf(
            '1 Year Lease (%s – %s)',
            $startDate->format('M d, Y'),
            $endDate->format('M d, Y')
        );

        // Bed-Spacer logic
        if ($unit->type === 'Bed-Spacer') {
            $unit->no_of_occupants = ($unit->no_of_occupants ?? 0) + 1;

            // Only mark as 'occupied' if full
            if ($unit->no_of_occupants >= $unit->capacity) {
                $unit->status = 'occupied';
            }
        } else {
            // Other unit types are fully occupied
            $unit->status = 'occupied';
        }
        $unit->save();

        // Determine rent and deposit
        $unitType = $tenantApp->unit_type ?? 'Studio';
        $monthlyRent = match($unitType) {
            'Studio' => $unit->room_price ?? 0,
            '1-Bedroom' => $unit->room_price ?? 0,
            '2-Bedroom' => $unit->room_price ?? 0,
            'Bed-Spacer' => $unit->room_price ?? 0,
            default => 0,
        };
        $depositAmount = $monthlyRent * 2;
        $monthlyUtilities = 0;

        // Create Lease
        $lease = Lease::create([
            'user_id'        => $user->id,
            'unit_id'        => $unit->id,
            'lea_start_date' => $startDate,
            'lea_end_date'   => $endDate,
            'lea_status'     => 'active',
            'room_no'        => $unit->room_no,
            'lea_terms'      => $leaseTermText,
            'bed_number'     => $tenantApp->bed_number ?? null,
            'deposit_balance' => $depositAmount,
            'rent_balance' => $monthlyRent,
        ]);

        // Update tenant financial info
        $user->update([
            'rent_amount'     => $monthlyRent,
            'utility_amount'  => $monthlyUtilities,
            'deposit_amount'  => $depositAmount,
            'rent_balance'    => $monthlyRent,
            'utility_balance' => $monthlyUtilities,
        ]);

        // Notification
        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Application Approved',
            'message' => 'Congratulations! Your tenant application has been approved.',
        ]);

        // Send Approval Email
        try {
            Mail::to($user->email)->send(new TenantApprovedMail($user, $lease));
        } catch (\Exception $e) {
            Log::error("❌ Failed to send tenant approval email to {$user->email}: {$e->getMessage()}");
        }

        // Send Approval SMS
        $contactNumber = $this->normalizePhoneNumber($tenantApp->contact_number);

        if ($contactNumber) {
            $approvalMessage = "Hello {$user->name}, your application has been APPROVED! "
                . "Your lease for room {$unit->room_no} starts {$startDate->format('M d, Y')}.";
            $this->smsService->send($contactNumber, $approvalMessage);
        }

        return redirect()->back()->with('success', 'Tenant approved successfully. Lease and financial info initialized, and unit occupancy updated.');
    }


    /**
     * Reject a tenant application
     */
    // public function reject(Request $request)
    // {
    //     // Validate incoming request
    //     $request->validate([
    //         'tenant_id' => 'required|exists:users,id',
    //         'rejection_reason' => 'required|string|max:255',
    //     ]);

    //     // Find tenant by ID from hidden field
    //     $tenant = User::find($request->tenant_id);

    //     // Ensure tenant exists and is role=tenant
    //     if (!$tenant || $tenant->role !== 'tenant') {
    //         return redirect()->back()->with('error', 'Invalid tenant.');
    //     }

    //     // Update status and reason
    //     $tenant->status = 'rejected';
    //     $tenant->rejection_reason = $request->rejection_reason;
    //     $tenant->save();

    //     return redirect()->back()->with('warning', 'Tenant rejected successfully.');
    // }

    // With reject this time
    public function reject(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:users,id',
            'rejection_reason' => 'required|string|max:255',
        ]);

        $tenant = User::find($request->tenant_id);

        if (!$tenant || $tenant->role !== 'tenant') {
            return redirect()->back()->with('error', 'Invalid tenant.');
        }

        $tenantApp = $tenant->tenantApplication;

        $tenant->status = 'rejected';
        $tenant->rejection_reason = $request->rejection_reason;
        $tenant->save();

        // ✅ Send Rejection Email
        try {
            Mail::to($tenant->email)->send(new TenantRejectedMail($tenant));
        } catch (\Exception $e) {
            Log::error("❌ Failed to send tenant rejection email to {$tenant->email}: {$e->getMessage()}");
        }

        // ✅ Send Rejection SMS
        $tenantApp = $tenant->tenantApplication;
        $contactNumber = $this->normalizePhoneNumber($tenantApp->contact_number ?? null);

        if ($contactNumber) {
            $rejectionMessage = "TenantMS\n,Hello {$tenant->name}, your application has been REJECTED. "
                . "Reason: {$tenant->rejection_reason}";
            $this->smsService->send($contactNumber, $rejectionMessage);
        }

        return redirect()->back()->with('warning', 'Tenant rejected successfully.');
    }

    // public function viewIds($id)
    // {
    //     $tenant = User::with('tenantApplication')->findOrFail($id);

    //     if (!$tenant->tenantApplication ||
    //         !$tenant->tenantApplication->valid_id_path ||
    //         !$tenant->tenantApplication->id_picture_path) {
    //         abort(404, 'Tenant has no uploaded IDs.');
    //     }

    //     $pdf = Pdf::loadView('reports.pdf.tenant-ids', [
    //         'tenant' => $tenant,
    //         'validId' => $tenant->tenantApplication->valid_id_path,
    //         'idPicture' => $tenant->tenantApplication->id_picture_path,
    //     ])->setPaper('a4', 'portrait');

    //     return $pdf->stream("tenant-ids-{$tenant->id}.pdf");
    // }

    public function viewIds($id)
    {
        $tenant = User::with('tenantApplication.unit')->findOrFail($id);

        if (!$tenant->tenantApplication) {
            abort(404, 'Tenant has no application data.');
        }

        $tenantApp = $tenant->tenantApplication;

        $pdf = Pdf::loadView('reports.pdf.tenant-full-info', [
            'tenant' => $tenant,
            'tenantApp' => $tenantApp,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("tenant-info-{$tenant->id}.pdf");
    }

    public function forceMoveOut(Request $request, Lease $lease)
    {
        $lease->load('unit', 'tenant');

        if (!in_array($lease->lea_status, ['active', 'pending'])) {
            return redirect()->back()->with('info', 'This lease is already inactive.');
        }

        $lease->lea_status = 'terminated';
        $lease->lea_end_date = now();
        $lease->save();

        if ($lease->unit) {
            if ($lease->unit->type === 'Bed-Spacer') {
                $current = max(0, ($lease->unit->no_of_occupants ?? 0) - 1);
                $lease->unit->no_of_occupants = $current;
                if ($current < $lease->unit->capacity) {
                    $lease->unit->status = 'vacant';
                }
            } else {
                $lease->unit->status = 'vacant';
            }
            $lease->unit->save();
        }

        $user_deposit = $lease->unit->room_price * 2;

        $tenant = User::where('id', $lease->user_id)->first();

        $tenant->update([
            'deposit_amount' => max(0, $tenant->deposit_amount - $user_deposit),

            'rent_balance' => max(0, ($tenant->rent_balance ?? 0) - ($lease->unit->room_price ?? 0)),

            'rent_amount' => max(0, ($tenant->rent_amount ?? 0) - ($lease->unit->room_price ?? 0)),
        ]);

        Notification::create([
            'user_id' => $lease->tenant->id,
            'title' => 'Lease Terminated',
            'message' => 'Your lease for room ' . ($lease->room_no ?? 'N/A') . ' has been marked as moved out.',
        ]);

        return redirect()->back()->with('success', 'Tenant marked as moved out successfully.');
    }

    public function rejectMoveOut(Request $request, Lease $lease)
    {
        $lease->load('tenant');

        if (!$lease->move_out_requested) {
            return redirect()->back()->with('info', 'No move out request to reject for this lease.');
        }

        // Optional: you can store rejection reason if you want
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $lease->move_out_requested = false;
        $lease->save();

        // Notify tenant
        $message = 'Your move out request for room ' . ($lease->room_no ?? 'N/A') . ' has been rejected.';
        if ($request->reason) {
            $message .= ' Reason: ' . $request->reason;
        }

        Notification::create([
            'user_id' => $lease->tenant->id,
            'title' => 'Move Out Rejected',
            'message' => $message,
        ]);

        return redirect()->back()->with('info', 'Move out request rejected.');
    }

}