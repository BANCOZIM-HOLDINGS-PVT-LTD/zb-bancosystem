<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPerformance extends Model
{
    protected $fillable = [
        'agent_id',
        'period_start',
        'period_end',
        'applications_submitted',
        'applications_approved',
        'applications_rejected',
        'total_loan_amount',
        'commission_earned',
        'conversion_rate',
        'metrics',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_loan_amount' => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'metrics' => 'array',
    ];

    /**
     * Get the agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get formatted period
     */
    public function getPeriodAttribute(): string
    {
        return $this->period_start->format('M Y');
    }

    /**
     * Calculate and update performance metrics
     */
    public function calculateMetrics(): void
    {
        $applications = $this->agent->applications()
            ->whereBetween('created_at', [$this->period_start, $this->period_end])
            ->get();

        $approved = $applications->filter(function ($app) {
            $metadata = $app->metadata ?? [];
            return ($metadata['admin_status'] ?? 'pending') === 'approved';
        });

        $rejected = $applications->filter(function ($app) {
            $metadata = $app->metadata ?? [];
            return ($metadata['admin_status'] ?? 'pending') === 'rejected';
        });

        $totalLoanAmount = $applications->sum(function ($app) {
            $formData = $app->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            return floatval($formResponses['loanAmount'] ?? 0);
        });

        $commissionEarned = $this->agent->commissions()
            ->whereBetween('earned_date', [$this->period_start, $this->period_end])
            ->sum('amount');

        $conversionRate = $applications->count() > 0 
            ? round(($approved->count() / $applications->count()) * 100, 2) 
            : 0;

        $this->update([
            'applications_submitted' => $applications->count(),
            'applications_approved' => $approved->count(),
            'applications_rejected' => $rejected->count(),
            'total_loan_amount' => $totalLoanAmount,
            'commission_earned' => $commissionEarned,
            'conversion_rate' => $conversionRate,
            'metrics' => [
                'avg_loan_amount' => $applications->count() > 0 ? $totalLoanAmount / $applications->count() : 0,
                'channels_used' => $applications->pluck('channel')->unique()->values()->toArray(),
                'peak_submission_day' => $this->getPeakSubmissionDay($applications),
                'calculated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get peak submission day
     */
    private function getPeakSubmissionDay($applications): ?string
    {
        if ($applications->isEmpty()) {
            return null;
        }

        $dayCount = $applications->groupBy(function ($app) {
            return $app->created_at->format('l'); // Day name
        })->map->count();

        return $dayCount->keys()->first();
    }

    /**
     * Create or update performance for agent and period
     */
    public static function updateForAgent(Agent $agent, $periodStart, $periodEnd): self
    {
        $performance = self::firstOrCreate([
            'agent_id' => $agent->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $performance->calculateMetrics();

        return $performance;
    }
}
