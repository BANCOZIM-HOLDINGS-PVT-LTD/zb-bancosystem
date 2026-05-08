<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTransaction extends Model
{
    protected $fillable = [
        'type',
        'source',
        'amount',
        'currency',
        'reference',
        'application_state_id',
        'payment_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
