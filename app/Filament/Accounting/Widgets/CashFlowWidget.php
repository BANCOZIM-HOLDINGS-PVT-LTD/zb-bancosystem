<?php

namespace App\Filament\Accounting\Widgets;

use App\Models\ApplicationState;
use App\Models\Commission;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashFlowWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Approved loans value (this month)
        $approvedLoansThisMonth = ApplicationState::where('current_step', 'approved')
            ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get()
            ->sum(fn ($app) => $app->form_data['finalPrice'] ?? $app->form_data['grossLoan'] ?? 0);

        // Total commissions paid (this month)
        $commissionsPaidThisMonth = Commission::where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        // Expenses (this month)
        $expensesThisMonth = Expense::whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        // Pending payroll
        $pendingPayroll = \App\Models\PayrollEntry::where('status', 'pending')
            ->sum('net_pay');

        return [
            Stat::make('Approved Loans (This Month)', '$' . number_format($approvedLoansThisMonth, 2))
                ->description('Total value of approved loans')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success')
                ->chart([20, 30, 45, 60, 75, 90, $approvedLoansThisMonth > 0 ? 100 : 0]),

            Stat::make('Commissions Paid', '$' . number_format($commissionsPaidThisMonth, 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Expenses', '$' . number_format($expensesThisMonth, 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Pending Payroll', '$' . number_format($pendingPayroll, 2))
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayroll > 0 ? 'warning' : 'success'),
        ];
    }
}

