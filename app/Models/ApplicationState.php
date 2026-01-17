<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationState extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'session_id',
        'channel',
        'user_identifier',
        'current_step',
        'form_data',
        'metadata',
        'expires_at',
        'reference_code',
        'reference_code_expires_at',
        'agent_id',
        'exempt_from_auto_deletion',
        'deposit_amount',
        'deposit_paid',
        'deposit_paid_at',
        'deposit_transaction_id',
        'deposit_payment_method',
    ];

    protected $casts = [
        'form_data' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'reference_code_expires_at' => 'datetime',
        'exempt_from_auto_deletion' => 'boolean',
        'deposit_paid' => 'boolean',
        'deposit_paid_at' => 'datetime',
    ];

    public function transitions(): HasMany
    {
        return $this->hasMany(StateTransition::class, 'state_id');
    }

    /**
     * Get the agent who referred this application
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the delivery tracking for this application
     */
    public function delivery(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DeliveryTracking::class);
    }

    /**
     * Get commissions for this application
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'application_id');
    }
}
