<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepaymentTerm extends Model
{
    protected $fillable = [
        'months',
        'interest_rate',
    ];

    protected $casts = [
        'interest_rate' => 'decimal:2',
    ];

    /**
     * Get formatted term display
     */
    public function getFormattedTermAttribute(): string
    {
        return $this->months.' months';
    }

    /**
     * Get formatted interest rate
     */
    public function getFormattedInterestRateAttribute(): string
    {
        return $this->interest_rate.'%';
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->formatted_term.' at '.$this->formatted_interest_rate;
    }

    /**
     * Calculate monthly payment for a given principal amount
     */
    public function calculateMonthlyPayment(float $principal): float
    {
        $monthlyRate = $this->interest_rate / 100 / 12;
        $numPayments = $this->months;

        if ($monthlyRate == 0) {
            return $principal / $numPayments;
        }

        return $principal * ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) /
               (pow(1 + $monthlyRate, $numPayments) - 1);
    }

    /**
     * Calculate total amount to be paid
     */
    public function calculateTotalAmount(float $principal): float
    {
        return $this->calculateMonthlyPayment($principal) * $this->months;
    }

    /**
     * Calculate total interest
     */
    public function calculateTotalInterest(float $principal): float
    {
        return $this->calculateTotalAmount($principal) - $principal;
    }

    /**
     * Scope for common terms
     */
    public function scopeCommon($query)
    {
        return $query->whereIn('months', [6, 12, 18, 24, 36]);
    }

    /**
     * Scope for short term (less than 12 months)
     */
    public function scopeShortTerm($query)
    {
        return $query->where('months', '<', 12);
    }

    /**
     * Scope for long term (12 months or more)
     */
    public function scopeLongTerm($query)
    {
        return $query->where('months', '>=', 12);
    }
}
