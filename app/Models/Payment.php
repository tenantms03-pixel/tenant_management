<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'tenant_id',
        'lease_id',
        'pay_date',
        'payment_for',    // Rent, Utilities, Deposit, Other
        'pay_amount',
        'pay_method',     // Cash, GCash, Bank Transfer
        'account_no',
        'reference_number',
        'pay_status',     // Paid, Pending, Overdue
        'proof',
    ];

    protected $casts = [
        'pay_date' => 'datetime',
        'pay_amount' => 'decimal:2',
    ];

    // 🔗 Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class, 'lease_id');
    }

    // Alias for clarity
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
