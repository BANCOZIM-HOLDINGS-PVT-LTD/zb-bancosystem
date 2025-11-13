<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Commission;
use App\Models\ApplicationState;
use Carbon\Carbon;

class CommissionCalculationService
{
    /**
     * Calculate commission for an agent based on an application
     * Note 15: Commissions are a % of the monthly installment based on 12-month computation
     * regardless of actual loan term. 0.3% for online and 3% for field agents.
     */
    public function calculateCommission(ApplicationState $application, Agent $agent): float
    {
        $loanAmount = $this->getApplicationLoanAmount($application);
        
        if ($loanAmount <= 0) {
            return 0;
        }
        
        // Calculate monthly installment based on 12-month term (as per note 15)
        $standardMonthlyInstallment = $loanAmount / 12;
        
        // Get commission rate based on agent type
        $commissionRate = $this->getAgentCommissionRate($agent);
        
        // Calculate commission
        $commission = $standardMonthlyInstallment * ($commissionRate / 100);
        
        return round($commission, 2);
    }
    
    /**
     * Get commission rate based on agent type
     * Note 15: 0.3% for online agents, 3% for field agents
     */
    protected function getAgentCommissionRate(Agent $agent): float
    {
        switch ($agent->type) {
            case 'online':
                return 0.3;
            case 'field':
                return 3.0;
            case 'direct':
                return 3.0; // Assuming direct agents get same as field agents
            default:
                return $agent->commission_rate ?? 3.0;
        }
    }
    
    /**
     * Calculate supervisor commission
     * Note 21: Supervisor earns 10% of each subordinate's commission total
     */
    public function calculateSupervisorCommission(Agent $supervisor, Carbon $periodStart, Carbon $periodEnd): float
    {
        $subordinateCommissions = 0;
        
        // Get all team members where this agent is supervisor
        $teams = $supervisor->teams()
            ->wherePivot('role', 'supervisor')
            ->wherePivot('is_active', true)
            ->get();
        
        foreach ($teams as $team) {
            // Get all team members
            $members = $team->agents()
                ->where('agents.id', '!=', $supervisor->id)
                ->wherePivot('is_active', true)
                ->get();
            
            foreach ($members as $member) {
                // Get member's commissions for the period
                $memberCommissions = Commission::where('agent_id', $member->id)
                    ->whereBetween('earned_date', [$periodStart, $periodEnd])
                    ->where('status', '!=', 'cancelled')
                    ->sum('amount');
                
                $subordinateCommissions += $memberCommissions;
            }
        }
        
        // Supervisor gets 10% of subordinates' total commissions
        return round($subordinateCommissions * 0.10, 2);
    }
    
    /**
     * Calculate hardship allowance
     * Note 22: Field agents get $2 per day, supervisors get $3 per day
     */
    public function calculateHardshipAllowance(Agent $agent, Carbon $periodStart, Carbon $periodEnd): float
    {
        $workingDays = $this->calculateWorkingDays($periodStart, $periodEnd);
        
        // Check if agent is a field agent or supervisor
        if ($agent->type !== 'field') {
            return 0;
        }
        
        // Check if agent is a supervisor
        $isSupervisor = $agent->teams()
            ->wherePivot('role', 'supervisor')
            ->wherePivot('is_active', true)
            ->exists();
        
        $dailyRate = $isSupervisor ? 3.0 : 2.0;
        
        return round($workingDays * $dailyRate, 2);
    }
    
    /**
     * Calculate working days between two dates (excluding weekends)
     */
    protected function calculateWorkingDays(Carbon $start, Carbon $end): int
    {
        $workingDays = 0;
        $current = $start->copy();
        
        while ($current <= $end) {
            if (!$current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }
    
    /**
     * Get loan amount from application
     */
    protected function getApplicationLoanAmount(ApplicationState $application): float
    {
        $formData = $application->form_data ?? [];
        
        // Try different possible field names
        $amount = $formData['loanAmount'] 
            ?? $formData['loan_amount'] 
            ?? $formData['amount'] 
            ?? $formData['creditAmount']
            ?? $formData['credit_amount']
            ?? 0;
        
        return floatval($amount);
    }
    
    /**
     * Create commission record for an application
     */
    public function createCommissionRecord(ApplicationState $application, Agent $agent): Commission
    {
        $amount = $this->calculateCommission($application, $agent);
        
        return Commission::create([
            'agent_id' => $agent->id,
            'application_id' => $application->id,
            'reference_number' => 'COM-' . now()->format('YmdHis') . '-' . $agent->id,
            'type' => 'application',
            'amount' => $amount,
            'rate' => $this->getAgentCommissionRate($agent),
            'base_amount' => $this->getApplicationLoanAmount($application),
            'status' => 'pending',
            'earned_date' => now(),
            'notes' => 'Commission for application: ' . $application->reference_code,
        ]);
    }
    
    /**
     * Create supervisor incentive record
     */
    public function createSupervisorIncentive(Agent $supervisor, Carbon $periodStart, Carbon $periodEnd): ?Commission
    {
        $amount = $this->calculateSupervisorCommission($supervisor, $periodStart, $periodEnd);
        
        if ($amount <= 0) {
            return null;
        }
        
        return Commission::create([
            'agent_id' => $supervisor->id,
            'reference_number' => 'SUP-' . now()->format('YmdHis') . '-' . $supervisor->id,
            'type' => 'bonus',
            'amount' => $amount,
            'rate' => 10.0, // 10% of subordinates' commissions
            'base_amount' => $amount * 10, // Total subordinate commissions
            'status' => 'pending',
            'earned_date' => $periodEnd,
            'notes' => 'Supervisor incentive for period: ' . $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d'),
            'metadata' => [
                'incentive_type' => 'supervisor',
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
            ]
        ]);
    }
    
    /**
     * Create hardship allowance record
     */
    public function createHardshipAllowance(Agent $agent, Carbon $periodStart, Carbon $periodEnd): ?Commission
    {
        $amount = $this->calculateHardshipAllowance($agent, $periodStart, $periodEnd);
        
        if ($amount <= 0) {
            return null;
        }
        
        $workingDays = $this->calculateWorkingDays($periodStart, $periodEnd);
        $isSupervisor = $agent->teams()
            ->wherePivot('role', 'supervisor')
            ->wherePivot('is_active', true)
            ->exists();
        
        $dailyRate = $isSupervisor ? 3.0 : 2.0;
        
        return Commission::create([
            'agent_id' => $agent->id,
            'reference_number' => 'HARD-' . now()->format('YmdHis') . '-' . $agent->id,
            'type' => 'bonus',
            'amount' => $amount,
            'rate' => $dailyRate,
            'base_amount' => $workingDays,
            'status' => 'pending',
            'earned_date' => $periodEnd,
            'notes' => 'Hardship allowance for ' . $workingDays . ' working days at $' . $dailyRate . '/day',
            'metadata' => [
                'allowance_type' => 'hardship',
                'working_days' => $workingDays,
                'daily_rate' => $dailyRate,
                'is_supervisor' => $isSupervisor,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
            ]
        ]);
    }
    
    /**
     * Process all commissions for a period
     */
    public function processMonthlyCommissions(Carbon $month = null): array
    {
        $month = $month ?? now()->startOfMonth();
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();
        
        $results = [
            'application_commissions' => 0,
            'supervisor_incentives' => 0,
            'hardship_allowances' => 0,
            'total' => 0,
            'agents_processed' => 0,
        ];
        
        // Get all active agents
        $agents = Agent::active()->get();
        
        foreach ($agents as $agent) {
            // Process supervisor incentive
            if ($agent->teams()->wherePivot('role', 'supervisor')->exists()) {
                $incentive = $this->createSupervisorIncentive($agent, $periodStart, $periodEnd);
                if ($incentive) {
                    $results['supervisor_incentives'] += $incentive->amount;
                }
            }
            
            // Process hardship allowance for field agents
            if ($agent->type === 'field') {
                $allowance = $this->createHardshipAllowance($agent, $periodStart, $periodEnd);
                if ($allowance) {
                    $results['hardship_allowances'] += $allowance->amount;
                }
            }
            
            $results['agents_processed']++;
        }
        
        // Get application commissions for the period
        $applicationCommissions = Commission::where('type', 'application')
            ->whereBetween('earned_date', [$periodStart, $periodEnd])
            ->sum('amount');
        
        $results['application_commissions'] = $applicationCommissions;
        $results['total'] = $results['application_commissions'] + 
                           $results['supervisor_incentives'] + 
                           $results['hardship_allowances'];
        
        return $results;
    }
}