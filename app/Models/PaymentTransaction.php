<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id',
        'type',
        'status',
        'transaction_id',
        'reference_id',
        'amount',
        'currency',
        'request_payload',
        'response_payload',
        'error_message',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the payment that owns the transaction
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
