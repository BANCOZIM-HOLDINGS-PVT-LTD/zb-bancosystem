<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Commission extends Model
{
    protected $fillable = [
        'agent_id',
        'agent_type',
        'application_id',
        'reference_number',
        'type',
        'amount',
        'rate',
        'base_amount',
        'status',
        'earned_date',
        'paid_date',
        'payment_method',
        'payment_reference',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'earned_date' => 'date',
        'paid_date' => 'date',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commission) {
            if (empty($commission->reference_number)) {
                $commission->reference_number = 'COM' . date('Ymd') . strtoupper(Str::random(6));
            }
        });
    }

    /**
     * Get the parent agent model (Agent or AgentApplication).
     */
    public function agent(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('agent', 'agent_type', 'agent_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class, 'application_id');
    }


    /**
     * Check if commission is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Scope for pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for specific agent
     */
    public function scopeForAgent($query, int $agentId, string $agentType = 'agents')
    {
        return $query->where('agent_id', $agentId)->where('agent_type', $agentType);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('earned_date', [$startDate, $endDate]);
    }

    /**
     * Calculate commission amount
     */
    public static function calculateCommission(float $baseAmount, float $rate): float
    {
        return round($baseAmount * ($rate / 100), 2);
    }

    /**
     * Create commission for application
     */
    public static function createForApplication(ApplicationState $application, $agent): self
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? ($formData ?? []);
        $loanAmount = floatval($formResponses['loanAmount'] ?? ($formResponses['loan_amount'] ?? 0));

        // Tier logic: 1% for ordinary, 1.5% for higher_achiever
        $tier = $agent->tier ?? 'ordinary';
        $rate = $tier === 'higher_achiever' ? 1.5 : 1.0;
        
        $commissionAmount = self::calculateCommission($loanAmount, $rate);

        $agentType = ($agent instanceof Agent) ? 'agents' : 'agent_applications';

        return self::create([
            'agent_id' => $agent->id,
            'agent_type' => $agentType,
            'application_id' => $application->id,
            'type' => 'application',
            'amount' => $commissionAmount,
            'rate' => $rate,
            'base_amount' => $loanAmount,
            'status' => 'pending',
            'earned_date' => now(),
            'metadata' => [
                'application_session_id' => $application->session_id,
                'loan_amount' => $loanAmount,
                'calculated_at' => now()->toISOString(),
                'agent_tier' => $tier,
            ],
        ]);
    }
}
