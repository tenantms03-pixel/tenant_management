<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

   protected $fillable = [
        'tenant_id',
        'lease_id',
        'unit_type',
        'room_no', // still included, optional
        'description',
        'urgency',
        'supposed_date',
        'status',
        'issue_image'
    ];


    // Link to tenant (User)
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    // Link to lease
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    // Link to unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Link to property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
