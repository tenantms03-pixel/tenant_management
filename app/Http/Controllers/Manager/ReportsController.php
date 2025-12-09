<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\Unit;
// use App\Models\MaintenanceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;



class ReportsController extends Controller
{
    public function index()
    {
        $reports = [
            'active-tenants'       => 'Active Tenants',
            'payment-history'      => 'Payment History',
            'lease-summary'        => 'Lease Information',
            'maintenance-requests' => 'Maintenance Requests',
        ];

        return view('manager.reports.index', compact('reports'));
    }
    public function viewReportPdf(Request $request, $report)
    {
        switch ($report) {

            // ---------------- PAYMENT HISTORY ----------------
            case 'payment-history':
                $query = Payment::with(['tenant', 'lease.unit']);

                // Filter by purpose (legacy support)
                if ($request->filled('payment_for')) {
                    $query->where('payment_for', $request->payment_for);
                }

                // ✅ Comprehensive search filter - search ALL visible columns
                if ($request->filled('search')) {
                    $search = $request->search;

                    $query->where(function($q) use ($search) {
                        // 1. Search on the 'users' table columns (first_name, last_name, email)
                        $q->where(function($userQ) use ($search) { // <-- NEW NESTED WHERE
                            $userQ->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });

                        // 2. Search on the 'tenantApplication' relation (OR'd with the user fields)
                        $q->orWhereHas('tenantApplication', function($subQ) use ($search) {
                            $subQ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('unit_type', 'like', "%{$search}%");
                        });

                        // 3. Search on the 'leases' relation (OR'd with the previous fields/relations)
                        $q->orWhereHas('leases', function($leaseQuery) use ($search) {

                            $leaseQuery->where('lea_status', 'like', "%{$search}%")
                                    ->orWhere('room_no', 'like', "%{$search}%")
                                    ->orWhere('lea_terms', 'like', "%{$search}%");

                            // Search by formatted date, month, year (all OR'd inside this block)
                            // ... (Your date/month/year logic remains here) ...

                            // Unit
                            $leaseQuery->orWhereHas('unit', function($unitQuery) use ($search) {
                                $unitQuery->where('type', 'like', "%{$search}%")
                                        ->orWhere('room_no', 'like', "%{$search}%");
                            });
                        });
                    });
                }

                $payments = $query->orderBy('pay_date', 'desc')->get();

                $pdf = Pdf::loadView('reports.pdf.payment-history', [
                    'payments' => $payments
                ])->setPaper('a4', 'landscape');

                return $pdf->stream('payment-history.pdf');

            // ---------------- TENANT REPORT ----------------
            case 'active-tenants':
                $pendingTenants = User::with('tenantApplication')
                    ->where('role', 'tenant')->where('status', 'pending')->get();
                $approvedTenants = User::with(['tenantApplication', 'leases' => function($q) {
                    $q->where('lea_status', 'active')->latest('created_at');
                }])->where('role', 'tenant')->where('status', 'approved')->get();
                $rejectedTenants = User::with('tenantApplication')
                    ->where('role', 'tenant')->where('status', 'rejected')->get();

                $pdf = Pdf::loadView('reports.pdf.active-tenants', [
                    'pendingTenants' => $pendingTenants,
                    'approvedTenants' => $approvedTenants,
                    'rejectedTenants' => $rejectedTenants
                ])->setPaper('a4', 'landscape');

                return $pdf->stream('active-tenants.pdf');

            // ---------------- MAINTENANCE REQUESTS ----------------
            case 'maintenance-requests':
                // Start query with join
                $query = DB::table('maintenance_requests')
                    ->join('tenant_applications', 'maintenance_requests.tenant_id', '=', 'tenant_applications.user_id')
                    ->select(
                        'tenant_applications.full_name as tenant_name',
                        'maintenance_requests.room_no',
                        'maintenance_requests.unit_type',
                        'maintenance_requests.description',
                        'maintenance_requests.supposed_date',
                        'maintenance_requests.status',
                        'maintenance_requests.urgency',
                        'maintenance_requests.issue_image',
                        'maintenance_requests.created_at'
                    );

                // ✅ Apply filters (same as your index)
                // if ($request->filled('status')) {
                //     $query->where('maintenance_requests.status', $request->status);
                // }

                // if ($request->filled('urgency')) {
                //     $query->where('maintenance_requests.urgency', $request->urgency);
                // }

                // // ✅ Live search (works across tenant + maintenance columns)
                // if ($request->filled('search')) {
                //     $search = $request->search;
                //     $query->where(function ($q) use ($search) {
                //         $q->where('tenant_applications.full_name', 'like', "%{$search}%")
                //         ->orWhere('maintenance_requests.description', 'like', "%{$search}%")
                //         ->orWhere('maintenance_requests.status', 'like', "%{$search}%")
                //         ->orWhere('maintenance_requests.urgency', 'like', "%{$search}%")
                //         ->orWhere('maintenance_requests.room_no', 'like', "%{$search}%")
                //         ->orWhere('maintenance_requests.unit_type', 'like', "%{$search}%");
                //     });
                // }

                // // ✅ Execute query and export
                // $requests = $query->orderBy('maintenance_requests.created_at', 'desc')->get();

                $requests = MaintenanceRequest::with(['tenant.leases'])
                ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
                ->when($request->filled('urgency'), fn($q) => $q->where('urgency', $request->urgency))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->whereHas('tenant', fn($t) => $t->where('first_name', 'like', "%$search%"))
                    ->orWhere('description', 'like', "%$search%");
                })
                ->latest()
                ->get();

                $pdf = Pdf::loadView('reports.pdf.maintenance-requests', [
                    'requests' => $requests
                ])->setPaper('a4', 'landscape');

                return $pdf->stream('maintenance-requests.pdf');


            // ---------------- LEASE SUMMARY ----------------
            case 'lease-summary':
            $query = User::select('users.*')
                ->with([
                    'tenantApplication',
                    'leases' => function($q) {
                        $q->latest('created_at')->with('unit');
                    }
                ])
                ->where('role', 'tenant')
                ->where('status', 'approved');

            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);

                    // Tenant application
                    $q->orWhereHas('tenantApplication', function($subQ) use ($search) {
                        $subQ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('unit_type', 'like', "%{$search}%");
                    });

                    // Lease
                    $q->orWhereHas('leases', function($leaseQuery) use ($search) {

                        $leaseQuery->where('lea_status', 'like', "%{$search}%")
                                ->orWhere('room_no', 'like', "%{$search}%")
                                ->orWhere('lea_terms', 'like', "%{$search}%");

                        // Search by formatted date
                        $dateFormats = ['Y-m-d', 'm/d/Y', 'M d, Y', 'F d, Y', 'Y', 'M Y', 'F Y'];
                        foreach ($dateFormats as $format) {
                            try {
                                $parsedDate = \Carbon\Carbon::createFromFormat($format, $search);
                                $leaseQuery->orWhereDate('lea_start_date', $parsedDate)
                                        ->orWhereDate('lea_end_date', $parsedDate);
                                break;
                            } catch (\Exception $e) {}
                        }

                        // Month search
                        $monthMap = [
                            'january' => 1, 'jan' => 1,
                            'february' => 2, 'feb' => 2,
                            'march' => 3, 'mar' => 3,
                            'april' => 4, 'apr' => 4,
                            'may' => 5,
                            'june' => 6, 'jun' => 6,
                            'july' => 7, 'jul' => 7,
                            'august' => 8, 'aug' => 8,
                            'september' => 9, 'sep' => 9,
                            'october' => 10, 'oct' => 10,
                            'november' => 11, 'nov' => 11,
                            'december' => 12, 'dec' => 12,
                        ];

                        $lower = strtolower($search);
                        if (isset($monthMap[$lower])) {
                            $leaseQuery->orWhereMonth('lea_start_date', $monthMap[$lower])
                                    ->orWhereMonth('lea_end_date', $monthMap[$lower]);
                        }

                        // Year search
                        if (strlen($search) === 4 && is_numeric($search)) {
                            $leaseQuery->orWhereYear('lea_start_date', $search)
                                    ->orWhereYear('lea_end_date', $search);
                        }

                        // Unit
                        $leaseQuery->orWhereHas('unit', function($unitQuery) use ($search) {
                            $unitQuery->where('type', 'like', "%{$search}%")
                                    ->orWhere('room_no', 'like', "%{$search}%");
                        });
                    });
                });
            }

            // ✅ FIX: Remove duplicates using unique() on the collection instead of GROUP BY
            $data = $query->get()->unique('id');
            $total = $data->count();

            // ✅ REMOVE dd($data); - This was preventing PDF generation

            $pdf = Pdf::loadView('reports.pdf.lease-summary', [
                'data' => $data,
                'total' => $total
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('lease-summary.pdf');

            default:
                abort(404, 'Report not available for PDF view.');
        }
    }

    public function show(Request $request, $report)
    {
        $total = 0;
        $currentFilter = '';
        $data = collect();
        $title = '';
        $availableUnits = null;
        $tenantsForPayment = [];

        switch ($report) {
            case 'active-tenants':
                $query = User::with(['tenantApplication', 'leases' => function($q) {
                    $q->where('lea_status', 'active')->latest('created_at');
                }])->where('role', 'tenant')->where('status', 'approved');
                // ✅ Search filter (includes full_name, unit_type, and employment_status)
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('tenantApplication', function($subQ) use ($search) {
                            $subQ->where('full_name', 'like', "%{$search}%")  // Search tenant full_name
                                ->orWhere('unit_type', 'like', "%{$search}%")
                                ->orWhere('employment_status', 'like', "%{$search}%");
                        });
                    });
                    $currentFilter = $request->search;
                }
                $total = $query->count();
                $data = $query->paginate(10);
                $title = "Active Tenants";
                break;

            case 'payment-history':

                $query = Payment::with(['tenant', 'lease.unit']);

                // ✅ Search filter - search across ALL visible columns
                if ($request->filled('search')) {
                    $search = $request->search;

                    $query->where(function($q) use ($search) {

                        // ✅ 1. Search by Tenant Name / Email
                        $q->whereHas('tenant', function($tenantQuery) use ($search) {
                            $tenantQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });

                        // ✅ 2. Search by Amount
                        if (is_numeric($search)) {
                            $q->orWhere('pay_amount', 'like', "%{$search}%");
                        }

                        // ✅ 3. Search by Date (Multiple Formats)
                        $dateFormats = ['Y-m-d', 'm/d/Y', 'M d, Y', 'F d, Y', 'Y', 'M Y', 'F Y'];
                        foreach ($dateFormats as $format) {
                            try {
                                $parsedDate = \Carbon\Carbon::createFromFormat($format, $search);
                                $q->orWhereDate('pay_date', $parsedDate->format('Y-m-d'));
                                break;
                            } catch (\Exception $e) {}
                        }

                        // ✅ Search by Month Name
                        $monthMap = [
                            'january'=>1,'jan'=>1, 'february'=>2,'feb'=>2,
                            'march'=>3,'mar'=>3, 'april'=>4,'apr'=>4,
                            'may'=>5, 'june'=>6,'jun'=>6,
                            'july'=>7,'jul'=>7, 'august'=>8,'aug'=>8,
                            'september'=>9,'sep'=>9, 'october'=>10,'oct'=>10,
                            'november'=>11,'nov'=>11, 'december'=>12,'dec'=>12
                        ];

                        $searchLower = strtolower($search);
                        if (isset($monthMap[$searchLower])) {
                            $q->orWhereMonth('pay_date', $monthMap[$searchLower]);
                        }

                        // ✅ Search by Year
                        if (strlen($search) == 4 && is_numeric($search)) {
                            $q->orWhereYear('pay_date', $search);
                        }

                        // ✅ 4. Search by Payment For
                        $q->orWhere('payment_for', 'like', "%{$search}%");

                        // ✅ 5. Search by Status
                        $q->orWhere('pay_status', 'like', "%{$search}%");

                        // ✅ 6. Additional Fields
                        $q->orWhere('pay_method', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('account_no', 'like', "%{$search}%");

                        // ✅ 7. Search by Room / Unit
                        $q->orWhereHas('lease', function($leaseQuery) use ($search) {
                            $leaseQuery->where('room_no', 'like', "%{$search}%")
                                ->orWhereHas('unit', function($unitQuery) use ($search) {
                                    $unitQuery->where('type', 'like', "%{$search}%")
                                            ->orWhere('room_no', 'like', "%{$search}%");
                                });
                        });

                    });

                    $currentFilter = $search;
                }

                // ✅ Tenants for Make Payment Modal
                $tenantsForPayment = \App\Models\User::with('leases.unit')
                    ->where('role', 'tenant')
                    ->where('status', 'approved')
                    ->whereHas('leases', function ($q) {
                        $q->whereIn('lea_status', ['active', 'pending']);
                    })
                    ->orderBy('first_name')
                    ->get();

                // ✅ Total Accepted Payments
                $total = (clone $query)
                    ->where('pay_status', 'Accepted')
                    ->sum('pay_amount');

                // ✅ Paginated Data
                $data = $query->orderBy('pay_date', 'desc')->paginate(10);

                $title = "Payment History per Tenant";

            break;

            case 'lease-summary':
                $query = User::with(['tenantApplication', 'leases' => function($q) {
                    $q->latest('created_at')->with('unit'); // Get all leases regardless of status to preserve history
                }])
                ->where('role', 'tenant')
                ->where('status', 'approved');

                $availableUnits = Unit::with(['leases' => fn($query) => $query->whereIn('lea_status', ['active', 'pending'])])
                    ->where(function ($query) {
                        $query->where('status', 'vacant')
                            ->orWhere(function ($bedQuery) {
                                $bedQuery->where('type', 'Bed-Spacer')
                                    ->whereColumn('no_of_occupants', '<', 'capacity');
                            });
                    })
                    ->get();

                // ✅ Comprehensive search filter - search ALL visible columns
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        // 1. Search by Tenant Name (column: Tenant)
                        $q->where('email', 'like', "%{$search}%")
                          ->orWhere('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);

                        // 2. Search by Unit Type (column: Unit Type)
                        $q->orWhereHas('tenantApplication', function($subQ) use ($search) {
                            $subQ->where('full_name', 'like', "%{$search}%")
                                 ->orWhere('unit_type', 'like', "%{$search}%");
                        });

                        // 3. Search by Lease fields (Start Date, End Date, Term, Status, Room)
                        $q->orWhereHas('leases', function($leaseQuery) use ($search) {
                            // Search by lease status
                            $leaseQuery->where('lea_status', 'like', "%{$search}%")
                                      ->orWhere('room_no', 'like', "%{$search}%")
                                      ->orWhere('lea_terms', 'like', "%{$search}%");

                            // Search by lease dates
                            try {
                                $dateFormats = ['Y-m-d', 'm/d/Y', 'M d, Y', 'F d, Y', 'Y', 'M Y', 'F Y'];
                                foreach ($dateFormats as $format) {
                                    try {
                                        $parsedDate = \Carbon\Carbon::createFromFormat($format, $search);
                                        $leaseQuery->orWhereDate('lea_start_date', $parsedDate->format('Y-m-d'))
                                                  ->orWhereDate('lea_end_date', $parsedDate->format('Y-m-d'));
                                        break;
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                                // Search by month name
                                $monthMap = [
                                    'january' => 1, 'jan' => 1,
                                    'february' => 2, 'feb' => 2,
                                    'march' => 3, 'mar' => 3,
                                    'april' => 4, 'apr' => 4,
                                    'may' => 5,
                                    'june' => 6, 'jun' => 6,
                                    'july' => 7, 'jul' => 7,
                                    'august' => 8, 'aug' => 8,
                                    'september' => 9, 'sep' => 9,
                                    'october' => 10, 'oct' => 10,
                                    'november' => 11, 'nov' => 11,
                                    'december' => 12, 'dec' => 12,
                                ];
                                $searchLower = strtolower($search);
                                if (isset($monthMap[$searchLower])) {
                                    $leaseQuery->orWhereMonth('lea_start_date', $monthMap[$searchLower])
                                              ->orWhereMonth('lea_end_date', $monthMap[$searchLower]);
                                }
                                // Search by year
                                if (strlen($search) == 4 && is_numeric($search)) {
                                    $leaseQuery->orWhereYear('lea_start_date', $search)
                                              ->orWhereYear('lea_end_date', $search);
                                }
                            } catch (\Exception $e) {
                                // Continue with other searches
                            }

                            // Search by unit type and room number through lease->unit
                            $leaseQuery->orWhereHas('unit', function($unitQuery) use ($search) {
                                $unitQuery->where('type', 'like', "%{$search}%")
                                         ->orWhere('room_no', 'like', "%{$search}%");
                            });
                        });
                    });
                    $currentFilter = $request->search;
                }
                $total = $query->count();
                $data = $query->paginate(10);
                $title = "Lease Summary";
                break;

           case 'maintenance-requests':
                // No changes needed - already has search
                $query = \App\Models\MaintenanceRequest::with(['tenant', 'unit']);

                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('urgency')) {
                    $query->where('urgency', $request->urgency);
                }

               if ($request->filled('search')) {
                    $search = $request->search;

                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('urgency', 'like', "%{$search}%")
                        ->orWhereHas('tenant', function($subQ) use ($search) {
                            $subQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                    });
                }


                $total = (clone $query)->count();
                $data = $query->orderBy('created_at', 'desc')->paginate(10);
                $title = "Maintenance Requests";
                break;

            default:
                abort(404, 'Report not found.');
        }

        return view('manager.reports.show', compact(
            'data', 'title', 'report', 'total', 'currentFilter', 'availableUnits', 'tenantsForPayment'
        ));
    }

    // public function show(Request $request, $report)
    // {
    //     $total = 0;
    //     $currentFilter = '';
    //     $data = collect();
    //     $title = '';

    //     switch ($report) {
    //         case 'active-tenants':
    //             $query = User::with(['tenantApplication', 'leases' => function($q) {
    //                 $q->where('lea_status', 'active')->latest('created_at');
    //             }])->where('role', 'tenant')->where('status', 'approved');

    //             // Filter by Unit Type
    //             if ($request->filled('unit_type')) {
    //                 $query->whereHas('tenantApplication', function($q) use ($request) {
    //                     $q->where('unit_type', $request->unit_type);
    //                 });
    //             }

    //             // Filter by Employment Status
    //             if ($request->filled('employment_status')) {
    //                 $query->whereHas('tenantApplication', function($q) use ($request) {
    //                     $q->where('employment_status', $request->employment_status);
    //                 });
    //             }

    //             $total = $query->count();
    //             $data = $query->paginate(10);

    //             $title = "Active Tenants";
    //             $currentFilter = $request->only(['unit_type', 'employment_status']);
    //             break;


    //         case 'payment-history':
    //             $query = Payment::with('tenant');

    //             if ($request->filled('payment_for')) {
    //                 $query->where('payment_for', $request->payment_for);
    //                 $currentFilter = $request->payment_for;
    //             }

    //             $total = (clone $query)->sum('pay_amount');
    //             $data = $query->orderBy('pay_date', 'desc')->paginate(10);
    //             $title = "Payment History per Tenant";
    //             break;

    //         case 'lease-summary':
    //             $query = User::with(['tenantApplication', 'leases' => function($q) {
    //                 $q->where('lea_status', 'active')->latest('created_at');
    //             }])
    //             ->where('role', 'tenant')
    //             ->where('status', 'approved');

    //             $total = $query->count();
    //             $data = $query->paginate(10);
    //             $title = "List of Active Lease";
    //             break;

    //         case 'maintenance-requests':
    //             $query = \App\Models\MaintenanceRequest::query();

    //             // Filters
    //             if ($request->filled('status')) {
    //                 $query->where('status', $request->status);
    //             }
    //             if ($request->filled('urgency')) {
    //                 $query->where('urgency', $request->urgency);
    //             }

    //             // ✅ Live search (without relations)
    //             if ($request->filled('search')) {
    //                 $search = $request->search;
    //                 $query->where(function ($q) use ($search) {
    //                     $q->where('description', 'like', "%{$search}%")
    //                     ->orWhere('status', 'like', "%{$search}%")
    //                     ->orWhere('urgency', 'like', "%{$search}%");
    //                 });
    //             }

    //             $total = (clone $query)->count();
    //             $data = $query->orderBy('created_at', 'desc')->paginate(10);
    //             $title = "Maintenance Requests";
    //             break;

    //         default:
    //             abort(404, 'Report not found.');
    //     }

    //     return view('manager.reports.show', compact(
    //         'data', 'title', 'report', 'total', 'currentFilter'
    //     ));
    // }



    // public function export(Request $request, $report)
    // {
    //     switch ($report) {
    //         // ---------------- Payment History ----------------
    //         case 'payment-history':
    //             $query = Payment::with('tenant');

    //             if ($request->filled('payment_for')) {
    //                 $query->where('payment_for', $request->payment_for);
    //             }

    //             $payments = $query->orderBy('pay_date', 'desc')->get();

    //             $filename = "payment-history-" . now()->format('Y-m-d_H-i-s') . ".csv";

    //             $response = new StreamedResponse(function () use ($payments) {
    //                 $handle = fopen('php://output', 'w');

    //                 // CSV header
    //                 fputcsv($handle, ['Reference_number', 'Tenant', 'Amount', 'Date', 'Purpose', 'Status']);

    //                 foreach ($payments as $payment) {
    //                     fputcsv($handle, [
    //                         $payment->reference_number,
    //                         $payment->tenant->name ?? 'N/A',
    //                         $payment->pay_amount,
    //                         optional($payment->pay_date)->format('Y-m-d'),
    //                         ucfirst($payment->payment_for),
    //                         $payment->pay_status,
    //                     ]);
    //                 }

    //                 fclose($handle);
    //             });

    //             $response->headers->set('Content-Type', 'text/csv');
    //             $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

    //             return $response;

    //         // ---------------- Tenants Export ----------------
    //         case 'active-tenants':
    //             $pendingTenants = User::with('tenantApplication')
    //                 ->where('role', 'tenant')->where('status', 'pending')->get();
    //             $approvedTenants = User::with(['tenantApplication', 'leases' => function($q) {
    //                 $q->where('lea_status', 'active')->latest('created_at');
    //             }])->where('role', 'tenant')->where('status', 'approved')->get();
    //             $rejectedTenants = User::with('tenantApplication')
    //                 ->where('role', 'tenant')->where('status', 'rejected')->get();

    //             $filename = "tenants-export-" . now()->format('Y-m-d_H-i-s') . ".csv";

    //             $response = new StreamedResponse(function () use ($pendingTenants, $approvedTenants, $rejectedTenants) {
    //                 $handle = fopen('php://output', 'w');

    //                 // Export date
    //                 fputcsv($handle, ['Date of Export', now()->format('Y-m-d H:i:s')]);
    //                 fputcsv($handle, []);

    //                 // --- Pending Tenants ---
    //                 fputcsv($handle, ['Pending Tenants']);
    //                 fputcsv($handle, ['Full Name','Email','Contact Number','Unit Type','Employment Status','Source of Income','Emergency Name','Emergency Number']);
    //                 foreach ($pendingTenants as $tenant) {
    //                     $app = $tenant->tenantApplication;
    //                     fputcsv($handle, [
    //                         $tenant->name,
    //                         $tenant->email,
    //                         $app->contact_number ?? 'N/A',
    //                         $app->unit_type ?? 'N/A',
    //                         $app->employment_status ?? 'N/A',
    //                         $app->source_of_income ?? 'N/A',
    //                         $app->emergency_name ?? 'N/A',
    //                         $app->emergency_number ?? 'N/A',
    //                     ]);
    //                 }
    //                 fputcsv($handle, []);

    //                 // --- Approved Tenants ---
    //                 fputcsv($handle, ['Approved Tenants']);
    //                 fputcsv($handle, ['Full Name','Email','Contact Number','Unit Type','Employment Status','Source of Income','Emergency Name','Emergency Number','Lease Start','Lease End']);
    //                 foreach ($approvedTenants as $tenant) {
    //                     $app = $tenant->tenantApplication;
    //                     $lease = $tenant->leases->first(); // latest active lease
    //                     fputcsv($handle, [
    //                         $tenant->name,
    //                         $tenant->email,
    //                         $app->contact_number ?? 'N/A',
    //                         $app->unit_type ?? 'N/A',
    //                         $app->employment_status ?? 'N/A',
    //                         $app->source_of_income ?? 'N/A',
    //                         $app->emergency_name ?? 'N/A',
    //                         $app->emergency_number ?? 'N/A',
    //                         $lease?->lea_start_date ?? 'N/A',
    //                         $lease?->lea_end_date ?? 'N/A',
    //                     ]);
    //                 }
    //                 fputcsv($handle, []);

    //                 // --- Rejected Tenants ---
    //                 fputcsv($handle, ['Rejected Tenants']);
    //                 fputcsv($handle, ['Full Name','Email','Contact Number','Unit Type','Employment Status','Source of Income','Emergency Name','Emergency Number','Rejection Reason']);
    //                 foreach ($rejectedTenants as $tenant) {
    //                     $app = $tenant->tenantApplication;
    //                     fputcsv($handle, [
    //                         $tenant->name,
    //                         $tenant->email,
    //                         $app->contact_number ?? 'N/A',
    //                         $app->unit_type ?? 'N/A',
    //                         $app->employment_status ?? 'N/A',
    //                         $app->source_of_income ?? 'N/A',
    //                         $app->emergency_name ?? 'N/A',
    //                         $app->emergency_number ?? 'N/A',
    //                         $tenant->rejection_reason ?? 'N/A',
    //                     ]);
    //                 }

    //                 fclose($handle);
    //             });

    //             $response->headers->set('Content-Type', 'text/csv');
    //             $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

    //             return $response;

    //         default:
    //             abort(404, 'Export not available for this report.');
    //     }
    // }

    // Landscape orientation
    public function export(Request $request, $report)
{
    switch ($report) {
        // ---------------- PAYMENT HISTORY ----------------
        case 'payment-history':
            $query = Payment::with(['tenant', 'lease.unit']);

            // Filter by purpose (legacy support)
            if ($request->filled('payment_for')) {
                $query->where('payment_for', $request->payment_for);
            }

            // ✅ Comprehensive search filter - search ALL visible columns
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    // 1. Search by Tenant Name (column: Tenant)
                    $q->whereHas('tenant', function($tenantQuery) use ($search) {
                        $tenantQuery->where('email', 'like', "%{$search}%")
                                   ->orWhere('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });

                    // 2. Search by Amount (column: Amount)
                    if (is_numeric($search)) {
                        $q->orWhere('pay_amount', 'like', "%{$search}%");
                    }

                    // 3. Search by Date (column: Date)
                    try {
                        $dateFormats = ['Y-m-d', 'm/d/Y', 'M d, Y', 'F d, Y', 'Y', 'M Y', 'F Y'];
                        foreach ($dateFormats as $format) {
                            try {
                                $parsedDate = \Carbon\Carbon::createFromFormat($format, $search);
                                $q->orWhereDate('pay_date', $parsedDate->format('Y-m-d'));
                                break;
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                        $monthNames = ['january', 'february', 'march', 'april', 'may', 'june',
                                     'july', 'august', 'september', 'october', 'november', 'december',
                                     'jan', 'feb', 'mar', 'apr', 'may', 'jun',
                                     'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                        if (in_array(strtolower($search), $monthNames)) {
                            $monthNum = array_search(strtolower($search), array_map('strtolower', $monthNames)) % 12 + 1;
                            $q->orWhereMonth('pay_date', $monthNum);
                        }
                        if (strlen($search) == 4 && is_numeric($search)) {
                            $q->orWhereYear('pay_date', $search);
                        }
                    } catch (\Exception $e) {
                        // Continue with other searches
                    }

                    // 4. Search by Purpose/Payment Type (column: Purpose)
                    $q->orWhere('payment_for', 'like', "%{$search}%");

                    // 5. Search by Status (column: Status)
                    $q->orWhere('pay_status', 'like', "%{$search}%");

                    // Additional fields
                    $q->orWhere('pay_method', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhere('account_no', 'like', "%{$search}%");

                    // Search by room number
                    $q->orWhereHas('lease', function($leaseQuery) use ($search) {
                        $leaseQuery->where('room_no', 'like', "%{$search}%")
                                  ->orWhereHas('unit', function($unitQuery) use ($search) {
                                      $unitQuery->where('type', 'like', "%{$search}%")
                                               ->orWhere('room_no', 'like', "%{$search}%");
                                  });
                    });
                });
            }

            $payments = $query->orderBy('pay_date', 'desc')->get();

            $pdf = Pdf::loadView('reports.pdf.payment-history', compact('payments'))
                      ->setPaper('a4', 'landscape');

            return $pdf->download("payment-history-" . now()->format('Y-m-d_H-i-s') . ".pdf");

        // ---------------- ACTIVE TENANTS ----------------
        case 'active-tenants':
        $query = User::with(['tenantApplication', 'leases' => function ($q) {
            $q->where('lea_status', 'active')->latest('created_at');
        }])->where('role', 'tenant');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('tenantApplication', function ($subQ) use ($search) {
                $subQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('unit_type', 'like', "%{$search}%")
                    ->orWhere('employment_status', 'like', "%{$search}%");
            });
        }

        // ✅ Order alphabetically by full name
        $approvedTenants = $query->orderByRaw("CONCAT(first_name, ' ', last_name) ASC")->get();

        try {
            $pdf = Pdf::loadView('reports.pdf.active-tenants', compact('approvedTenants'))
                    ->setPaper('a4', 'landscape');

            return $pdf->download("active-tenants-" . now()->format('Y-m-d_H-i-s') . ".pdf");
        } catch (\Exception $e) {
            dd($e->getMessage());
        }


        // ---------------- LEASE SUMMARY ----------------
        case 'lease-summary':
            $query = User::with(['tenantApplication', 'leases' => function ($q) {
                        $q->latest('created_at')->with('unit'); // Get all leases regardless of status to preserve history
                    }])
                    ->where('role', 'tenant')
                    ->where('status', 'approved');

            // ✅ Comprehensive search filter - search ALL visible columns
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    // 1. Search by Tenant Name
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);

                    // 2. Search by Unit Type
                    $q->orWhereHas('tenantApplication', function($subQ) use ($search) {
                        $subQ->where('full_name', 'like', "%{$search}%")
                             ->orWhere('unit_type', 'like', "%{$search}%");
                    });

                    // 3. Search by Lease fields
                    $q->orWhereHas('leases', function($leaseQuery) use ($search) {
                        $leaseQuery->where('lea_status', 'like', "%{$search}%")
                                  ->orWhere('room_no', 'like', "%{$search}%")
                                  ->orWhere('lea_terms', 'like', "%{$search}%");

                        // Search by dates
                        try {
                            $dateFormats = ['Y-m-d', 'm/d/Y', 'M d, Y', 'F d, Y', 'Y', 'M Y', 'F Y'];
                            foreach ($dateFormats as $format) {
                                try {
                                    $parsedDate = \Carbon\Carbon::createFromFormat($format, $search);
                                    $leaseQuery->orWhereDate('lea_start_date', $parsedDate->format('Y-m-d'))
                                              ->orWhereDate('lea_end_date', $parsedDate->format('Y-m-d'));
                                    break;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            $monthMap = [
                                'january' => 1, 'jan' => 1, 'february' => 2, 'feb' => 2,
                                'march' => 3, 'mar' => 3, 'april' => 4, 'apr' => 4,
                                'may' => 5, 'june' => 6, 'jun' => 6, 'july' => 7, 'jul' => 7,
                                'august' => 8, 'aug' => 8, 'september' => 9, 'sep' => 9,
                                'october' => 10, 'oct' => 10, 'november' => 11, 'nov' => 11,
                                'december' => 12, 'dec' => 12,
                            ];
                            $searchLower = strtolower($search);
                            if (isset($monthMap[$searchLower])) {
                                $leaseQuery->orWhereMonth('lea_start_date', $monthMap[$searchLower])
                                          ->orWhereMonth('lea_end_date', $monthMap[$searchLower]);
                            }
                            if (strlen($search) == 4 && is_numeric($search)) {
                                $leaseQuery->orWhereYear('lea_start_date', $search)
                                          ->orWhereYear('lea_end_date', $search);
                            }
                        } catch (\Exception $e) {
                            // Continue
                        }

                        $leaseQuery->orWhereHas('unit', function($unitQuery) use ($search) {
                            $unitQuery->where('type', 'like', "%{$search}%")
                                     ->orWhere('room_no', 'like', "%{$search}%");
                        });
                    });
                });
            }

            $data = $query->get();
            $total = $data->count();

            $pdf = Pdf::loadView('reports.pdf.lease-summary', compact('data','total'))
                      ->setPaper('a4', 'landscape');

            return $pdf->download("lease-summary-" . now()->format('Y-m-d_H-i-s') . ".pdf");

        // ---------------- MAINTENANCE REQUESTS ----------------
        case 'maintenance-requests':
            $query = DB::table('maintenance_requests')
                        ->join('tenant_applications', 'maintenance_requests.tenant_id', '=', 'tenant_applications.user_id')
                        ->select(
                            'tenant_applications.full_name as tenant_name',
                            'maintenance_requests.room_no',
                            'maintenance_requests.unit_type',
                            'maintenance_requests.description',
                            'maintenance_requests.supposed_date',
                            'maintenance_requests.status',
                            'maintenance_requests.urgency',
                            'maintenance_requests.issue_image',
                            'maintenance_requests.created_at'
                        );

            // if ($request->filled('status')) {
            //     $query->where('maintenance_requests.status', $request->status);
            // }
            // if ($request->filled('urgency')) {
            //     $query->where('maintenance_requests.urgency', $request->urgency);
            // }
            // if ($request->filled('search')) {
            //     $search = $request->search;
            //     $query->where(function ($q) use ($search) {
            //         $q->where('tenant_applications.full_name', 'like', "%{$search}%")
            //           ->orWhere('maintenance_requests.description', 'like', "%{$search}%")
            //           ->orWhere('maintenance_requests.status', 'like', "%{$search}%")
            //           ->orWhere('maintenance_requests.urgency', 'like', "%{$search}%")
            //           ->orWhere('maintenance_requests.room_no', 'like', "%{$search}%")
            //           ->orWhere('maintenance_requests.unit_type', 'like', "%{$search}%");
            //     });
            // }

            // $requests = $query->orderBy('maintenance_requests.created_at', 'desc')->get();

            $requests = MaintenanceRequest::with(['tenant.leases'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('urgency'), fn($q) => $q->where('urgency', $request->urgency))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->whereHas('tenant', fn($t) => $t->where('full_name', 'like', "%$search%"))
                ->orWhere('description', 'like', "%$search%");
            })
            ->latest()
            ->get();

            $pdf = Pdf::loadView('reports.pdf.maintenance-requests', compact('requests'))
                      ->setPaper('a4', 'landscape');

            return $pdf->download("maintenance-requests-" . now()->format('Y-m-d_H-i-s') . ".pdf");

        default:
            abort(404, 'Export not available for this report.');
    }
}


    // A4 Orientation
    // public function export(Request $request, $report)
    // {
    //     switch ($report) {
    //         // ---------------- PAYMENT HISTORY ----------------
    //         case 'payment-history':
    //             $query = Payment::with('tenant');

    //             if ($request->filled('payment_for')) {
    //                 $query->where('payment_for', $request->payment_for);
    //             }

    //             $payments = $query->orderBy('pay_date', 'desc')->get();

    //             $pdf = Pdf::loadView('reports.pdf.payment-history', compact('payments'))
    //                 ->setPaper('a4', 'landscape'); // A4 + landscape orientation

    //             $filename = 'payment-history-' . now()->format('Y-m-d_H-i-s') . '.pdf';
    //             return $pdf->download($filename);

    //         // ---------------- ACTIVE TENANTS ----------------
    //         case 'active-tenants':
    //             $pendingTenants = User::with('tenantApplication')
    //                 ->where('role', 'tenant')->where('status', 'pending')->get();

    //             $approvedTenants = User::with([
    //                 'tenantApplication',
    //                 'leases' => function ($q) {
    //                     $q->where('lea_status', 'active')->latest('created_at');
    //                 }
    //             ])->where('role', 'tenant')->where('status', 'approved')->get();

    //             $rejectedTenants = User::with('tenantApplication')
    //                 ->where('role', 'tenant')->where('status', 'rejected')->get();

    //             $pdf = Pdf::loadView('reports.pdf.active-tenants', compact(
    //                 'pendingTenants', 'approvedTenants', 'rejectedTenants'
    //             ))->setPaper('a4', 'portrait'); // A4 + portrait orientation

    //             $filename = 'active-tenants-' . now()->format('Y-m-d_H-i-s') . '.pdf';
    //             return $pdf->download($filename);

    //         default:
    //             abort(404, 'Export not available for this report.');
    //     }
    // }


    // public function updatePaymentStatus(Request $request, Payment $payment)
    // {
    //     $request->validate([
    //         'pay_status' => 'required|in:Pending,Accepted',
    //     ]);

    //     $payment->update(['pay_status' => $request->pay_status]);

    //     return redirect()->back()->with('success', 'Payment status updated successfully.');
    // }

    public function updatePaymentStatus(Request $request, Payment $payment)
    {
        $request->validate([
            'pay_status' => 'required|in:Pending,Accepted,Rejected',
        ]);
        $oldStatus = $payment->pay_status;
        $newStatus = $request->pay_status;
        // Only apply deductions/credits if accepting the payment
        if ($newStatus === 'Accepted' && $oldStatus !== 'Accepted') {
            $tenant = $payment->tenant;  // Ensure Payment model has: public function tenant() { return $this->belongsTo(User::class, 'tenant_id'); }
            $lease = $payment->lease; // Get the lease associated with this payment
            $finalAmount = $payment->pay_amount;
            // 🧠 Apply credit BEFORE payment deduction (if applicable)
            $creditUsed = 0;
            if (in_array($payment->payment_for, ['Rent', 'Utilities']) && $tenant->user_credit > 0) {
                if ($payment->payment_for === 'Rent' && $tenant->rent_balance > 0) {
                    $creditUsed = min($tenant->user_credit, $tenant->rent_balance);
                    $tenant->rent_balance -= $creditUsed;
                    // Also deduct from lease if available
                    if ($lease && $lease->rent_balance > 0) {
                        $lease->rent_balance = max(0, $lease->rent_balance - $creditUsed);
                    }
                } elseif ($payment->payment_for === 'Utilities' && $tenant->utility_balance > 0) {
                    $creditUsed = min($tenant->user_credit, $tenant->utility_balance);
                    $tenant->utility_balance -= $creditUsed;
                    // Also deduct from lease if available
                    if ($lease && $lease->utility_balance > 0) {
                        $lease->utility_balance = max(0, $lease->utility_balance - $creditUsed);
                    }
                }
                $tenant->user_credit -= $creditUsed;
                if ($creditUsed > 0) {
                    $this->createNotification($tenant->id, 'Credit Applied', "₱{$creditUsed} of your credits were automatically applied to this {$payment->payment_for} payment.");
                }
            }
            // 🧾 Deduct the remaining payment from the correct balance
            switch ($payment->payment_for) {
                case 'Deposit':
                    $tenant->deposit_amount = max(0, $tenant->deposit_amount - $finalAmount);
                    if ($tenant->deposit_amount <= 0) {
                        $tenant->rent_balance = 0;
                    }
                    // Also update lease deposit if available
                    if ($lease && $lease->deposit_balance > 0) {
                        $lease->deposit_balance = max(0, $lease->deposit_balance - $finalAmount);
                        if ($lease->deposit_balance <= 0 && $lease->rent_balance > 0) {
                            $lease->rent_balance = 0;
                        }
                    }
                    $this->createNotification($tenant->id, 'Deposit Paid', "Your deposit payment of ₱{$finalAmount} has been received. You can now access Maintenance Requests.");
                    break;
                case 'Rent':
                    $tenant->rent_balance = max(0, $tenant->rent_balance - $finalAmount);
                    // Also deduct from lease rent balance if available
                    if ($lease && $lease->rent_balance > 0) {
                        $lease->rent_balance = max(0, $lease->rent_balance - $finalAmount);
                    }
                    $this->createNotification($tenant->id, 'Rent Payment Received', "Your rent payment of ₱{$finalAmount} has been received. Remaining rent balance: ₱{$tenant->rent_balance}.");
                    break;
                case 'Utilities':
                    $tenant->utility_balance = max(0, $tenant->utility_balance - $finalAmount);
                    // Also deduct from lease utility balance if available
                    if ($lease && $lease->utility_balance > 0) {
                        $lease->utility_balance = max(0, $lease->utility_balance - $finalAmount);
                    }
                    $this->createNotification($tenant->id, 'Utility Payment Received', "Your utility payment of ₱{$finalAmount} has been received.");
                    break;
                case 'Other':
                    $tenant->user_credit += $payment->pay_amount;
                    $this->createNotification($tenant->id, 'Credit Added', "Your payment of ₱{$payment->pay_amount} for Advance Payment has been added as credit. You now have ₱{$tenant->user_credit} in credits.");
                    break;
            }
            // Save tenant and lease updates
            $tenant->save();
            if ($lease) {
                $lease->save();
            }
        }
        // Update the payment status
        $payment->update(['pay_status' => $newStatus]);
        return redirect()->back()->with('success', 'Payment status updated successfully.');
    }

    protected function createNotification($userId, $title, $message)
    {
        Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'is_read' => false,  // Matches your model
        ]);
    }

    public function paymentStore(Request $request){
        $request->validate([
            'tenant_id'   => 'required|exists:users,id',
            'lease_id'    => 'required|exists:leases,id',
            'payment_for' => 'required|in:Deposit,Rent,Utilities',
            'pay_amount'  => 'required|numeric|min:1',
            'pay_method'  => 'required|string',
        ]);

        $tenant = Auth::user();

        if($tenant->role === 'manager'){
            $tenant = User::where('id', $request->tenant_id)->first();
        }else{
            $tenant = Auth::user();
        }

        $lease = Lease::where('id', $request->lease_id)
            ->where('user_id', $tenant->id)
            ->first();

        $hasDeposit = $lease->deposit_balance > 0;
        if ($hasDeposit && $request->payment_for !== 'Deposit') {
            return redirect()->back()->with('error', 'You must pay the Deposit first.');
        }

        if ($request->payment_for === 'Deposit' && $request->pay_amount > $lease->deposit_balance) {
            return back()->withErrors(['pay_amount' => 'Payment exceeds deposit balance.']);
        }

        if ($lease->penalty_fee > 0 && $request->pay_amount < ($lease->penalty_fee + $lease->rent_balance)) {
            return redirect()->back()->with('error', "Your payment is overdue! You need to pay the full amount including penalty!");
        }

        // ✅ Optional: Prevent overpayment (example logic)
        if ($request->payment_for === 'Rent' && $request->pay_amount > $lease->unit->room_price) {
            return back()->withErrors(['pay_amount' => 'Payment exceeds rent price.']);
        }

        if ($request->payment_for === 'Utilities' && $request->pay_amount > $lease->utility_balance) {
            return back()->withErrors(['pay_amount' => 'Payment exceeds utility balance.']);
        }

        if (!in_array($lease->lea_status, ['active', 'pending', 'Active', 'Pending'])) {
            return redirect()->back()->with('error', 'You can only make payments for active or pending leases.');
        }

        // ✅ Save Payment
        $payment = Payment::create([
            'tenant_id'   => $request->tenant_id,
            'lease_id'    => $request->lease_id,
            'payment_for' => $request->payment_for,
            'pay_amount'  => $request->pay_amount,
            'pay_method'      => $request->pay_method,
            'pay_status'  => $request->pay_method === 'Cash' ? 'Accepted' : 'Pending',
            'paid_at'     => now(),
        ]);

        // ✅ Update balances automatically
        if ($request->payment_for === 'Rent') {
            $lease->rent_balance -= $request->pay_amount;
        }

        if ($request->payment_for === 'Utilities') {
            $lease->utility_balance -= $request->pay_amount;
        }

        if ($request->payment_for === 'Deposit') {
            $lease->deposit_balance -= $request->pay_amount;
        }

        if($lease->penalty_fee > 0){
            $lease->penalty_fee = 0;
        }

        $lease->paid_date = now();
        $lease->save();

        return redirect()->back()->with('success', 'Payment recorded successfully!');
    }

}
