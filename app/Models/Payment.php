<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'status',
        'description',
        'transaction_id',
        'authorization_code',
        'processor_reference',
        'threeds_version',
        'threeds_eci',
        'threeds_cavv',
        'threeds_xid',
        'threeds_authentication_status',
        'liability_shift',
        'flow_type',
        'enrollment_data',
        'card_last_four',
        'card_type',
        'metadata',
        'error_message',
        'error_reason',
        'risk_score',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'liability_shift' => 'boolean',
        'enrollment_data' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
