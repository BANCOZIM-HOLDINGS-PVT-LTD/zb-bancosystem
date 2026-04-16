<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanTerm extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'description',
        'duration_months',
        'interest_rate',
        'interest_type',
        'calculation_method',
        'payment_frequency',
        'minimum_amount',
        'maximum_amount',
        'processing_fee',
        'processing_fee_type',
        'insurance_rate',
        'insurance_required',
        'early_payment_penalty',
        'late_payment_penalty',
        'grace_period_days',
        'custom_formula',
        'conditions',
        'is_active',
        'is_default',
        'effective_date',
        'expiry_date',
        'metadata',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'interest_rate' => 'decimal:4',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'insurance_rate' => 'decimal:4',
        'insurance_required' => 'boolean',
        'early_payment_penalty' => 'decimal:4',
        'late_payment_penalty' => 'decimal:4',
        'grace_period_days' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'effective_date' => 'datetime',
        'expiry_date' => 'datetime',
        'conditions' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Interest types
     */
    const INTEREST_SIMPLE = 'simple';
    const INTEREST_COMPOUND = 'compound';
    const INTEREST_FLAT = 'flat';
    const INTEREST_REDUCING = 'reducing';
    const INTEREST_CUSTOM = 'custom';

    /**
     * Calculation methods
     */
    const CALC_STANDARD = 'standard';
    const CALC_CUSTOM_FORMULA = 'custom_formula';
    const CALC_TIERED = 'tiered';
    const CALC_PERCENTAGE_OF_INCOME = 'percentage_of_income';

    /**
     * Payment frequencies
     */
    const FREQ_WEEKLY = 'weekly';
    const FREQ_BIWEEKLY = 'biweekly';
    const FREQ_MONTHLY = 'monthly';
    const FREQ_QUARTERLY = 'quarterly';
    const FREQ_ANNUALLY = 'annually';

    /**
     * Processing fee types
     */
    const FEE_FIXED = 'fixed';
    const FEE_PERCENTAGE = 'percentage';

    /**
     * Get the product this loan term belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get payment schedules for this loan term
     *
     * NOTE: Commented out - LoanPaymentSchedule model does not exist.
     * Payment schedules are generated dynamically via generatePaymentSchedule() method.
     */
    // public function paymentSchedules(): HasMany
    // {
    //     return $this->hasMany(LoanPaymentSchedule::class);
    // }

    /**
     * Calculate monthly payment strictly using the amortization formula on Gross Loan.
     */
    public function calculateMonthlyPayment(float $loanAmount, ?int $durationMonths = null): float
    {
        $costData = $this->calculateTotalCost($loanAmount, $durationMonths);
        return $costData['monthly_payment'];
    }

    /**
     * Calculate total loan cost using the unified standard formula:
     * Gross Loan = Net Loan + (Net Loan * Admin Fee %)
     * Monthly Payment = Amortized payment on Gross Loan
     */
    public function calculateTotalCost(float $loanAmount, ?int $durationMonths = null): array
    {
        $duration = $durationMonths ?? $this->duration_months;
        
        // Processing Fee acts as the Bank Admin Fee
        $processingFeeRate = $this->processing_fee;
        if ($this->processing_fee_type === self::FEE_FIXED) {
            $adminFee = $this->processing_fee;
        } else {
            $adminFee = round(($loanAmount * $processingFeeRate) / 100, 2);
        }

        // Gross Loan
        $grossLoan = round($loanAmount + $adminFee, 2);

        // Amortization (interest_rate is ANNUAL percentage)
        $annualInterestRate = $this->interest_rate;
        $monthlyInterestRate = $annualInterestRate / 100 / 12;

        if ($monthlyInterestRate <= 0 || $duration <= 0) {
            $monthlyPayment = $duration > 0 ? round($grossLoan / $duration, 2) : $grossLoan;
        } else {
            $monthlyPayment = round(($grossLoan * $monthlyInterestRate * pow(1 + $monthlyInterestRate, $duration)) / 
                              (pow(1 + $monthlyInterestRate, $duration) - 1), 2);
        }

        $totalPayments = round($monthlyPayment * $duration, 2);
        $totalInterest = round($totalPayments - $grossLoan, 2);

        return [
            'net_loan' => $loanAmount,
            'admin_fee' => round($adminFee, 2),
            'gross_loan' => round($grossLoan, 2),
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payments' => round($totalPayments, 2),
            'total_interest' => round($totalInterest, 2),
            'duration_months' => $duration,
            'annual_interest_rate' => $annualInterestRate,
        ];
    }

    /**
     * Generate payment schedule
     */
    public function generatePaymentSchedule(float $loanAmount, \DateTime $startDate = null): array
    {
        $startDate = $startDate ?: new \DateTime();
        $monthlyPayment = $this->calculateMonthlyPayment($loanAmount);
        $balance = $loanAmount;
        $schedule = [];

        for ($i = 1; $i <= $this->duration_months; $i++) {
            $paymentDate = clone $startDate;
            $paymentDate->modify("+{$i} months");

            $interestPayment = $balance * ($this->interest_rate / 100 / 12);
            $principalPayment = $monthlyPayment - $interestPayment;
            $balance -= $principalPayment;

            $schedule[] = [
                'payment_number' => $i,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'payment_amount' => round($monthlyPayment, 2),
                'principal_amount' => round($principalPayment, 2),
                'interest_amount' => round($interestPayment, 2),
                'remaining_balance' => round(max(0, $balance), 2),
            ];
        }

        return $schedule;
    }

    /**
     * Check if loan term is currently active
     */
    public function getIsActiveNowAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        if ($this->effective_date && $now < $this->effective_date) {
            return false;
        }

        if ($this->expiry_date && $now > $this->expiry_date) {
            return false;
        }

        return true;
    }

    /**
     * Get payment frequency label
     */
    public function getPaymentFrequencyLabelAttribute(): string
    {
        return match ($this->payment_frequency) {
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_BIWEEKLY => 'Bi-weekly',
            self::FREQ_MONTHLY => 'Monthly',
            self::FREQ_QUARTERLY => 'Quarterly',
            self::FREQ_ANNUALLY => 'Annually',
            default => ucfirst($this->payment_frequency),
        };
    }

    /**
     * Scope for active loan terms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope for default loan terms
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
