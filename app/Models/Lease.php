<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lease extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'user_id',
    //     'unit_id',
    //     'lea_start_date',
    //     'lea_end_date',
    //     'lea_status',
    //     'room_no',
    //     'lea_terms',
    // ];

    protected $fillable = [
        'user_id',
        'unit_id',
        'lea_start_date',
        'lea_end_date',
        'lea_status',
        'room_no',
        'bed_number',
        'lea_terms',
        'rent_balance',
        'utility_balance',
        'deposit_amount',
        'rental_payment_status',
        'utility_payment_status',
        'move_out_requested',
    ];


    // Make sure date columns are cast to Carbon
    protected $casts = [
        'lea_start_date' => 'date',
        'lea_end_date' => 'date',
    ];

    // Lease belongs to a tenant
    public function tenant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Lease belongs to a unit
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // Lease can have many payments
    public function payments()
    {
        return $this->hasMany(Payment::class, 'lease_id');
    }

    // Lease can have many maintenance requests
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id', 'user_id');
    }
}