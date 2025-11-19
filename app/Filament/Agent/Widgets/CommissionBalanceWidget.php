<?php

namespace App\Filament\Agent\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CommissionBalanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $agent = Auth::guard('agent')->user();

        if (!$agent) {
            return [];
        }

        // Calculate commission balances
        $pendingCommission = $agent->commissions()
            ->where('status', 'pending')
            ->sum('amount');

        $approvedCommission = $agent->commissions()
            ->where('status', 'approved')
            ->sum('amount');

        $paidCommission = $agent->commissions()
            ->where('status', 'paid')
            ->sum('amount');

        $totalBalance = $pendingCommission + $approvedCommission;

        // Get total clients and approved count
        $totalClients = $agent->applications()->count();
        $approvedClients = $agent->applications()
            ->whereJsonContains('metadata->admin_status', 'approved')
            ->count();

        return [
            Stat::make('Commission Balance', '$' . number_format($totalBalance, 2))
                ->description('Pending + Approved')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([0, $pendingCommission, $approvedCommission, $totalBalance]),

            Stat::make('Pending Commission', '$' . number_format($pendingCommission, 2))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved Commission', '$' . number_format($approvedCommission, 2))
                ->description('Ready for payment')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Total Paid', '$' . number_format($paidCommission, 2))
                ->description('All-time earnings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Clients', $totalClients)
                ->description($approvedClients . ' approved')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Conversion Rate', $agent->conversion_rate . '%')
                ->description('Application success rate')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($agent->conversion_rate >= 50 ? 'success' : 'warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
