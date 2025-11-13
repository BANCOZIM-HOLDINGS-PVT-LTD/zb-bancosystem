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
     */
    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(LoanPaymentSchedule::class);
    }

    /**
     * Calculate monthly payment amount
     */
    public function calculateMonthlyPayment(float $loanAmount): float
    {
        switch ($this->calculation_method) {
            case self::CALC_CUSTOM_FORMULA:
                return $this->calculateCustomFormula($loanAmount);
            case self::CALC_TIERED:
                return $this->calculateTieredPayment($loanAmount);
            case self::CALC_PERCENTAGE_OF_INCOME:
                return $this->calculateIncomeBasedPayment($loanAmount);
            default:
                return $this->calculateStandardPayment($loanAmount);
        }
    }

    /**
     * Calculate standard payment using interest rate and duration
     */
    private function calculateStandardPayment(float $loanAmount): float
    {
        if ($this->interest_rate <= 0 || $this->duration_months <= 0) {
            return $loanAmount / $this->duration_months;
        }

        $monthlyRate = $this->interest_rate / 100 / 12;
        $numPayments = $this->duration_months;

        switch ($this->interest_type) {
            case self::INTEREST_SIMPLE:
                $totalInterest = $loanAmount * ($this->interest_rate / 100) * ($this->duration_months / 12);
                return ($loanAmount + $totalInterest) / $this->duration_months;

            case self::INTEREST_FLAT:
                $monthlyInterest = $loanAmount * ($this->interest_rate / 100) / 12;
                return ($loanAmount / $this->duration_months) + $monthlyInterest;

            case self::INTEREST_REDUCING:
            case self::INTEREST_COMPOUND:
                // Standard amortization formula
                if ($monthlyRate == 0) {
                    return $loanAmount / $numPayments;
                }
                return $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) / 
                       (pow(1 + $monthlyRate, $numPayments) - 1);

            default:
                return $loanAmount / $this->duration_months;
        }
    }

    /**
     * Calculate payment using custom formula
     */
    private function calculateCustomFormula(float $loanAmount): float
    {
        if (empty($this->custom_formula)) {
            return $this->calculateStandardPayment($loanAmount);
        }

        // Parse and evaluate custom formula
        // Variables available: amount, rate, months, processing_fee
        $formula = $this->custom_formula;
        $variables = [
            'amount' => $loanAmount,
            'rate' => $this->interest_rate,
            'months' => $this->duration_months,
            'processing_fee' => $this->processing_fee,
        ];

        // Simple variable replacement (in production, use a proper expression evaluator)
        foreach ($variables as $var => $value) {
            $formula = str_replace('{' . $var . '}', $value, $formula);
        }

        // For safety, only allow basic math operations
        if (preg_match('/^[\d\+\-\*\/\(\)\.\s]+$/', $formula)) {
            try {
                return eval("return $formula;");
            } catch (Exception $e) {
                return $this->calculateStandardPayment($loanAmount);
            }
        }

        return $this->calculateStandardPayment($loanAmount);
    }

    /**
     * Calculate tiered payment based on loan amount ranges
     */
    private function calculateTieredPayment(float $loanAmount): float
    {
        $tiers = $this->metadata['tiers'] ?? [];
        
        foreach ($tiers as $tier) {
            if ($loanAmount >= $tier['min_amount'] && $loanAmount <= $tier['max_amount']) {
                $tierRate = $tier['interest_rate'] ?? $this->interest_rate;
                $tempTerm = clone $this;
                $tempTerm->interest_rate = $tierRate;
                return $tempTerm->calculateStandardPayment($loanAmount);
            }
        }

        return $this->calculateStandardPayment($loanAmount);
    }

    /**
     * Calculate income-based payment
     */
    private function calculateIncomeBasedPayment(float $loanAmount): float
    {
        $incomePercentage = $this->metadata['income_percentage'] ?? 30;
        $estimatedIncome = $this->metadata['estimated_monthly_income'] ?? 1000;
        
        return ($estimatedIncome * $incomePercentage) / 100;
    }

    /**
     * Calculate total loan cost
     */
    public function calculateTotalCost(float $loanAmount): array
    {
        $monthlyPayment = $this->calculateMonthlyPayment($loanAmount);
        $totalPayments = $monthlyPayment * $this->duration_months;
        
        $processingFee = $this->processing_fee_type === self::FEE_PERCENTAGE 
            ? ($loanAmount * $this->processing_fee / 100)
            : $this->processing_fee;
        
        $insuranceCost = $this->insurance_required 
            ? ($loanAmount * $this->insurance_rate / 100)
            : 0;

        $totalCost = $totalPayments + $processingFee + $insuranceCost;
        $totalInterest = $totalCost - $loanAmount - $processingFee - $insuranceCost;

        return [
            'loan_amount' => $loanAmount,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payments' => round($totalPayments, 2),
            'processing_fee' => round($processingFee, 2),
            'insurance_cost' => round($insuranceCost, 2),
            'total_interest' => round($totalInterest, 2),
            'total_cost' => round($totalCost, 2),
            'duration_months' => $this->duration_months,
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
