<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'recipient_type',
        'recipient_name',
        'base_salary',
        'commission',
        'allowances',
        'deductions',
        'net_pay',
        'pay_period_start',
        'pay_period_end',
        'status',
        'payment_reference',
        'notes',
        'processed_by',
        'paid_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'commission' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the user (employee/intern) for this payroll entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the agent for this payroll entry.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the user who processed this entry.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for pending entries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processed entries.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for paid entries.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for employees.
     */
    public function scopeEmployees($query)
    {
        return $query->whereIn('recipient_type', ['employee', 'intern']);
    }

    /**
     * Scope for agents.
     */
    public function scopeAgents($query)
    {
        return $query->whereIn('recipient_type', ['agent_online', 'agent_physical']);
    }

    /**
     * Scope for online agents.
     */
    public function scopeOnlineAgents($query)
    {
        return $query->where('recipient_type', 'agent_online');
    }

    /**
     * Scope for physical agents.
     */
    public function scopePhysicalAgents($query)
    {
        return $query->where('recipient_type', 'agent_physical');
    }

    /**
     * Calculate gross pay (before deductions).
     */
    public function getGrossPayAttribute(): float
    {
        return $this->base_salary + $this->commission + $this->allowances;
    }

    /**
     * Mark as processed.
     */
    public function markAsProcessed(?int $processedBy = null): void
    {
        $this->update([
            'status' => 'processed',
            'processed_by' => $processedBy ?? auth()->id(),
        ]);
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(?string $paymentReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
        ]);
    }
}
