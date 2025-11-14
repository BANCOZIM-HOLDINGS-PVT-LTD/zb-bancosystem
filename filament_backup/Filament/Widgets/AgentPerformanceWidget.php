<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\Commission;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgentPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Agent statistics
        $totalAgents = Agent::count();
        $activeAgents = Agent::active()->count();

        // Commission statistics
        $totalCommissions = Commission::sum('amount');
        $thisMonthCommissions = Commission::where('earned_date', '>=', $thisMonth)->sum('amount');
        $lastMonthCommissions = Commission::whereBetween('earned_date', [$lastMonth, $thisMonth])->sum('amount');

        $pendingCommissions = Commission::pending()->sum('amount');
        $paidCommissions = Commission::paid()->sum('amount');

        // Calculate trends
        $commissionTrend = $lastMonthCommissions > 0
            ? round((($thisMonthCommissions - $lastMonthCommissions) / $lastMonthCommissions) * 100, 1)
            : 0;

        // Top performer
        $topAgent = Agent::active()
            ->withCount('applications')
            ->orderBy('applications_count', 'desc')
            ->first();

        return [
            Stat::make('Total Agents', $totalAgents)
                ->description($activeAgents.' active agents')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Commissions', '$'.number_format($totalCommissions, 2))
                ->description('$'.number_format($thisMonthCommissions, 2).' this month')
                ->descriptionIcon($commissionTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($commissionTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getCommissionChart()),

            Stat::make('Pending Commissions', '$'.number_format($pendingCommissions, 2))
                ->description('Awaiting approval/payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCommissions > 10000 ? 'warning' : 'info'),

            Stat::make('Top Performer', $topAgent ? $topAgent->full_name : 'N/A')
                ->description($topAgent ? $topAgent->applications_count.' applications' : 'No data')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
        ];
    }

    /**
     * Get commission chart data for the last 7 days
     */
    private function getCommissionChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->startOfDay();
            $amount = Commission::whereDate('earned_date', $date)->sum('amount');
            $data[] = floatval($amount);
        }

        return $data;
    }
}
