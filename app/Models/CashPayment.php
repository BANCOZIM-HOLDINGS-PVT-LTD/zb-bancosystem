<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CashPayment extends Model
{
    protected $fillable = [
        'payment_id',
        'application_state_id',
        'cashier_reference',
        'received_amount',
        'receipt_number',
        'verified_by',
        'verified_at',
        'rejected_at',
        'notes',
    ];

    protected $casts = [
        'received_amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CashPayment $cashPayment) {
            $cashPayment->cashier_reference ??= 'CASH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
