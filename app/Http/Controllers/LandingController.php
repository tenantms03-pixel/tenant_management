<?php
// app/Http/Controllers/LandingController.php
namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\TenantApplication;

class LandingController extends Controller
{
    public function index()
    {
        // Fetch all units
        $units = Unit::all();

        // Count vacant rooms
        $vacantCount = $units->where('status', 'vacant')->count();

        // Get available units (vacant or Bed-Spacer with available beds)
        $availableUnits = Unit::with(['leases' => fn($query) => $query->whereIn('lea_status', ['active', 'pending'])])
            ->where(function ($query) {
                $query->where('status', 'vacant')
                    ->orWhere(function ($bedQuery) {
                        $bedQuery->where('type', 'Bed-Spacer')
                            ->whereColumn('no_of_occupants', '<', 'capacity');
                    });
            })
            ->get();

        // Get pending bed reservations
        $pendingBedReservations = TenantApplication::select('unit_id', 'bed_number')
            ->whereNotNull('bed_number')
            ->whereHas('user', function ($query) {
                $query->whereIn('status', ['pending', 'approved']);
            })
            ->get()
            ->groupBy('unit_id')
            ->map(fn($group) => $group->pluck('bed_number')->filter()->unique());

        // Add taken_beds attribute to each unit
        $availableUnits->each(function ($unit) use ($pendingBedReservations) {
            $leaseBeds = $unit->leases->pluck('bed_number')->filter();
            $pendingBeds = $pendingBedReservations->get($unit->id, collect());
            $unit->setAttribute(
                'taken_beds',
                $leaseBeds->merge($pendingBeds)->filter()->unique()->values()
            );
        });

        // Pass everything to the view
        return view('landing', compact('units', 'vacantCount', 'availableUnits'));
    }
}
