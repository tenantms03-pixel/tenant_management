<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        // Fetch the latest lease (with unit details)
        $lease = $user->leases()
                      ->with(['tenant', 'unit'])
                      ->latest()
                      ->first();

        // Fetch the tenant's application (to get unit_type)
        $application = $user->tenantApplication()->latest()->first();

        // Fetch tenant payments
        $payments = Payment::where('tenant_id', $user->id)
                            ->orderBy('pay_date', 'desc')
                            ->get();

        // Fetch tenant maintenance requests
        $requests = MaintenanceRequest::where('tenant_id', $user->id)
                                      ->orderBy('created_at', 'desc')
                                      ->get();

        return view('tenant.home', compact('lease', 'application', 'payments', 'requests'));
    }

    public function payments()
    {
        $tenant = Auth::user();

        $payments = Payment::where('tenant_id', $tenant->id)
                            ->orderBy('pay_date', 'desc')
                            ->get();

        // Exclude deposit records since they’re one-time
        $payments = $payments->filter(function ($payment) {
            return strtolower($payment->type) !== 'deposit';
        });

        return view('tenant.payments', compact('payments'));
    }

    public function requests()
    {
        $tenant = Auth::user();

        $requests = MaintenanceRequest::where('tenant_id', $tenant->id)
                                      ->orderBy('created_at', 'desc')
                                      ->get();

        return view('tenant.request', compact('requests'));
    }
}
