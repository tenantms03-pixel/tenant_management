<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;


class PaymentController extends Controller
{
    /**
     * Display tenant payments dashboard
     */
    public function index()
    {
        $tenant = Auth::user();
        // Load leases with utility_balance for the accessor to work
        $tenant->load(['leases' => function($q) {
            $q->whereIn('lea_status', ['active', 'pending', 'Active', 'Pending']);
        }]);

        $lease = $tenant->leases()->latest()->first();  // Get the latest lease

        $payments = $tenant->payments()
            ->with('lease.unit')
            ->orderBy('created_at', 'desc')
            ->get();

        $leasesForSelection = $tenant->leases()
            ->whereIn('lea_status', ['active', 'pending'])
            ->with('unit')
            ->get();

        // Show Deposit option only if deposit is not fully paid
        $depositExists = ($tenant->deposit_amount ?? 0) > 0;

        // User balances
        $unpaidRent = $tenant->rent_balance ?? $tenant->rent_amount;
        // Use total utility balance from all active leases
        $unpaidUtilities = $tenant->total_utility_balance;
        $depositBalance = $tenant->deposit_amount ?? 0;
        $userCredit = $tenant-> user_credit ?? 0;

        // Load ALL utility proofs with lease and unit relationships (no limits, no filters)
        $utilityProofs = \App\Models\UtilityBillingProof::where('tenant_id', $tenant->id)
            ->with(['lease.unit'])
            ->orderBy('created_at', 'desc')
            ->get(); // Get ALL records, no pagination or limit

        // ✅ Calculate unpaid utilities grouped by billing month
        // Group all utility proofs by billing_month and sum their amounts
        $unpaidUtilitiesByMonth = [];
        foreach ($utilityProofs as $proof) {
            if (!$proof->billing_month) {
                continue; // Skip if no billing month
            }

            $billingMonth = $proof->billing_month;
            // Use amount from proof, or fallback to lease utility_balance, or 0
            $amount = $proof->amount ?? ($proof->lease->utility_balance ?? 0);

            if ($amount > 0) {
                if (!isset($unpaidUtilitiesByMonth[$billingMonth])) {
                    $unpaidUtilitiesByMonth[$billingMonth] = 0;
                }
                $unpaidUtilitiesByMonth[$billingMonth] += $amount;
            }
        }

        // Sort by month (most recent first) - convert to Carbon dates for proper sorting
        uksort($unpaidUtilitiesByMonth, function($a, $b) {
            try {
                $dateA = Carbon::parse($a);
                $dateB = Carbon::parse($b);
                return $dateB->gt($dateA) ? 1 : -1; // Most recent first
            } catch (\Exception $e) {
                // Fallback to string comparison if parsing fails
                return strcmp($b, $a);
            }
        });

        // ✅ Calculate unpaid month for Rent
        $unpaidRentMonth = $this->getUnpaidMonth($tenant, $lease, 'Rent');

        // ✅ Calculate unpaid month for Utilities
        // Use the most recent billing month from utility proofs, but never show future months
        if (!empty($unpaidUtilitiesByMonth)) {
            // Get the first (most recent) month from the sorted array
            $mostRecentBillingMonth = array_key_first($unpaidUtilitiesByMonth);
            // Try to parse and format it
            try {
                $billingDate = Carbon::parse($mostRecentBillingMonth);
                $currentMonth = now()->startOfMonth();

                // If the billing month is in the future, use current month instead
                if ($billingDate->gt($currentMonth)) {
                    $unpaidUtilitiesMonth = now()->format('F Y');
                } else {
                    $unpaidUtilitiesMonth = $billingDate->format('F Y');
                }
            } catch (\Exception $e) {
                // If parsing fails, use current month
                $unpaidUtilitiesMonth = now()->format('F Y');
            }
        } else {
            // No utility proofs, use current month
            $unpaidUtilitiesMonth = now()->format('F Y');
        }


        return view('tenant.payments', compact(
            'payments',
            'leasesForSelection',
            'depositExists',
            'unpaidRent',
            'unpaidUtilities',
            'depositBalance',
            'userCredit',
            'unpaidRentMonth',      // Pass dynamic month for rent
            'unpaidUtilitiesMonth',  // Pass dynamic month for utilities
            'utilityProofs',
            'unpaidUtilitiesByMonth'  // Pass unpaid utilities grouped by month

        ));
    }

    public function createNotification($title, $message)
    {
        $userId = Auth::id(); // Gets the ID directly, which is all you need for 'user_id'

        if ($userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Store a new payment
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'pay_method'  => 'required|string',
    //         'payment_for' => 'required|string',
    //         'pay_amount'  => 'required|numeric|min:1',
    //         'proof'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //         'account_no'  => 'nullable|string|max:255',
    //     ]);

    //     $tenant = Auth::user();
    //     $lease = Lease::where('user_id', $tenant->id)->latest()->first();

    //     // Force Deposit payment first
    //     if ($tenant->deposit_amount > 0 && $request->payment_for !== 'Deposit') {
    //         return redirect()->back()->with('error', 'You must pay the Deposit first.');
    //     }

    //     $pathToProof = $request->hasFile('proof')
    //         ? $request->file('proof')->store('proofs', 'public')
    //         : null;

    //     $referenceNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

    //     // Create payment record
    //     $payment = $tenant->payments()->create([
    //         'lease_id'         => $lease?->id,
    //         'pay_date'         => now(),
    //         'pay_amount'       => $request->pay_amount,
    //         'pay_method'       => $request->pay_method,
    //         'pay_status'       => 'Paid',
    //         'proof'            => $pathToProof,
    //         'payment_for'      => $request->payment_for,
    //         'account_no'       => $request->account_no,
    //         'reference_number' => $referenceNumber,
    //     ]);

    //     // Update tenant balances
    //     switch ($request->payment_for) {
    //         case 'Deposit':
    //             // Clear deposit
    //             $tenant->deposit_amount = 0;
    //             // Clear rent balance because deposit is fully paid
    //             $tenant->rent_balance = 0;
    //             break;

    //         case 'Rent':
    //             $tenant->rent_balance = max(0, $tenant->rent_balance - $request->pay_amount);
    //             break;

    //         case 'Utilities':
    //             $tenant->utility_balance = max(0, $tenant->utility_balance - $request->pay_amount);
    //             break;
    //     }

    //     $tenant->save();

    //     return redirect()->route('tenant.payments')
    //         ->with('success', 'Payment submitted successfully! Reference Number: ' . $referenceNumber);
    // }

    // Updated payment to determind if the tenant paid late
//    public function store(Request $request)
//     {
//         $request->validate([
//             'pay_method'  => 'required|string',
//             'payment_for' => 'required|string',
//             'pay_amount'  => 'required|numeric|min:1',
//             'proof'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
//             'account_no'  => 'nullable|string|max:255',
//         ]);

//         $tenant = Auth::user();
//         $lease = Lease::where('user_id', $tenant->id)->latest()->first();

//         if (!$lease) {
//             return redirect()->back()->with('error', 'Lease not found.');
//         }

//         // Force Deposit payment first
//         if ($tenant->deposit_amount > 0 && $request->payment_for !== 'Deposit') {
//             return redirect()->back()->with('error', 'You must pay the Deposit first.');
//         }

//         $pathToProof = $request->hasFile('proof')
//             ? $request->file('proof')->store('proofs', 'public')
//             : null;

//         $referenceNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

//         $paymentStatus = 'Paid';

//         if ($request->payment_for === 'Rent') {
//             // Use correct column and parse Carbon
//             $leaseStart = \Carbon\Carbon::parse($lease->lea_start_date);
//             $today = now();

//             $monthsSinceStart = $leaseStart->diffInMonths($today);
//             $dueDate = $leaseStart->copy()->addMonths($monthsSinceStart);

//             if ($today->gt($dueDate)) {
//                 $paymentStatus = 'Paid Late';
//             } else{
//                 $paymentStatus = 'Paid Early';
//             }
//         }


//         // Create payment
//         $payment = Payment::create([
//             'tenant_id'       => $tenant->id,
//             'lease_id'        => $lease->id,
//             'pay_date'        => now(),
//             'pay_amount'      => $request->pay_amount,
//             'pay_method'      => $request->pay_method,
//             'pay_status'      => $paymentStatus,
//             'proof'           => $pathToProof,
//             'payment_for'     => $request->payment_for,
//             'account_no'      => $request->account_no,
//             'reference_number'=> $referenceNumber,
//         ]);

//         // Update balances
//         switch ($request->payment_for) {
//             case 'Deposit':
//                 $tenant->deposit_amount = max(0, $tenant->deposit_amount - $request->pay_amount);

//                 if ($tenant->deposit_amount <= 0) {
//                     $tenant->rent_balance = 0;
//                 }

//                 $this->createNotification(
//                     'Deposit Paid',
//                     'Your deposit payment of ' . $request->pay_amount . ' has been received. You can now access Maintenance Requests.'
//                 );

//                 break;
//             case 'Rent':
//                 $tenant->rent_balance = max(0, $tenant->rent_balance - $request->pay_amount);

//                 $this->createNotification(
//                     'Rent Payment Received',
//                     'Your rent payment of ' . $request->pay_amount . ' has been received. Your new balance is ' . $tenant->rent_balance . '.'
//                 );

//                 break;
//             case 'Utilities':
//                 $tenant->utility_balance = max(0, $tenant->utility_balance - $request->pay_amount);

//                 $this->createNotification(
//                     'Utility Payment Received',
//                     'Your utility payment of ' . $request->pay_amount . ' has been received.'
//                 );

//                 break;
//         }

//         $tenant->save();

//         return redirect()->route('tenant.payments')
//             ->with('success', "Payment submitted successfully! Reference Number: {$referenceNumber}. Status: {$paymentStatus}");
//     }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'pay_method'  => 'required|string',
    //         'payment_for' => 'required|string',
    //         'pay_amount'  => 'required|numeric|min:1',
    //         'proof'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //         'account_no'  => 'nullable|string|max:255',
    //     ]);

    //     $tenant = Auth::user();
    //     $lease = Lease::where('user_id', $tenant->id)->latest()->first();

    //     if (!$lease) {
    //         return redirect()->back()->with('error', 'Lease not found.');
    //     }

    //     // Force Deposit payment first
    //     if ($tenant->deposit_amount > 0 && $request->payment_for !== 'Deposit') {
    //         return redirect()->back()->with('error', 'You must pay the Deposit first.');
    //     }

    //     $pathToProof = $request->hasFile('proof')
    //         ? $request->file('proof')->store('proofs', 'public')
    //         : null;

    //     $referenceNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

    //     // Determine payment status
    //     $paymentStatus = 'Paid';
    //     if ($request->payment_for === 'Rent') {
    //         $leaseStart = Carbon::parse($lease->lea_start_date);
    //         $today = now();
    //         $monthsSinceStart = $leaseStart->diffInMonths($today);
    //         $dueDate = $leaseStart->copy()->addMonths($monthsSinceStart);

    //         $paymentStatus = $today->gt($dueDate) ? 'Paid Late' : 'Paid Early';
    //     }

    //     // Apply available credits automatically for Rent/Utilities
    //     $finalAmount = $request->pay_amount;
    //     if (in_array($request->payment_for, ['Rent', 'Utilities']) && $tenant->user_credit > 0) {
    //         $creditUsed = min($tenant->user_credit, $finalAmount);
    //         $finalAmount -= $creditUsed;
    //         $tenant->user_credit -= $creditUsed;

    //         $this->createNotification(
    //             'Credit Applied',
    //             "₱{$creditUsed} of your credits were automatically applied to this {$request->payment_for} payment."
    //         );

    //     }

    //     // Create payment record
    //     $payment = Payment::create([
    //         'tenant_id'        => $tenant->id,
    //         'lease_id'         => $lease->id,
    //         'pay_date'         => now(),
    //         'pay_amount'       => $finalAmount,
    //         'pay_method'       => $request->pay_method,
    //         'pay_status'       => $paymentStatus,
    //         'proof'            => $pathToProof,
    //         'payment_for'      => $request->payment_for,
    //         'account_no'       => $request->account_no,
    //         'reference_number' => $referenceNumber,
    //     ]);

    //     // Update balances and credits
    //     switch ($request->payment_for) {
    //         case 'Deposit':
    //             $tenant->deposit_amount = max(0, $tenant->deposit_amount - $finalAmount);
    //             if ($tenant->deposit_amount <= 0) {
    //                 $tenant->rent_balance = 0;
    //             }
    //             $this->createNotification(
    //                 'Deposit Paid',
    //                 "Your deposit payment of ₱{$finalAmount} has been received. You can now access Maintenance Requests."
    //             );
    //             break;

    //         case 'Rent':
    //             $tenant->rent_balance = max(0, $tenant->rent_balance - $finalAmount);
    //             $this->createNotification(
    //                 'Rent Payment Received',
    //                 "Your rent payment of ₱{$finalAmount} has been received. Remaining rent balance: ₱{$tenant->rent_balance}."
    //             );
    //             break;

    //         case 'Utilities':
    //             $tenant->utility_balance = max(0, $tenant->utility_balance - $finalAmount);
    //             $this->createNotification(
    //                 'Utility Payment Received',
    //                 "Your utility payment of ₱{$finalAmount} has been received."
    //             );
    //             break;

    //         case 'Other':
    //             // Add amount as tenant credit
    //             $tenant->user_credit += $request->pay_amount;
    //             $this->createNotification(
    //                 'Credit Added',
    //                 "Your payment of ₱{$request->pay_amount} for Others has been added as credit. You now have ₱{$tenant->user_credit} in credits."
    //             );
    //             break;
    //     }

    //     $tenant->save();

    //     return redirect()->route('tenant.payments')
    //         ->with('success', "Payment submitted successfully! Reference Number: {$referenceNumber}. Status: {$paymentStatus}");
    // }

//     public function store(Request $request)
// {
//     $request->validate([
//         'pay_method'  => 'required|string',
//         'payment_for' => 'required|string',
//         'pay_amount'  => 'required|numeric|min:1',
//         'proof'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
//         'account_no'  => 'nullable|string|max:255',
//     ]);

//     $tenant = Auth::user();
//     $lease = Lease::where('user_id', $tenant->id)->latest()->first();

//     if (!$lease) {
//         return redirect()->back()->with('error', 'Lease not found.');
//     }

//     // Force Deposit payment first
//     if ($tenant->deposit_amount > 0 && $request->payment_for !== 'Deposit') {
//         return redirect()->back()->with('error', 'You must pay the Deposit first.');
//     }

//     $pathToProof = $request->hasFile('proof')
//         ? $request->file('proof')->store('proofs', 'public')
//         : null;

//     $referenceNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

//     // Determine payment status
//     $paymentStatus = 'Pending';
//     if ($request->payment_for === 'Rent') {
//         $leaseStart = Carbon::parse($lease->lea_start_date);
//         $today = now();
//         $monthsSinceStart = $leaseStart->diffInMonths($today);
//         $dueDate = $leaseStart->copy()->addMonths($monthsSinceStart);
//         $paymentStatus = $today->gt($dueDate) ? 'Paid Late' : 'Paid Early';
//     }

//     $finalAmount = $request->pay_amount;

//     /**
//      * 🧠 Step 1: Apply credit BEFORE payment for Rent or Utilities
//      */
//     $creditUsed = 0;

//     if (in_array($request->payment_for, ['Rent', 'Utilities']) && $tenant->user_credit > 0) {
//         if ($request->payment_for === 'Rent' && $tenant->rent_balance > 0) {
//             $creditUsed = min($tenant->user_credit, $tenant->rent_balance);
//             $tenant->rent_balance -= $creditUsed;
//         } elseif ($request->payment_for === 'Utilities' && $tenant->utility_balance > 0) {
//             $creditUsed = min($tenant->user_credit, $tenant->utility_balance);
//             $tenant->utility_balance -= $creditUsed;
//         }

//         $tenant->user_credit -= $creditUsed;

//         if ($creditUsed > 0) {
//             $this->createNotification(
//                 'Credit Applied',
//                 "₱{$creditUsed} of your credits were automatically applied to this {$request->payment_for} payment."
//             );
//         }
//     }

//     // Now apply the actual cash payment
//     $payment = Payment::create([
//         'tenant_id'        => $tenant->id,
//         'lease_id'         => $lease->id,
//         'pay_date'         => now(),
//         'pay_amount'       => $finalAmount,
//         'pay_method'       => $request->pay_method,
//         'pay_status'       => $paymentStatus,
//         'proof'            => $pathToProof,
//         'payment_for'      => $request->payment_for,
//         'account_no'       => $request->account_no,
//         'reference_number' => $referenceNumber,
//     ]);

//     /**
//      * 🧾 Step 2: Deduct payment from the correct balance
//      */
//     switch ($request->payment_for) {
//         case 'Deposit':
//             $tenant->deposit_amount = max(0, $tenant->deposit_amount - $finalAmount);
//             if ($tenant->deposit_amount <= 0) {
//                 $tenant->rent_balance = 0;
//             }
//             $this->createNotification(
//                 'Deposit Paid',
//                 "Your deposit payment of ₱{$finalAmount} has been received. You can now access Maintenance Requests."
//             );
//             break;

//         case 'Rent':
//             $tenant->rent_balance = max(0, $tenant->rent_balance - $finalAmount);
//             $this->createNotification(
//                 'Rent Payment Received',
//                 "Your rent payment of ₱{$finalAmount} has been received. Remaining rent balance: ₱{$tenant->rent_balance}."
//             );
//             break;

//         case 'Utilities':
//             $tenant->utility_balance = max(0, $tenant->utility_balance - $finalAmount);
//             $this->createNotification(
//                 'Utility Payment Received',
//                 "Your utility payment of ₱{$finalAmount} has been received."
//             );
//             break;

//         case 'Other':
//             $tenant->user_credit += $request->pay_amount;
//             $this->createNotification(
//                 'Credit Added',
//                 "Your payment of ₱{$request->pay_amount} for Others has been added as credit. You now have ₱{$tenant->user_credit} in credits."
//             );
//             break;
//     }

//     // ✅ Save all updates
//     $tenant->save();

//     return redirect()->route('tenant.payments')
//         ->with('success', "Payment submitted successfully! Reference Number: {$referenceNumber}. Status: {$paymentStatus}");
// }

    public function store(Request $request)
    {
        $request->validate([
            'lease_id'    => 'required|exists:leases,id',
            'pay_method'  => 'required|string',
            'payment_for' => 'required|string',
            'pay_amount'  => 'required|numeric|min:1',
            'proof'       => 'nullable|image|mimes:jpg,jpeg,png',
            'account_no'  => 'nullable|string|max:255',
        ]);

        $tenant = Auth::user();
        $lease = Lease::where('id', $request->lease_id)
            ->where('user_id', $tenant->id)
            ->first();

        if (!$lease) {
            return redirect()->back()->with('error', 'Selected lease not found.');
        }

        // Check if lease is still active or pending
        if (!in_array($lease->lea_status, ['active', 'pending', 'Active', 'Pending'])) {
            return redirect()->back()->with('error', 'You can only make payments for active or pending leases.');
        }

        // Force Deposit payment first - check both user-level and lease-level deposit
        $hasDeposit = ($tenant->deposit_amount > 0) || ($lease->deposit_amount > 0);
        if ($hasDeposit && $request->payment_for !== 'Deposit') {
            return redirect()->back()->with('error', 'You must pay the Deposit first.');
        }

        $pathToProof = $request->hasFile('proof')
            ? $request->file('proof')->store('proofs', 'public')
            : null;

        $referenceNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        // Always set to 'Pending' initially - no automatic status changes
        $paymentStatus = 'Pending';

        $finalAmount = $request->pay_amount;

        // ❌ REMOVE: Do NOT apply credits or deduct balances here
        // (This will be handled when the payment is accepted)

        // Create the payment record
        $payment = Payment::create([
            'tenant_id'        => $tenant->id,
            'lease_id'         => $lease->id,
            'pay_date'         => now(),
            'pay_amount'       => $finalAmount,
            'pay_method'       => $request->pay_method,
            'pay_status'       => $paymentStatus,  // Always 'Pending'
            'proof'            => $pathToProof,
            'payment_for'      => $request->payment_for,
            'account_no'       => $request->account_no,
            'reference_number' => $referenceNumber,
        ]);

        // ❌ REMOVE: All switch-case deduction logic (move to acceptance handler)

        return redirect()->route('tenant.payments')
            ->with('success', "Payment submitted successfully! Reference Number: {$referenceNumber}. Status: {$paymentStatus}");
    }

    private function getUnpaidMonth($tenant, $lease, $paymentType)
    {
        if (!$lease) {
            return now()->format('F Y');  // Fallback to current month
        }
        // Get the latest accepted payment for this type
        $latestPayment = $tenant->payments()
            ->where('payment_for', $paymentType)
            ->where('pay_status', 'Accepted')
            ->orderBy('pay_date', 'desc')
            ->first();
        $leaseStart = \Carbon\Carbon::parse($lease->lea_start_date);
        $currentMonth = now()->startOfMonth();
        if ($latestPayment) {
            // Use the payment date to determine the last paid month
            $lastPaidMonth = \Carbon\Carbon::parse($latestPayment->pay_date)->startOfMonth();
            $nextUnpaidMonth = $lastPaidMonth->copy()->addMonth();
        } else {
            // No payments: Start from lease start or current month
            $nextUnpaidMonth = $leaseStart->startOfMonth();
            if ($nextUnpaidMonth->lt($currentMonth)) {
                $nextUnpaidMonth = $currentMonth;
            }
        }
        return $nextUnpaidMonth->format('F Y');
    }

}
