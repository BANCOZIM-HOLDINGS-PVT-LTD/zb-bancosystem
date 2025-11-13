<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Product;
use App\Models\AgentReferralLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReportingService
{
    /**
     * Generate comprehensive application report
     */
    public function generateApplicationReport(Carbon $startDate, Carbon $endDate): array
    {
        $applications = ApplicationState::whereBetween('created_at', [$startDate, $endDate])->get();
        
        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_applications' => $applications->count(),
                'completed_applications' => $applications->where('current_step', 'completed')->count(),
                'approved_applications' => $applications->where('current_step', 'approved')->count(),
                'rejected_applications' => $applications->where('current_step', 'rejected')->count(),
                'pending_applications' => $applications->where('current_step', 'in_review')->count(),
            ],
            'by_status' => $this->getApplicationsByStatus($applications),
            'by_product' => $this->getApplicationsByProduct($applications),
            'by_channel' => $this->getApplicationsByChannel($applications),
            'by_agent' => $this->getApplicationsByAgent($applications),
            'daily_breakdown' => $this->getDailyApplicationBreakdown($startDate, $endDate),
            'conversion_funnel' => $this->getConversionFunnel($applications),
        ];
    }

    /**
     * Generate agent performance report
     */
    public function generateAgentPerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $agents = Agent::with(['referralLinks', 'commissions', 'applications'])->get();
        $agentPerformance = [];

        foreach ($agents as $agent) {
            $applications = $agent->applications()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $commissions = $agent->commissions()
                ->whereBetween('earned_date', [$startDate, $endDate])
                ->get();

            $agentPerformance[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->full_name,
                'team' => $agent->team?->name,
                'applications_referred' => $applications->count(),
                'applications_approved' => $applications->where('current_step', 'approved')->count(),
                'total_commission_earned' => $commissions->where('status', '!=', 'cancelled')->sum('amount'),
                'pending_commission' => $commissions->where('status', 'pending')->sum('amount'),
                'paid_commission' => $commissions->where('status', 'paid')->sum('amount'),
                'conversion_rate' => $this->calculateAgentConversionRate($agent, $startDate, $endDate),
                'average_deal_size' => $this->calculateAverageAgentDealSize($agent, $startDate, $endDate),
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_agents' => $agents->count(),
                'active_agents' => $agents->where('status', 'active')->count(),
                'total_commission_earned' => collect($agentPerformance)->sum('total_commission_earned'),
                'total_applications_referred' => collect($agentPerformance)->sum('applications_referred'),
            ],
            'agent_performance' => $agentPerformance,
            'top_performers' => $this->getTopPerformingAgents($agentPerformance),
            'team_performance' => $this->getTeamPerformance($agents, $startDate, $endDate),
        ];
    }

    /**
     * Generate product performance report
     */
    public function generateProductPerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $applications = ApplicationState::whereBetween('created_at', [$startDate, $endDate])->get();
        $productPerformance = [];

        $products = Product::all();
        foreach ($products as $product) {
            $productApplications = $applications->filter(function ($app) use ($product) {
                return isset($app->form_data['selectedProduct']) && 
                       $app->form_data['selectedProduct'] == $product->id;
            });

            $productPerformance[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category,
                'subcategory' => $product->subcategory,
                'applications_count' => $productApplications->count(),
                'approved_applications' => $productApplications->where('current_step', 'approved')->count(),
                'total_value' => $this->calculateProductTotalValue($productApplications),
                'average_loan_amount' => $this->calculateProductAverageLoanAmount($productApplications),
                'approval_rate' => $this->calculateProductApprovalRate($productApplications),
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_products' => $products->count(),
                'products_with_applications' => collect($productPerformance)->where('applications_count', '>', 0)->count(),
                'total_application_value' => collect($productPerformance)->sum('total_value'),
            ],
            'product_performance' => $productPerformance,
            'top_products' => $this->getTopPerformingProducts($productPerformance),
            'category_breakdown' => $this->getProductCategoryBreakdown($productPerformance),
        ];
    }

    /**
     * Generate commission report
     */
    public function generateCommissionReport(Carbon $startDate, Carbon $endDate): array
    {
        $commissions = Commission::with(['agent', 'application'])
            ->whereBetween('earned_date', [$startDate, $endDate])
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_commissions' => $commissions->count(),
                'total_amount' => $commissions->sum('amount'),
                'pending_amount' => $commissions->where('status', 'pending')->sum('amount'),
                'paid_amount' => $commissions->where('status', 'paid')->sum('amount'),
                'cancelled_amount' => $commissions->where('status', 'cancelled')->sum('amount'),
            ],
            'by_status' => $this->getCommissionsByStatus($commissions),
            'by_agent' => $this->getCommissionsByAgent($commissions),
            'by_team' => $this->getCommissionsByTeam($commissions),
            'monthly_breakdown' => $this->getMonthlyCommissionBreakdown($startDate, $endDate),
            'payment_schedule' => $this->getCommissionPaymentSchedule($commissions),
        ];
    }

    /**
     * Generate referral link performance report
     */
    public function generateReferralLinkReport(Carbon $startDate, Carbon $endDate): array
    {
        $links = AgentReferralLink::with(['agent', 'clicks', 'applications'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $linkPerformance = [];
        foreach ($links as $link) {
            $clicks = $link->clicks()->whereBetween('created_at', [$startDate, $endDate])->count();
            $applications = $link->applications()->whereBetween('created_at', [$startDate, $endDate])->count();
            $conversions = $link->applications()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('current_step', 'approved')
                ->count();

            $linkPerformance[] = [
                'link_id' => $link->id,
                'agent_name' => $link->agent->full_name,
                'campaign_name' => $link->campaign_name,
                'code' => $link->code,
                'clicks' => $clicks,
                'applications' => $applications,
                'conversions' => $conversions,
                'click_to_application_rate' => $clicks > 0 ? round(($applications / $clicks) * 100, 2) : 0,
                'application_to_conversion_rate' => $applications > 0 ? round(($conversions / $applications) * 100, 2) : 0,
                'overall_conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_links' => $links->count(),
                'active_links' => $links->where('is_active', true)->count(),
                'total_clicks' => collect($linkPerformance)->sum('clicks'),
                'total_applications' => collect($linkPerformance)->sum('applications'),
                'total_conversions' => collect($linkPerformance)->sum('conversions'),
            ],
            'link_performance' => $linkPerformance,
            'top_performing_links' => $this->getTopPerformingLinks($linkPerformance),
            'campaign_breakdown' => $this->getCampaignBreakdown($linkPerformance),
        ];
    }

    /**
     * Export report data to CSV format
     */
    public function exportToCSV(array $reportData, string $reportType): string
    {
        $filename = storage_path("app/reports/{$reportType}_" . now()->format('Y-m-d_H-i-s') . '.csv');
        
        // Ensure reports directory exists
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        $file = fopen($filename, 'w');
        
        // Write headers and data based on report type
        switch ($reportType) {
            case 'applications':
                $this->writeApplicationCSV($file, $reportData);
                break;
            case 'agents':
                $this->writeAgentCSV($file, $reportData);
                break;
            case 'products':
                $this->writeProductCSV($file, $reportData);
                break;
            case 'commissions':
                $this->writeCommissionCSV($file, $reportData);
                break;
            case 'referral_links':
                $this->writeReferralLinkCSV($file, $reportData);
                break;
        }
        
        fclose($file);
        return $filename;
    }

    // Helper methods for calculations and data processing...
    private function getApplicationsByStatus(Collection $applications): array
    {
        return $applications->groupBy('current_step')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getApplicationsByProduct(Collection $applications): array
    {
        return $applications->groupBy(function ($app) {
            return $app->form_data['selectedProduct'] ?? 'Unknown';
        })->map(fn($group) => $group->count())->toArray();
    }

    private function getApplicationsByChannel(Collection $applications): array
    {
        return $applications->groupBy('channel')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getApplicationsByAgent(Collection $applications): array
    {
        return $applications->groupBy(function ($app) {
            return $app->form_data['agentId'] ?? 'Direct';
        })->map(fn($group) => $group->count())->toArray();
    }

    private function getDailyApplicationBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $breakdown = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $count = ApplicationState::whereDate('created_at', $current)->count();
            $breakdown[$current->toDateString()] = $count;
            $current->addDay();
        }
        
        return $breakdown;
    }

    private function getConversionFunnel(Collection $applications): array
    {
        $total = $applications->count();
        $completed = $applications->where('current_step', 'completed')->count();
        $approved = $applications->where('current_step', 'approved')->count();
        
        return [
            'started' => $total,
            'completed' => $completed,
            'approved' => $approved,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'approval_rate' => $completed > 0 ? round(($approved / $completed) * 100, 2) : 0,
            'overall_conversion_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
        ];
    }

    // Additional helper methods would continue here...
    private function calculateAgentConversionRate(Agent $agent, Carbon $startDate, Carbon $endDate): float
    {
        $referred = $agent->applications()->whereBetween('created_at', [$startDate, $endDate])->count();
        $approved = $agent->applications()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('current_step', 'approved')
            ->count();
        
        return $referred > 0 ? round(($approved / $referred) * 100, 2) : 0;
    }

    private function calculateAverageAgentDealSize(Agent $agent, Carbon $startDate, Carbon $endDate): float
    {
        $applications = $agent->applications()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('current_step', 'approved')
            ->get();
        
        if ($applications->isEmpty()) {
            return 0;
        }
        
        $totalValue = $applications->sum(function ($app) {
            return $app->form_data['finalPrice'] ?? 0;
        });
        
        return round($totalValue / $applications->count(), 2);
    }

    // More helper methods for other calculations...
}
