<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Commission extends Model
{
    protected $fillable = [
        'agent_id',
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
     * Get the agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
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
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
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
    public static function createForApplication(ApplicationState $application, Agent $agent): self
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $loanAmount = floatval($formResponses['loanAmount'] ?? 0);

        $commissionAmount = self::calculateCommission($loanAmount, $agent->commission_rate);

        return self::create([
            'agent_id' => $agent->id,
            'application_id' => $application->id,
            'type' => 'application',
            'amount' => $commissionAmount,
            'rate' => $agent->commission_rate,
            'base_amount' => $loanAmount,
            'status' => 'pending',
            'earned_date' => now(),
            'metadata' => [
                'application_session_id' => $application->session_id,
                'loan_amount' => $loanAmount,
                'calculated_at' => now()->toISOString(),
            ],
        ]);
    }
}
