<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityBillingProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'lease_id',
        'file_path',
        'billing_month',
        'billing_type',
        'amount',
        'notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}

