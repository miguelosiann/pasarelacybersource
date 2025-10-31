<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInstrument extends Model
{
    protected $fillable = [
        'user_id',
        'instrument_identifier_id',
        'payment_instrument_id',
        'type',
        'card_type',
        'card_last_four',
        'expiration_month',
        'expiration_year',
        'cardholder_name',
        'state',
        'is_default',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payment instrument
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
